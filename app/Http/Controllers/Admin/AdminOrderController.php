<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderInvoiceMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Notifications\NewOrderReceived;
use App\Notifications\OrderStatusUpdated;
use App\Notifications\RefundRequestSubmitted;
use App\Support\OrderInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class AdminOrderController extends Controller
{
    public function notifications(Request $request): JsonResponse
    {
        $admin = $request->user('admin');
        $notifications = $admin->notifications()
            ->whereIn('type', [NewOrderReceived::class, RefundRequestSubmitted::class])
            ->latest()
            ->limit(30)
            ->get();

        return response()->json([
            'unread_count' => $admin->unreadNotifications()
                ->whereIn('type', [NewOrderReceived::class, RefundRequestSubmitted::class])
                ->count(),
            'notifications' => $notifications->map(function ($notification) {
                $isRefundRequest = $notification->type === RefundRequestSubmitted::class;

                return [
                    'id' => $notification->id,
                    'kind' => $isRefundRequest ? 'refund_request' : 'order',
                    'order_number' => $notification->data['order_number'] ?? 'Order update',
                    'buyer' => $notification->data['buyer'] ?? 'Customer',
                    'total' => (float) ($notification->data['total'] ?? 0),
                    'items' => (int) ($notification->data['items'] ?? 0),
                    'message' => $notification->data['message'] ?? 'A new order was placed.',
                    'url' => $isRefundRequest
                        ? route('admin.refunds.show', ['refundRequest' => (int) ($notification->data['refund_request_id'] ?? 0)])
                        : route('admin.orders.show', ['order' => (int) ($notification->data['order_id'] ?? 0)]),
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->toIso8601String(),
                ];
            })->values(),
        ])->header('Cache-Control', 'no-store');
    }

    public function markNotificationRead(Request $request, string $notification): JsonResponse
    {
        $admin = $request->user('admin');
        $admin->notifications()
            ->whereIn('type', [NewOrderReceived::class, RefundRequestSubmitted::class])
            ->whereKey($notification)
            ->firstOrFail()
            ->markAsRead();

        return response()->json([
            'unread_count' => $admin->unreadNotifications()
                ->whereIn('type', [NewOrderReceived::class, RefundRequestSubmitted::class])
                ->count(),
        ]);
    }

    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $admin = $request->user('admin');
        $admin->unreadNotifications()
            ->whereIn('type', [NewOrderReceived::class, RefundRequestSubmitted::class])
            ->update(['read_at' => now()]);

        return response()->json(['unread_count' => 0]);
    }

    public function index(Request $request): View
    {
        $statuses = ['processing', 'shipped', 'completed', 'cancelled'];
        $request->validate([
            'status' => ['nullable', Rule::in($statuses)],
        ]);

        $orders = Order::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q')->trim()->toString();
                $query->where(function ($query) use ($search) {
                    $query->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->withCount('items')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.orders.index', compact('orders', 'statuses'));
    }

    public function show(Order $order): View
    {
        return view('admin.orders.show', [
            'order' => $order->load('items'),
            'statuses' => ['processing', 'shipped', 'completed', 'cancelled'],
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['processing', 'shipped', 'completed', 'cancelled'])],
        ]);

        $statusChanged = DB::transaction(function () use ($order, $data) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $previousStatus = $lockedOrder->status;
            $items = $lockedOrder->items;
            $quantities = $items->whereNotNull('product_id')
                ->groupBy('product_id')
                ->map(fn ($productItems) => $productItems->sum('quantity'));

            if ($lockedOrder->status !== 'cancelled' && $data['status'] === 'cancelled') {
                OrderInventory::restoreForCancellation($lockedOrder);
            } elseif ($lockedOrder->status === 'cancelled' && $data['status'] !== 'cancelled') {
                $products = Product::query()
                    ->whereIn('id', $quantities->keys())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($quantities as $productId => $quantity) {
                    $product = $products->get($productId);
                    if (! $product || ! $product->is_active || $product->stock < $quantity) {
                        throw ValidationException::withMessages([
                            'status' => ['A product in this order does not have enough active stock to reopen it.'],
                        ]);
                    }

                    $productVariants = $product->variants()->lockForUpdate()->get();
                    if ($productVariants->isNotEmpty()) {
                        $variantItems = $items->where('product_id', $productId)->whereNotNull('color')->groupBy('color');
                        foreach ($variantItems as $variantName => $variantOrderItems) {
                            $variant = $productVariants->firstWhere('name', $variantName);
                            $variantQuantity = $variantOrderItems->sum('quantity');
                            if (! $variant || $variant->stock < $variantQuantity) {
                                throw ValidationException::withMessages([
                                    'status' => ["The {$variantName} option for {$product->name} does not have enough stock to reopen this order."],
                                ]);
                            }
                        }
                    }
                }

                foreach ($quantities as $productId => $quantity) {
                    $products->get($productId)->decrement('stock', $quantity);
                }
                foreach ($items->whereNotNull('product_id')->whereNotNull('color')->groupBy(fn ($item) => $item->product_id . '|' . $item->color) as $variantItems) {
                    $item = $variantItems->first();
                    ProductVariant::query()
                        ->where('product_id', $item->product_id)
                        ->where('name', $item->color)
                        ->decrement('stock', $variantItems->sum('quantity'));
                }
            }

            $lockedOrder->update($data);

            return $previousStatus !== $lockedOrder->status;
        });

        if ($statusChanged && $order->user) {
            $order->user->notify(new OrderStatusUpdated($order->fresh()));
        }

        return redirect()->route('admin.orders.show', $order)->with('status', 'Order status updated.');
    }

    public function exportSalesCsv(Request $request): StreamedResponse
    {
        $range = $request->query('range', 'day');

        abort_unless(in_array($range, ['day', 'month', 'year'], true), 400, 'Invalid sales range.');

        $query = Order::query()
            ->where('status', '!=', 'cancelled')
            ->with('items');

        if ($range === 'day') {
            $query->whereDate('created_at', today());
        } elseif ($range === 'month') {
            $query->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        } else {
            $query->whereYear('created_at', now()->year);
        }

        $orders = $query->orderBy('created_at')->get();

        $filename = sprintf('sales_%s_%s.csv', $range, now()->format('Ymd'));

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Order Number', 'Customer Name', 'Customer Email', 'Status', 'Total', 'Items', 'Created At']);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->order_number,
                    $order->customer_name,
                    $order->customer_email,
                    ucfirst($order->status),
                    number_format((float) $order->total, 2, '.', ''),
                    $order->items->count(),
                    $order->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    public function printInvoice(Order $order): View
    {
        return view('admin.orders.invoice', ['order' => $order->load('items')]);
    }

    public function emailInvoice(Order $order): RedirectResponse
    {
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            return back()->withErrors([
                'invoice' => 'Email delivery is not configured on this server. Configure SMTP in the hosting environment and try again.',
            ]);
        }

        try {
            Mail::to($order->customer_email)->send(new OrderInvoiceMail($order->load('items')));
        } catch (TransportExceptionInterface $exception) {
            Log::error('Order invoice email delivery failed.', [
                'order_id' => $order->id,
                'exception' => $exception::class,
            ]);

            return back()->withErrors([
                'invoice' => 'The invoice could not be emailed. Check the mail settings and try again.',
            ]);
        }

        return redirect()->route('admin.orders.show', $order)->with('status', 'Invoice emailed to the customer.');
    }
}
