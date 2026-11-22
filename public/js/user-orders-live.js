(() => {
    const root = document.body;
    const endpoint = root.dataset.ordersUpdatesUrl;
    if (!endpoint) return;

    const cards = new Map(
        [...document.querySelectorAll('[data-order-id]')]
            .map(card => [card.dataset.orderId, card])
    );
    const validStatuses = new Set(['processing', 'shipped', 'completed', 'cancelled']);
    let checking = false;

    async function refreshOrders() {
        if (document.visibilityState !== 'visible' || checking) return;

        checking = true;
        try {
            const response = await fetch(endpoint, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error(`Order update request failed with status ${response.status}.`);

            const payload = await response.json();
            const serverIds = new Set(payload.orders.map(order => String(order.id)));

            if (payload.orders.some(order => !cards.has(String(order.id))) || [...cards.keys()].some(id => !serverIds.has(id))) {
                window.location.reload();
                return;
            }

            payload.orders.forEach(order => {
                if (!validStatuses.has(order.status)) return;

                const status = cards.get(String(order.id)).querySelector('[data-order-status]');
                const card = cards.get(String(order.id));
                const cancelForm = card.querySelector('[data-order-cancel]');
                if (cancelForm) cancelForm.hidden = order.status !== 'processing';
                if (!status || status.textContent.trim().toLowerCase() === order.status) return;

                status.className = `order-status status-${order.status}`;
                status.textContent = order.status.charAt(0).toUpperCase() + order.status.slice(1);
            });
        } finally {
            checking = false;
        }
    }

    refreshOrders().catch(error => console.error('Unable to refresh customer orders.', error));
    window.setInterval(() => {
        refreshOrders().catch(error => console.error('Unable to refresh customer orders.', error));
    }, 5000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            refreshOrders().catch(error => console.error('Unable to refresh customer orders.', error));
        }
    });
})();
