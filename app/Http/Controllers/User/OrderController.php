<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\NewOrderReceived;
use App\Notifications\OrderPlaced;
use App\Notifications\OrderStatusUpdated;
use App\Support\OrderInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('user.orders', [
            'orders' => Order::query()
                ->where('user_id', $request->user()->id)
                ->with('items')
                ->latest()
                ->get(),
        ]);
    }

    public function updates(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get(['id', 'status']);

        return response()->json(['orders' => $orders])
            ->header('Cache-Control', 'no-store');
    }

    public function cancel(Request $request, int $order): RedirectResponse
    {
        $orderId = DB::transaction(function () use ($request, $order) {
            $lockedOrder = Order::query()
                ->where('user_id', $request->user()->id)
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($order);

            if ($lockedOrder->status !== 'processing') {
                throw ValidationException::withMessages([
                    'order' => ['Only processing orders can be cancelled.'],
                ]);
            }

            OrderInventory::restoreForCancellation($lockedOrder);
            $lockedOrder->update(['status' => 'cancelled']);

            return $lockedOrder->id;
        });

        $request->user()->notify(new OrderStatusUpdated(Order::query()->findOrFail($orderId)));

        return redirect()->route('orders')->with('status', 'Your order has been cancelled.');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.color' => ['nullable', 'string', 'max:50'],
            'customer.name' => ['required', 'string', 'max:120'],
            'customer.phone' => ['required', 'string', 'max:30'],
            'customer.address' => ['required', 'string', 'max:500'],
            'payment_method' => ['required', 'in:Cash on delivery'],
        ]);

        $order = DB::transaction(function () use ($data, $request) {
            $products = Product::query()
                ->whereIn('id', collect($data['items'])->pluck('id'))
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== collect($data['items'])->pluck('id')->unique()->count()) {
                throw ValidationException::withMessages([
                    'items' => ['One or more selected products are no longer available. Refresh your cart and try again.'],
                ]);
            }

            $variantsByProduct = $products->mapWithKeys(function (Product $product) {
                return [$product->id => $product->variants()->lockForUpdate()->get()];
            });
            $selectedVariants = [];
            $variantQuantities = [];
            $quantities = [];

            foreach ($data['items'] as $index => $item) {
                $product = $products->get($item['id']);
                $quantities[$product->id] = ($quantities[$product->id] ?? 0) + $item['quantity'];
                $productVariants = $variantsByProduct->get($product->id);

                if ($productVariants->isNotEmpty()) {
                    $selectedName = trim((string) ($item['color'] ?? ''));
                    $variant = $productVariants->first(
                        fn (ProductVariant $candidate) => mb_strtolower($candidate->name) === mb_strtolower($selectedName)
                    );

                    if (! $variant) {
                        throw ValidationException::withMessages([
                            'items' => ["Choose an available option for {$product->name}."],
                        ]);
                    }

                    $selectedVariants[$index] = $variant;
                    $variantQuantities[$variant->id] = ($variantQuantities[$variant->id] ?? 0) + $item['quantity'];
                } elseif ($product->stock < $quantities[$product->id]) {
                    throw ValidationException::withMessages([
                        'items' => ["There is not enough stock for {$product->name}. Please update the quantity and try again."],
                    ]);
                }
            }

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);
                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => ["There is not enough stock for {$product->name}. Please update the quantity and try again."],
                    ]);
                }
            }
            foreach ($variantQuantities as $variantId => $quantity) {
                $variant = ProductVariant::query()->find($variantId);
                if (! $variant || $variant->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => ['There is not enough stock for one of the selected flavors or colors.'],
                    ]);
                }
            }

            $subtotalCents = collect($data['items'])->sum(
                fn (array $item) => (int) round((float) $products->get($item['id'])->price * 100) * $item['quantity']
            );
            $subtotal = $subtotalCents / 100;
            $order = Order::create([
                'user_id' => $request->user()->id,
                'customer_name' => $data['customer']['name'],
                'customer_email' => $request->user()->email,
                'customer_phone' => $data['customer']['phone'],
                'delivery_address' => $data['customer']['address'],
                'payment_method' => $data['payment_method'],
                'status' => 'processing',
                'subtotal' => $subtotal,
                'shipping_fee' => 0,
                'total' => $subtotal,
            ]);

            foreach ($data['items'] as $index => $item) {
                $product = $products->get($item['id']);
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'brand' => $product->brand,
                    'category' => $product->category,
                    'image_path' => ($selectedVariants[$index] ?? null)?->image_path ?: $product->image_path,
                    'color' => $selectedVariants[$index]->name ?? ($item['color'] ?? null),
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                ]);
            }

            foreach ($variantQuantities as $variantId => $quantity) {
                ProductVariant::query()->whereKey($variantId)->decrement('stock', $quantity);
            }
            foreach ($quantities as $productId => $quantity) {
                $products->get($productId)->decrement('stock', $quantity);
            }

            $order->loadCount('items');
            $request->user()->notify(new OrderPlaced($order));
            User::query()
                ->where(fn ($query) => $query->where('is_admin', true)->orWhere('is_staff', true))
                ->get()
                ->each(fn (User $recipient) => $recipient->notify(new NewOrderReceived($order)));

            return $order;
        });

        return response()->json([
            'message' => 'Your order has been placed.',
            'order' => [
                'id' => $order->id,
                'number' => $order->order_number,
                'total' => (float) $order->total,
                'status' => $order->status,
            ],
        ], 201);
    }
}
