<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportChatConversation;
use App\Models\SupportChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SupportChatController extends Controller
{
    public function index(): View
    {
        return view('admin.support-chat.index');
    }

    public function unreadCount(): JsonResponse
    {
        $unreadCount = SupportChatMessage::query()
            ->where('sender_role', 'customer')
            ->whereNull('read_at')
            ->count();

        return response()->json(['unread_count' => $unreadCount])
            ->header('Cache-Control', 'no-store');
    }

    public function conversations(): JsonResponse
    {
        $conversations = SupportChatConversation::query()
            ->with(['customer', 'latestMessage'])
            ->withCount([
                'messages as unread_count' => fn ($query) => $query
                    ->where('sender_role', 'customer')
                    ->whereNull('read_at'),
            ])
            ->latest('updated_at')
            ->get()
            ->map(fn (SupportChatConversation $conversation) => [
                'id' => $conversation->id,
                'customer' => $conversation->customer->name,
                'preview' => Str::limit($conversation->latestMessage?->message ?? 'No messages yet.', 90),
                'unread_count' => (int) $conversation->unread_count,
                'updated_at' => $conversation->updated_at?->toIso8601String(),
            ])
            ->values();

        return response()->json(['conversations' => $conversations])
            ->header('Cache-Control', 'no-store');
    }

    public function messages(Request $request, SupportChatConversation $conversation): JsonResponse
    {
        $conversation->messages()
            ->where('sender_role', 'customer')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()
            ->orderBy('id')
            ->get()
            ->map(fn (SupportChatMessage $message) => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender_name,
                'sender_role' => $message->sender_role,
                'message' => $message->message,
                'is_mine' => $message->sender_id === $request->user('admin')->id,
                'created_at' => $message->created_at?->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages' => $messages,
        ])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request, SupportChatConversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);
        $staff = $request->user('admin');

        $message = DB::transaction(function () use ($conversation, $staff, $data) {
            $lockedConversation = SupportChatConversation::query()
                ->lockForUpdate()
                ->findOrFail($conversation->id);
            $message = $lockedConversation->messages()->create([
                'sender_id' => $staff->id,
                'sender_role' => $staff->is_admin ? 'admin' : 'staff',
                'sender_name' => $staff->name,
                'message' => $data['message'],
            ]);
            $lockedConversation->touch();

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

    public function destroyConversation(SupportChatConversation $conversation): JsonResponse
    {
        DB::transaction(function () use ($conversation): void {
            SupportChatConversation::query()
                ->lockForUpdate()
                ->findOrFail($conversation->id)
                ->delete();
        });

        return response()->json(['deleted' => true])
            ->header('Cache-Control', 'no-store');
    }
}
