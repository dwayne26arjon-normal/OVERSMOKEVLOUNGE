<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportChatController extends Controller
{
    public function unreadCount(Request $request): JsonResponse
    {
        $conversation = $request->user('web')->supportConversation()->first();
        $unreadCount = $conversation
            ? $conversation->messages()
                ->whereIn('sender_role', ['admin', 'staff'])
                ->whereNull('read_at')
                ->count()
            : 0;

        return response()->json(['unread_count' => $unreadCount])
            ->header('Cache-Control', 'no-store');
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user('web');
        $conversation = $user->supportConversation()->first();

        if (! $conversation) {
            return response()->json([
                'conversation_id' => null,
                'messages' => [],
            ])->header('Cache-Control', 'no-store');
        }

        $conversation->messages()
            ->whereIn('sender_role', ['admin', 'staff'])
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages' => $conversation->messages()
                ->orderBy('id')
                ->get()
                ->map(fn ($message) => [
                    'id' => $message->id,
                    'sender_name' => $message->sender_name,
                    'sender_role' => $message->sender_role,
                    'message' => $message->message,
                    'is_mine' => $message->sender_id === $user->id,
                    'created_at' => $message->created_at?->toIso8601String(),
                ])
                ->values(),
        ])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);
        $user = $request->user('web');

        $message = DB::transaction(function () use ($user, $data) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $conversation = $lockedUser->supportConversation()->firstOrCreate([]);

            $message = $conversation->messages()->create([
                'sender_id' => $lockedUser->id,
                'sender_role' => 'customer',
                'sender_name' => $lockedUser->name,
                'message' => $data['message'],
            ]);
            $conversation->touch();

            return $message;
        });

        return response()->json([
            'message' => [
                'id' => $message->id,
                'sender_name' => $message->sender_name,
                'sender_role' => $message->sender_role,
                'message' => $message->message,
                'is_mine' => true,
                'created_at' => $message->created_at?->toIso8601String(),
            ],
        ], 201)->header('Cache-Control', 'no-store');
    }
}
