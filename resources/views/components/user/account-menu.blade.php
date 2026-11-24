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
    <script defer src="{{ asset('js/user-notifications.js') }}?v={{ filemtime(public_path('js/user-notifications.js')) }}"></script>
@endauth
