(function () {
    const itemsContainer = document.getElementById('cartItems');
    const emptyState = document.getElementById('emptyCart');
    const removedPanel = document.getElementById('removedPanel');
    const removedItemsContainer = document.getElementById('removedItems');
    const selectAll = document.getElementById('selectAll');
    const message = document.getElementById('cartMessage');
    const formatter = new Intl.NumberFormat('en-PH');
    const userId = document.body.dataset.userId;
    const cartKey = `oversmoke-cart-user-${userId}`;
    const removedCartKey = `oversmoke-removed-cart-user-${userId}`;
    const checkoutItemsKey = `oversmoke-checkout-items-user-${userId}`;
    const checkoutSourceKey = `oversmoke-checkout-source-user-${userId}`;
    let items = JSON.parse(localStorage.getItem(cartKey) || '[]');
    let removedItems = JSON.parse(localStorage.getItem(removedCartKey) || '[]');
    const selected = new Set(items.map((item, index) => index));

    function saveCart() {
        localStorage.setItem(cartKey, JSON.stringify(items));
    }

    function saveRemovedItems() {
        localStorage.setItem(removedCartKey, JSON.stringify(removedItems));
    }

    function updateBadge() {
        document.getElementById('cartCount').textContent = items.length;
    }

    function safeProductImage(imageUrl) {
        if (typeof imageUrl !== 'string' || !imageUrl) return null;

        try {
            const parsedUrl = new URL(imageUrl, window.location.origin);
            return parsedUrl.origin === window.location.origin && parsedUrl.pathname.startsWith('/storage/')
                ? parsedUrl.href
                : null;
        } catch {
            return null;
        }
    }

    function render() {
        itemsContainer.replaceChildren();
        emptyState.hidden = items.length > 0;
        selectAll.checked = items.length > 0 && selected.size === items.length;
        selectAll.disabled = items.length === 0;

        items.forEach((item, index) => {
            const row = document.createElement('article');
            row.className = 'cart-row';
            row.dataset.index = index;

            const product = document.createElement('div');
            product.className = 'item-product';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'item-select';
            checkbox.checked = selected.has(index);
            checkbox.setAttribute('aria-label', `Select ${item.name}`);

            const image = document.createElement('div');
            image.className = 'item-image';
            image.setAttribute('aria-hidden', 'true');
            const device = document.createElement('span');
            device.className = 'item-device';
            const imageUrl = safeProductImage(item.image);
            if (imageUrl) {
                const productImage = document.createElement('img');
                productImage.src = imageUrl;
                productImage.alt = '';
                productImage.onerror = () => productImage.replaceWith(device);
                image.append(productImage);
            } else {
                image.append(device);
            }

            const details = document.createElement('div');
            details.className = 'item-details';
            const name = document.createElement('strong');
            name.textContent = item.name;
            const brand = document.createElement('span');
            brand.textContent = item.brand;
            const variant = document.createElement('span');
            variant.textContent = `Variation: ${item.color || 'Default'}`;
            details.append(name, brand, variant);
            product.append(checkbox, image, details);

            const unitPrice = document.createElement('span');
            unitPrice.className = 'unit-price';
            unitPrice.textContent = `₱ ${formatter.format(item.price)}`;

            const quantity = document.createElement('div');
            quantity.className = 'quantity-control';
            const decrease = document.createElement('button');
            decrease.type = 'button';
            decrease.dataset.action = 'decrease';
            decrease.setAttribute('aria-label', `Decrease ${item.name} quantity`);
            decrease.textContent = '−';
            const quantityOutput = document.createElement('output');
            quantityOutput.textContent = item.quantity;
            const increase = document.createElement('button');
            increase.type = 'button';
            increase.dataset.action = 'increase';
            increase.setAttribute('aria-label', `Increase ${item.name} quantity`);
            increase.textContent = '+';
            quantity.append(decrease, quantityOutput, increase);

            const lineTotal = document.createElement('strong');
            lineTotal.className = 'line-total';
            lineTotal.textContent = `₱ ${formatter.format(item.price * item.quantity)}`;

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'remove-item';
            remove.dataset.action = 'remove';
            remove.setAttribute('aria-label', `Remove all ${item.quantity} ${item.name} from cart`);
            remove.textContent = 'Remove all';

            row.append(product, unitPrice, quantity, lineTotal, remove);
            itemsContainer.append(row);
        });

        const selectedItems = items.filter((item, index) => selected.has(index));
        const selectedTotal = selectedItems.reduce((total, item) => total + item.price * item.quantity, 0);
        document.getElementById('selectedCount').textContent = selectedItems.length;
        document.getElementById('cartTotal').textContent = formatter.format(selectedTotal);
        document.getElementById('checkout').disabled = selectedItems.length === 0;
        updateBadge();
        renderRemovedItems();
    }

    function renderRemovedItems() {
        removedItemsContainer.replaceChildren();
        removedPanel.hidden = removedItems.length === 0;

        removedItems.forEach((item, index) => {
            const row = document.createElement('article');
            row.className = 'removed-row';

            const details = document.createElement('div');
            details.className = 'removed-details';
            const name = document.createElement('strong');
            name.textContent = item.name;
            const variant = document.createElement('span');
            variant.textContent = `${item.brand} · Variation: ${item.color || 'Default'} · Qty: ${item.quantity}`;
            details.append(name, variant);

            const actions = document.createElement('div');
            actions.className = 'removed-actions';
            const label = document.createElement('span');
            label.className = 'removed-label';
            label.textContent = 'Removed from cart';
            const restore = document.createElement('button');
            restore.type = 'button';
            restore.className = 'restore-item';
            restore.dataset.action = 'restore';
            restore.dataset.index = index;
            restore.textContent = 'Restore';
            const dismiss = document.createElement('button');
            dismiss.type = 'button';
            dismiss.className = 'dismiss-removed';
            dismiss.dataset.action = 'dismiss';
            dismiss.dataset.index = index;
            dismiss.setAttribute('aria-label', `Dismiss removed ${item.name}`);
            dismiss.textContent = '×';
            actions.append(label, restore, dismiss);

            row.append(details, actions);
            removedItemsContainer.append(row);
        });
    }

    selectAll.addEventListener('change', () => {
        selected.clear();
        if (selectAll.checked) items.forEach((item, index) => selected.add(index));
        render();
    });

    itemsContainer.addEventListener('change', event => {
        if (!event.target.matches('.item-select')) return;
        const index = Number(event.target.closest('.cart-row').dataset.index);
        if (event.target.checked) selected.add(index);
        else selected.delete(index);
        render();
    });

    itemsContainer.addEventListener('click', event => {
        const button = event.target.closest('button[data-action]');
        if (!button) return;

        const action = button.dataset.action;

        if (action === 'remove') {
            const index = Number(button.closest('.cart-row').dataset.index);
            const [removedItem] = items.splice(index, 1);
            removedItems.unshift(removedItem);
            saveRemovedItems();
            selected.clear();
            items.forEach((item, itemIndex) => selected.add(itemIndex));
            message.textContent = `${removedItem.name} removed from cart.`;
        } else if (action === 'increase' || action === 'decrease') {
            const index = Number(button.closest('.cart-row').dataset.index);
            if (action === 'increase') items[index].quantity++;
            else items[index].quantity = Math.max(1, items[index].quantity - 1);
        } else {
            return;
        }

        saveCart();
        render();
    });

    removedItemsContainer.addEventListener('click', event => {
        const button = event.target.closest('button[data-action]');
        if (!button) return;

        const index = Number(button.dataset.index);
        if (button.dataset.action === 'restore') {
            const [restoredItem] = removedItems.splice(index, 1);
            const existingIndex = items.findIndex(item => item.id === restoredItem.id && item.color === restoredItem.color);
            if (existingIndex >= 0) items[existingIndex].quantity += restoredItem.quantity;
            else {
                items.push(restoredItem);
                selected.add(items.length - 1);
            }
            saveCart();
            saveRemovedItems();
            message.textContent = `${restoredItem.name} restored to cart.`;
        } else if (button.dataset.action === 'dismiss') {
            removedItems.splice(index, 1);
            saveRemovedItems();
        } else {
            return;
        }

        render();
    });

    document.getElementById('checkout').addEventListener('click', () => {
        const selectedItems = items.filter((item, index) => selected.has(index));
        if (!selectedItems.length) return;

        sessionStorage.setItem(checkoutItemsKey, JSON.stringify(selectedItems));
        sessionStorage.setItem(checkoutSourceKey, 'cart');
        window.location.href = '/checkout';
    });

    window.addEventListener('pageshow', render);
    window.addEventListener('storage', event => {
        if (event.key === cartKey) {
            items = JSON.parse(event.newValue || '[]');
            selected.clear();
            items.forEach((item, itemIndex) => selected.add(itemIndex));
            render();
        }
        if (event.key === removedCartKey) {
            removedItems = JSON.parse(event.newValue || '[]');
            renderRemovedItems();
        }
    });

    render();
})();
