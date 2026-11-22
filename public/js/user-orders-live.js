(() => {
    const root = document.body;
    const endpoint = root.dataset.ordersUpdatesUrl;
    if (!endpoint) return;

    const cards = new Map(
        [...document.querySelectorAll('[data-order-id]')]
            .map(card => [card.dataset.orderId, card])
    );
    const validStatuses = new Set(['processing', 'shipped', 'completed', 'cancelled']);
    const filterButtons = [...document.querySelectorAll('[data-order-filter]')];
    const filterCounts = [...document.querySelectorAll('[data-order-filter-count]')];
    const filteredEmpty = document.querySelector('[data-orders-filter-empty]');
    let activeFilter = 'all';
    let checking = false;

    function updateOrderFilters() {
        const counts = new Map([['all', 0], ...[...validStatuses].map(status => [status, 0])]);
        let visibleCount = 0;

        cards.forEach(card => {
            const status = card.dataset.orderState;
            counts.set('all', counts.get('all') + 1);
            counts.set(status, (counts.get(status) || 0) + 1);

            const visible = activeFilter === 'all' || status === activeFilter;
            card.hidden = !visible;
            if (visible) visibleCount++;
        });

        filterCounts.forEach(count => {
            count.textContent = String(counts.get(count.dataset.orderFilterCount) || 0);
        });

        if (filteredEmpty) filteredEmpty.hidden = visibleCount > 0;
    }

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            activeFilter = button.dataset.orderFilter;
            filterButtons.forEach(filter => {
                filter.setAttribute('aria-pressed', String(filter === button));
            });
            updateOrderFilters();
        });
    });
    updateOrderFilters();

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

                const card = cards.get(String(order.id));
                const status = card.querySelector('[data-order-status]');
                const cancelForm = card.querySelector('[data-order-cancel]');
                card.dataset.orderState = order.status;
                if (cancelForm) cancelForm.hidden = order.status !== 'processing';
                if (!status || status.textContent.trim().toLowerCase() === order.status) return;

                status.className = `order-status status-${order.status}`;
                status.textContent = order.status.charAt(0).toUpperCase() + order.status.slice(1);
            });
            updateOrderFilters();
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
