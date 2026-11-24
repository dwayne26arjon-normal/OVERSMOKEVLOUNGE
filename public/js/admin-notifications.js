(() => {
    const root = document.getElementById('adminNotifications');
    if (!root) return;

    const button = document.getElementById('notificationButton');
    const menu = document.getElementById('notificationMenu');
    const list = document.getElementById('notificationList');
    const count = document.getElementById('notificationCount');
    const status = document.getElementById('notificationStatus');
    const markAllButton = document.getElementById('markAllNotificationsRead');
    const ordersBadge = document.querySelector('[data-admin-orders-unread-count]');
    const refundsBadge = document.querySelector('[data-admin-refunds-unread-count]');
    const ordersLink = ordersBadge ? ordersBadge.closest('a') : null;
    const refundsLink = refundsBadge ? refundsBadge.closest('a') : null;
    const csrfToken = root.dataset.csrfToken;
    let knownIds = new Set();
    let initialized = false;
    let checking = false;
    let toastTimer;

    function renderSidebarBadge(badge, link, value, label) {
        if (!badge || !link) return;

        const unreadCount = Math.max(0, Number(value) || 0);
        badge.hidden = unreadCount === 0;
        badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
        link.setAttribute('aria-label', unreadCount > 0
            ? `${label}, ${unreadCount} unread`
            : label);
    }

    function showToast(notification) {
        const toast = document.getElementById('orderToast');
        toast.replaceChildren();
        const title = document.createElement('strong');
        const isRefundRequest = notification.kind === 'refund_request';
        title.textContent = isRefundRequest ? 'New refund request' : 'New purchase received';
        const link = document.createElement('a');
        link.href = notification.url;
        link.textContent = isRefundRequest
            ? `${notification.order_number} · ${notification.buyer}`
            : `${notification.order_number} from ${notification.buyer} · ₱${Number(notification.total).toLocaleString('en-PH')}`;
        toast.append(title, link);
        toast.hidden = false;
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => {
            toast.hidden = true;
        }, 7000);
    }

    async function sendRequest(url, method = 'PATCH') {
        const response = await fetch(url, {
            method,
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin'
        });
        if (!response.ok) throw new Error(`Notification request failed with status ${response.status}.`);
        return response.json();
    }

    function renderNotifications(payload) {
        const notifications = Array.isArray(payload.notifications) ? payload.notifications : [];
        const unreadCount = Number(payload.unread_count) || 0;
        count.hidden = unreadCount === 0;
        count.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
        renderSidebarBadge(ordersBadge, ordersLink, payload.processing_orders_count, 'Orders awaiting processing');
        renderSidebarBadge(refundsBadge, refundsLink, payload.pending_refund_requests_count, 'Refund requests awaiting review');
        list.replaceChildren();

        if (!notifications.length) {
            const empty = document.createElement('p');
            empty.className = 'notification-empty';
            empty.textContent = 'No notifications yet.';
            list.append(empty);
            return;
        }

        notifications.forEach(notification => {
            const link = document.createElement('a');
            link.className = 'notification-item';
            if (!notification.read_at) link.classList.add('is-unread');
            link.href = notification.url;

            const title = document.createElement('strong');
            title.textContent = notification.order_number;
            const details = document.createElement('span');
            details.textContent = notification.kind === 'refund_request'
                ? notification.message
                : `${notification.buyer} · ${notification.items} item${notification.items === 1 ? '' : 's'} · ₱${Number(notification.total).toLocaleString('en-PH')}`;
            const date = document.createElement('time');
            date.dateTime = notification.created_at;
            date.textContent = new Date(notification.created_at).toLocaleString();
            link.append(title, details, date);
            link.addEventListener('click', async event => {
                if (notification.read_at) return;

                event.preventDefault();
                try {
                    await sendRequest(root.dataset.readUrlTemplate.replace('__NOTIFICATION_ID__', encodeURIComponent(notification.id)));
                    window.location.assign(link.href);
                } catch (error) {
                    console.error('Unable to mark the admin notification as read.', error);
                    status.textContent = 'Could not open this notification. Please try again.';
                }
            });
            list.append(link);
        });
    }

    async function refreshNotifications() {
        if (checking || document.visibilityState !== 'visible') return;

        checking = true;
        try {
            const response = await fetch(root.dataset.endpoint, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store'
            });
            if (!response.ok) throw new Error(`Notification request failed with status ${response.status}.`);

            const payload = await response.json();
            const notifications = Array.isArray(payload.notifications) ? payload.notifications : [];
            const freshNotification = initialized
                ? notifications.find(notification => !knownIds.has(notification.id) && !notification.read_at)
                : null;

            knownIds = new Set(notifications.map(notification => notification.id));
            renderNotifications(payload);
            if (freshNotification) showToast(freshNotification);
            initialized = true;
            status.textContent = '';
        } finally {
            checking = false;
        }
    }

    function refreshWithErrorHandling() {
        refreshNotifications().catch(error => {
            console.error('Unable to load admin notifications.', error);
            status.textContent = 'Notifications could not be loaded. Please try again.';
        });
    }

    button.addEventListener('click', () => {
        const open = button.getAttribute('aria-expanded') !== 'true';
        button.setAttribute('aria-expanded', String(open));
        menu.hidden = !open;
        if (open) refreshWithErrorHandling();
    });

    markAllButton.addEventListener('click', async () => {
        markAllButton.disabled = true;
        try {
            await sendRequest(root.dataset.readAllUrl);
            await refreshNotifications();
            status.textContent = 'All notifications marked as read.';
        } catch (error) {
            console.error('Unable to mark admin notifications as read.', error);
            status.textContent = 'Could not mark notifications as read. Please try again.';
        } finally {
            markAllButton.disabled = false;
        }
    });

    document.addEventListener('click', event => {
        if (!event.target.closest('#adminNotifications')) {
            menu.hidden = true;
            button.setAttribute('aria-expanded', 'false');
        }
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !menu.hidden) {
            menu.hidden = true;
            button.setAttribute('aria-expanded', 'false');
            button.focus();
        }
    });

    refreshWithErrorHandling();
    window.setInterval(refreshWithErrorHandling, 10000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') refreshWithErrorHandling();
    });
})();
