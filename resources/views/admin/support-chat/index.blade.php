@extends('admin.layout')

@section('title', 'Support chat')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Support chat</h1>
            <p>Reply to customer messages. Conversations refresh automatically.</p>
        </div>
    </div>

    <section
        class="panel admin-support-chat"
        data-admin-support-chat
        data-conversations-url="{{ route('admin.support-chat.conversations') }}"
        data-messages-url-template="{{ route('admin.support-chat.messages', ['conversation' => '__CONVERSATION_ID__']) }}"
        data-send-url-template="{{ route('admin.support-chat.messages.store', ['conversation' => '__CONVERSATION_ID__']) }}"
        data-delete-url-template="{{ route('admin.support-chat.conversations.destroy', ['conversation' => '__CONVERSATION_ID__']) }}"
        data-csrf-token="{{ csrf_token() }}"
    >
        <aside class="admin-chat-inbox" aria-label="Customer conversations">
            <h2>Conversations</h2>
            <div class="admin-chat-conversation-list" data-chat-conversations aria-live="polite">
                <p class="admin-chat-empty-list">Loading conversations...</p>
            </div>
        </aside>
        <section class="admin-chat-thread" aria-label="Selected conversation">
            <header class="admin-chat-thread-header">
                <div class="admin-chat-thread-identity">
                    <strong data-chat-customer>Select a conversation</strong>
                    <span>Customer support</span>
                </div>
                <button class="admin-chat-delete" type="button" data-chat-delete hidden>Delete conversation</button>
            </header>
            <div class="admin-chat-thread-messages" data-chat-messages role="log" aria-live="polite" aria-relevant="additions text">
                <p class="admin-chat-empty-message">Select a customer conversation to view messages.</p>
            </div>
            <p class="admin-chat-status" data-chat-status role="status" aria-live="polite"></p>
            <form class="admin-chat-reply" data-chat-form hidden>
                <label class="sr-only" for="adminSupportChatMessage">Your reply</label>
                <textarea id="adminSupportChatMessage" data-chat-input rows="2" maxlength="2000" placeholder="Write a reply..." required></textarea>
                <button type="submit" data-chat-send>Send reply</button>
            </form>
        </section>
    </section>
    @include('components.chat-confirmation-dialog')
@endsection

@push('styles')
    <style>
        .admin-support-chat { display: grid; min-height: min(680px, calc(100vh - 180px)); grid-template-columns: minmax(220px, .75fr) minmax(0, 1.6fr); overflow: hidden; }
        .admin-chat-inbox { min-width: 0; border-right: 1px solid #302a3b; }
        .admin-chat-inbox h2 { margin: 0; padding: 14px; border-bottom: 1px solid #302a3b; color: #eeeaf5; font: 600 12px 'Outfit', sans-serif; }
        .admin-chat-conversation-list { max-height: 680px; overflow-y: auto; }
        .admin-chat-conversation { display: grid; width: 100%; gap: 5px; padding: 12px 14px; border: 0; border-bottom: 1px solid rgba(48,42,59,.65); color: #c9c3d1; background: transparent; font: inherit; text-align: left; cursor: pointer; }
        .admin-chat-conversation:hover, .admin-chat-conversation.is-active { outline: 0; background: rgba(116,79,205,.14); }
        .admin-chat-conversation-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .admin-chat-conversation-top strong { color: #f1ecfa; font-size: 11px; }
        .admin-chat-preview { overflow: hidden; color: #a9a2b1; font-size: 10px; text-overflow: ellipsis; white-space: nowrap; }
        .admin-chat-conversation time { color: #7f788b; font-size: 9px; }
        .admin-chat-unread { display: grid; min-width: 17px; height: 17px; place-items: center; padding: 0 4px; border-radius: 999px; color: #fff; background: #e74d70; font-size: 9px; font-weight: 700; }
        .admin-chat-empty-list, .admin-chat-empty-message { margin: 0; padding: 24px 14px; color: #898292; font-size: 10px; text-align: center; }
        .admin-chat-thread { display: grid; min-width: 0; grid-template-rows: auto minmax(0, 1fr) auto auto; }
        .admin-chat-thread-header { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 14px 16px; border-bottom: 1px solid #302a3b; }
        .admin-chat-thread-identity { display: grid; min-width: 0; gap: 3px; }
        .admin-chat-thread-header strong { color: #f1ecfa; font-size: 12px; }
        .admin-chat-thread-header span { color: #898292; font-size: 9px; }
        .admin-chat-delete { flex: 0 0 auto; padding: 6px 9px; border: 1px solid rgba(255,101,123,.35); border-radius: 6px; color: #ff9aa6; background: rgba(139,43,61,.13); font: inherit; font-size: 9px; cursor: pointer; }
        .admin-chat-delete:hover, .admin-chat-delete:focus-visible { border-color: #ff9aa6; outline: 0; }
        .admin-chat-delete:disabled { opacity: .6; cursor: wait; }
        .chat-confirm-dialog { width: min(420px, calc(100% - 32px)); padding: 24px; border: 1px solid rgba(255,101,123,.4); border-radius: 14px; color: #f4f1ff; background: #15121c; box-shadow: 0 24px 80px rgba(0,0,0,.58); }
        .chat-confirm-dialog::backdrop { background: rgba(5,4,8,.75); backdrop-filter: blur(4px); }
        .chat-confirm-icon { display: grid; width: 38px; height: 38px; margin-bottom: 12px; place-items: center; border: 1px solid rgba(255,101,123,.4); border-radius: 50%; color: #ff9aa6; background: rgba(139,43,61,.16); font-size: 19px; font-weight: 700; }
        .chat-confirm-eyebrow { margin: 0 0 5px; color: #ff9aa6; font-size: 9px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .chat-confirm-dialog h2 { margin: 0; color: #f4f1ff; font: 600 16px 'Outfit', sans-serif; }
        .chat-confirm-dialog > p:not(.chat-confirm-eyebrow) { margin: 8px 0 0; color: #aaa4b2; font-size: 11px; line-height: 1.55; }
        .chat-confirm-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 21px; }
        .chat-confirm-actions button { min-height: 34px; padding: 0 12px; border: 1px solid #393347; border-radius: 6px; color: #d0cad8; background: #1b1823; font: inherit; font-size: 10px; font-weight: 600; cursor: pointer; }
        .chat-confirm-actions button:focus-visible { outline: 2px solid #a98cff; outline-offset: 2px; }
        .chat-confirm-actions .chat-confirm-proceed { border-color: #9c3e50; color: #fff; background: #8d3043; }
        .chat-confirm-actions .chat-confirm-proceed:hover { border-color: #e85c74; background: #a63b50; }
        .admin-chat-thread-messages { display: flex; min-height: 320px; max-height: 580px; flex-direction: column; gap: 10px; overflow-y: auto; padding: 16px; }
        .admin-chat-bubble { display: grid; max-width: 82%; align-self: flex-start; gap: 5px; padding: 9px 11px; border: 1px solid #332d3f; border-radius: 10px 10px 10px 3px; color: #e7e2ee; background: #1b1823; }
        .admin-chat-bubble.is-mine { align-self: flex-end; border-color: rgba(130,98,220,.45); border-radius: 10px 10px 3px 10px; background: rgba(100,72,177,.25); }
        .admin-chat-sender-line { display: flex; align-items: center; gap: 7px; }
        .admin-chat-bubble strong { color: #c9b8ff; font-size: 9px; }
        .admin-chat-role { display: inline-flex; align-items: center; padding: 2px 6px; border: 1px solid transparent; border-radius: 999px; font-size: 8px; font-weight: 600; line-height: 1.2; }
        .admin-chat-role.is-customer { border-color: rgba(113,169,255,.28); color: #a9cbff; background: rgba(70,119,197,.15); }
        .admin-chat-role.is-support { border-color: rgba(177,145,255,.3); color: #d3c0ff; background: rgba(116,79,205,.18); }
        .admin-chat-bubble p { margin: 0; font-size: 11px; line-height: 1.5; overflow-wrap: anywhere; white-space: pre-wrap; }
        .admin-chat-bubble time { color: #928b9c; font-size: 9px; }
        .admin-chat-status { margin: 0; padding: 0 12px; color: #ff9aa6; font-size: 10px; }
        .admin-chat-status:empty { display: none; }
        .admin-chat-reply { display: flex; align-items: flex-end; gap: 8px; padding: 11px; border-top: 1px solid #302a3b; }
        .admin-chat-reply textarea { min-width: 0; min-height: 38px; max-height: 110px; flex: 1; resize: vertical; padding: 9px; border: 1px solid #393347; border-radius: 7px; color: #eeeaf5; background: #0c0b11; font: inherit; font-size: 10px; }
        .admin-chat-reply textarea:focus { border-color: #7859ce; outline: 2px solid rgba(120,89,206,.2); }
        .admin-chat-reply button { min-height: 38px; padding: 0 12px; border: 1px solid #6345bd; border-radius: 7px; color: #fff; background: #5a3ab2; font: inherit; font-size: 10px; font-weight: 600; cursor: pointer; }
        .admin-chat-reply button:disabled { opacity: .6; cursor: wait; }
        @media (max-width: 700px) {
            .admin-support-chat { min-height: calc(100vh - 150px); grid-template-columns: minmax(105px, .65fr) minmax(0, 1.5fr); }
            .admin-chat-inbox h2 { padding: 11px 8px; font-size: 10px; }
            .admin-chat-conversation { padding: 10px 8px; }
            .admin-chat-conversation-top { align-items: flex-start; }
            .admin-chat-conversation-top strong { overflow-wrap: anywhere; }
            .admin-chat-thread-messages { padding: 10px; }
            .admin-chat-reply { flex-direction: column; align-items: stretch; }
            .admin-chat-reply button { align-self: flex-end; }
        }
    </style>
@endpush

@push('scripts')
    <script defer src="{{ asset('js/chat-confirmation.js') }}?v={{ filemtime(public_path('js/chat-confirmation.js')) }}"></script>
    <script defer src="{{ asset('js/admin-support-chat.js') }}?v={{ filemtime(public_path('js/admin-support-chat.js')) }}"></script>
@endpush
