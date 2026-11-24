<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Notifications\RefundRequestReviewed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'enabled' => (bool) $user->order_notifications_enabled,
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn ($notification) => [
                    'id' => $notification->id,
                    'kind' => $notification->type === RefundRequestReviewed::class ? 'refund_review' : 'order',
                    'order_number' => $notification->data['order_number'] ?? 'Order update',
                    'message' => $notification->data['message'] ?? 'Your order was updated.',
                    'url' => route('orders') . '#order-' . (int) ($notification->data['order_id'] ?? 0),
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->toIso8601String(),
                ])
                ->values(),
        ])->header('Cache-Control', 'no-store');
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail()
            ->markAsRead();

        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['unread_count' => 0]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $request->user()->update([
            'order_notifications_enabled' => $data['enabled'],
        ]);

        return response()->json([
            'enabled' => (bool) $request->user()->fresh()->order_notifications_enabled,
        ]);
    }
}
