(() => {
    const root = document.querySelector('[data-user-notifications]');
    if (!root) return;

    const button = root.querySelector('#userNotificationButton');
    const menu = root.querySelector('#userNotificationMenu');
    const count = root.querySelector('[data-notification-count]');
    const accountActions = root.parentElement;
    const toggle = accountActions.querySelector('[data-notification-toggle]');
    const toggleState = accountActions.querySelector('[data-notification-state]');
    const settingStatus = accountActions.querySelector('[data-notification-setting-status]');
    const status = root.querySelector('[data-notification-status]');
    const list = root.querySelector('[data-notification-list]');
    const toast = root.querySelector('[data-notification-toast]');
    const csrfToken = root.dataset.csrfToken;
    let knownIds = new Set();
    let initialized = false;
    let checking = false;
    let toastTimer;

    function showStatus(message) {
        status.textContent = message;
    }

    function showSettingStatus(message) {
        settingStatus.textContent = message;
        settingStatus.hidden = !message;
    }

    function showToast(notification) {
        toast.replaceChildren();
        const title = document.createElement('strong');
        title.textContent = notification.order_number;
        const message = document.createElement('span');
        message.textContent = notification.message;
        toast.append(title, message);
        toast.hidden = false;
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => {
            toast.hidden = true;
        }, 7000);
    }

    function renderNotifications(payload) {
        const unreadCount = Number(payload.unread_count) || 0;
        count.hidden = unreadCount === 0;
        count.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
        toggle.checked = Boolean(payload.enabled);
        toggleState.textContent = payload.enabled ? 'On' : 'Off';
        list.replaceChildren();

        if (!payload.notifications.length) {
            const empty = document.createElement('p');
            empty.className = 'user-notification-empty';
            empty.textContent = 'No notifications yet.';
            list.append(empty);
            return;
        }

        payload.notifications.forEach(notification => {
            const link = document.createElement('a');
            link.className = 'user-notification-item';
            if (!notification.read_at) link.classList.add('is-unread');
            link.href = notification.url;

            const title = document.createElement('strong');
            title.textContent = notification.order_number;
            const message = document.createElement('span');
            message.textContent = notification.message;
            const date = document.createElement('time');
            date.dateTime = notification.created_at;
            date.textContent = new Date(notification.created_at).toLocaleString();
            link.append(title, message, date);
            link.addEventListener('click', async event => {
                event.preventDefault();
                try {
                    await sendRequest(root.dataset.readUrlTemplate.replace('__NOTIFICATION_ID__', encodeURIComponent(notification.id)));
                    window.location.assign(link.href);
                } catch (error) {
                    console.error('Unable to mark the order notification as read.', error);
                    showStatus('Could not open this update. Please try again.');
                }
            });
            list.append(link);
        });
    }

    async function sendRequest(url, method = 'PATCH', body = undefined) {
        const response = await fetch(url, {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: body === undefined ? undefined : JSON.stringify(body)
        });
        if (!response.ok) throw new Error(`Notification request failed with status ${response.status}.`);
        return response.status === 204 ? {} : response.json();
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
            const freshNotification = initialized
                ? payload.notifications.find(notification =>
                    !knownIds.has(notification.id)
                    && !notification.read_at
                    && (payload.enabled || notification.kind === 'refund_review'))
                : null;

            knownIds = new Set(payload.notifications.map(notification => notification.id));
            renderNotifications(payload);
            if (freshNotification) showToast(freshNotification);
            initialized = true;
            showStatus('');
        } finally {
            checking = false;
        }
    }

    button.addEventListener('click', () => {
        const open = button.getAttribute('aria-expanded') !== 'true';
        button.setAttribute('aria-expanded', String(open));
        menu.hidden = !open;
        if (open) refreshNotifications().catch(error => {
            console.error('Unable to load notifications.', error);
            showStatus('Notifications could not be loaded. Please try again.');
        });
    });

    toggle.addEventListener('change', async () => {
        toggle.disabled = true;
        try {
            const payload = await sendRequest(root.dataset.settingsUrl, 'PATCH', { enabled: toggle.checked });
            toggle.checked = payload.enabled;
            toggleState.textContent = payload.enabled ? 'On' : 'Off';
            showSettingStatus(payload.enabled ? 'Order notifications are on.' : 'Order notifications are off.');
        } catch (error) {
            console.error('Unable to update order notification settings.', error);
            toggle.checked = !toggle.checked;
            showSettingStatus('Could not save your notification setting. Please try again.');
        } finally {
            toggle.disabled = false;
        }
    });

    root.querySelector('[data-notification-read-all]').addEventListener('click', async () => {
        try {
            await sendRequest(root.dataset.readAllUrl);
            await refreshNotifications();
            showStatus('All order updates marked as read.');
        } catch (error) {
            console.error('Unable to mark order notifications as read.', error);
            showStatus('Could not mark updates as read. Please try again.');
        }
    });

    document.addEventListener('click', event => {
        if (!event.target.closest('[data-user-notifications]')) {
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

    refreshNotifications().catch(error => {
        console.error('Unable to load notifications.', error);
        showStatus('Notifications could not be loaded. Please try again.');
    });
    window.setInterval(() => {
        refreshNotifications().catch(error => {
            console.error('Unable to refresh notifications.', error);
            showStatus('Notifications could not be refreshed.');
        });
    }, 10000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            refreshNotifications().catch(error => {
                console.error('Unable to refresh notifications.', error);
                showStatus('Notifications could not be refreshed.');
            });
        }
    });
})();
