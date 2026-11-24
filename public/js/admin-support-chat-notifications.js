(() => {
    const link = document.querySelector('[data-admin-chat-unread-url]');
    if (!link) return;

    const badge = link.querySelector('[data-admin-chat-unread-count]');
    let checking = false;

    function renderCount(value) {
        const unreadCount = Math.max(0, Number(value) || 0);
        badge.hidden = unreadCount === 0;
        badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
        link.setAttribute('aria-label', unreadCount > 0
            ? `Support chat, ${unreadCount} unread messages`
            : 'Support chat');
    }

    async function refresh() {
        if (checking || document.visibilityState !== 'visible') return;

        checking = true;
        try {
            const response = await fetch(link.dataset.adminChatUnreadUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store'
            });
            if (!response.ok) throw new Error(`Support chat unread request failed with status ${response.status}.`);
            const payload = await response.json();
            renderCount(payload.unread_count);
        } catch (error) {
            console.error('Unable to refresh admin support chat unread count.', error);
        } finally {
            checking = false;
        }
    }

    refresh();
    window.setInterval(refresh, 5000);
    document.addEventListener('visibilitychange', refresh);
})();
