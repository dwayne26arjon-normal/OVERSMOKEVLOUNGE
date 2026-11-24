<div class="account-actions">
    @auth('web')
        <div
            class="user-notifications"
            id="userNotifications"
            data-user-notifications
            data-endpoint="{{ route('user.notifications.index') }}"
            data-settings-url="{{ route('user.notifications.settings') }}"
            data-read-url-template="{{ route('user.notifications.read', ['notification' => '__NOTIFICATION_ID__']) }}"
            data-read-all-url="{{ route('user.notifications.read-all') }}"
            data-csrf-token="{{ csrf_token() }}"
        >
            <button class="user-notification-button" id="userNotificationButton" type="button" aria-label="Notifications" aria-expanded="false" aria-controls="userNotificationMenu">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
                <span class="user-notification-count" data-notification-count hidden></span>
            </button>
            <section class="user-notification-menu" id="userNotificationMenu" aria-label="Notifications" hidden>
                <div class="user-notification-heading">
                    <strong>Notifications</strong>
                    <button class="user-notification-read-all" type="button" data-notification-read-all>Mark all read</button>
                </div>
                <p class="user-notification-status" data-notification-status role="status" aria-live="polite"></p>
                <div class="user-notification-list" data-notification-list aria-live="polite"></div>
            </section>
            <div class="user-notification-toast" data-notification-toast role="status" aria-live="polite" hidden></div>
        </div>
        <button class="account-button" type="button" aria-label="Account for {{ auth('web')->user()->name }}" aria-haspopup="true" aria-expanded="false" aria-controls="accountMenu">
            <span class="account-greeting">Hi, {{ \Illuminate\Support\Str::title(\Illuminate\Support\Str::before(trim(auth('web')->user()->name), ' ')) }}</span>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M6.2 19a6 6 0 0 1 11.6 0"/></svg>
        </button>
        <div class="account-menu" id="accountMenu" hidden>
            <a href="{{ route('profile.edit') }}">Edit profile</a>
            <label class="account-notification-toggle">
                <span>Order notifications</span>
                <input type="checkbox" role="switch" aria-label="Order notifications" data-notification-toggle @checked(auth('web')->user()->order_notifications_enabled)>
                <strong data-notification-state>{{ auth('web')->user()->order_notifications_enabled ? 'On' : 'Off' }}</strong>
            </label>
            <p class="account-notification-status" data-notification-setting-status role="status" aria-live="polite" hidden></p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Log out</button>
            </form>
        </div>
    @else
        <a class="guest-auth-link guest-login" href="{{ url('/go/login') }}">Log in</a>
        <a class="guest-auth-link guest-signup" href="{{ url('/go/register') }}">Sign up</a>
    @endauth
</div>
@auth('web')
    <div
        class="user-support-chat"
        data-user-support-chat
        data-user-id="{{ auth('web')->id() }}"
        data-endpoint="{{ route('support-chat.index') }}"
        data-send-url="{{ route('support-chat.messages.store') }}"
        data-csrf-token="{{ csrf_token() }}"
    >
        <button class="support-chat-launcher" type="button" data-chat-toggle aria-expanded="false" aria-controls="userSupportChatPanel">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H5l1.5-3A7.5 7.5 0 1 1 20 11.5Z"/><path d="M8.5 11h7M8.5 14h4"/></svg>
            <span>Support chat</span>
        </button>
        <section class="support-chat-panel" id="userSupportChatPanel" aria-label="Support chat" hidden>
            <header class="support-chat-header">
                <div><strong>Support chat</strong><span>Chat with our store team</span></div>
                <button class="support-chat-close" type="button" data-chat-close aria-label="Close support chat">×</button>
            </header>
            <div class="support-chat-messages" data-chat-messages role="log" aria-live="polite" aria-relevant="additions text">
                <p class="support-chat-empty">Send a message to start a conversation.</p>
            </div>
            <p class="support-chat-status" data-chat-status role="status" aria-live="polite"></p>
            <form class="support-chat-form" data-chat-form>
                <label class="support-chat-sr-only" for="userSupportChatMessage">Your message</label>
                <textarea id="userSupportChatMessage" data-chat-input rows="2" maxlength="2000" placeholder="Write a message..." required></textarea>
                <button type="submit" data-chat-send>Send</button>
            </form>
        </section>
    </div>
    <script defer src="{{ asset('js/user-notifications.js') }}?v={{ filemtime(public_path('js/user-notifications.js')) }}"></script>
    <script defer src="{{ asset('js/user-support-chat.js') }}?v={{ filemtime(public_path('js/user-support-chat.js')) }}"></script>
@endauth
