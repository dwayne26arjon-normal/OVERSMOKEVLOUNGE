(function () {
    const formatter = new Intl.NumberFormat('en-PH');
    const orderItemsContainer = document.getElementById('orderItems');
    const emptyState = document.getElementById('emptyState');
    const confirmButton = document.getElementById('confirmPurchase');
    const form = document.getElementById('checkoutForm');
    const addressPreview = document.getElementById('addressPreview');
    const message = document.getElementById('checkoutMessage');
    const successSplash = document.getElementById('successSplash');
    const userId = document.body.dataset.userId;
    const cartKey = `oversmoke-cart-user-${userId}`;
    const deliveryDetailsKey = `oversmoke-delivery-details-user-${userId}`;
    const checkoutItemsKey = `oversmoke-checkout-items-user-${userId}`;
    const checkoutSourceKey = `oversmoke-checkout-source-user-${userId}`;
    let items = JSON.parse(sessionStorage.getItem(checkoutItemsKey) || '[]');
    let checkoutSource = sessionStorage.getItem(checkoutSourceKey) || 'cart';
    let details = {
        name: document.getElementById('customerName').value,
        phone: document.getElementById('phone').value,
        street: document.getElementById('street').value,
        barangay: document.getElementById('barangay').value,
        city: document.getElementById('city').value,
        province: document.getElementById('province').value,
        postal_code: document.getElementById('postalCode').value,
        paymentMethod: 'Cash on delivery'
    };
    const legacyAddress = addressPreview.dataset.legacyAddress || '';
    const savedCheckoutDetails = JSON.parse(localStorage.getItem(deliveryDetailsKey) || '{}');
    details.paymentMethod = savedCheckoutDetails.paymentMethod || details.paymentMethod;

    function updateBadge() {
        const cart = JSON.parse(localStorage.getItem(cartKey) || '[]');
        document.getElementById('cartCount').textContent = cart.length;
    }

    function renderItems() {
        orderItemsContainer.replaceChildren();
        let total = 0;

        items.forEach(item => {
            const row = document.createElement('article');
            row.className = 'order-item';

            const description = document.createElement('div');
            description.className = 'item-description';
            const name = document.createElement('strong');
            name.textContent = item.name;
            const brandAndColor = document.createElement('span');
            brandAndColor.textContent = `${item.brand} · ${item.color || 'Default'}`;
            description.append(name, brandAndColor);

            const quantity = document.createElement('span');
            quantity.className = 'item-quantity';
            quantity.textContent = `Quantity: ${item.quantity}`;

            const lineTotal = item.price * item.quantity;
            total += lineTotal;
            const price = document.createElement('strong');
            price.className = 'item-total';
            price.textContent = `₱ ${formatter.format(lineTotal)}`;

            row.append(description, quantity, price);
            orderItemsContainer.append(row);
        });

        document.getElementById('orderTotal').textContent = formatter.format(total);
        emptyState.hidden = items.length > 0;
        confirmButton.disabled = items.length === 0;
    }

    function updateAddressPreview() {
        const address = formattedAddress(details) || legacyAddress;
        addressPreview.textContent = details.phone && address
            ? `${details.phone} · ${address}`
            : 'Add your phone number and delivery address.';
    }

    function formattedAddress(source) {
        return [source.street, source.barangay, source.city, source.province, source.postal_code]
            .map(value => value.trim())
            .filter(Boolean)
            .join(', ');
    }

    function hasCompleteAddress(source) {
        return [source.street, source.barangay, source.city, source.province, source.postal_code]
            .every(value => value.trim() !== '');
    }

    function showSavedDetails() {
        document.getElementById('paymentMethod').value = details.paymentMethod;
    }

    function readFormDetails() {
        details = {
            name: document.getElementById('customerName').value.trim(),
            phone: document.getElementById('phone').value.trim(),
            street: document.getElementById('street').value.trim(),
            barangay: document.getElementById('barangay').value.trim(),
            city: document.getElementById('city').value.trim(),
            province: document.getElementById('province').value.trim(),
            postal_code: document.getElementById('postalCode').value.trim(),
            paymentMethod: document.getElementById('paymentMethod').value
        };
    }

    function saveFormDetails() {
        localStorage.setItem(deliveryDetailsKey, JSON.stringify(details));
        updateAddressPreview();
    }

    document.getElementById('editDetails').addEventListener('click', () => {
        form.hidden = !form.hidden;
        if (!form.hidden) document.getElementById('customerName').focus();
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (!form.reportValidity()) return;

        readFormDetails();
        message.textContent = 'Saving delivery details…';
        try {
            const response = await fetch('/profile/delivery-details', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    name: details.name,
                    phone: details.phone,
                    street: details.street,
                    barangay: details.barangay,
                    city: details.city,
                    province: details.province,
                    postal_code: details.postal_code
                })
            });
            const result = await response.json();
            if (!response.ok) {
                const errors = Object.values(result.errors || {}).flat();
                message.textContent = errors.join(' ') || 'Could not save delivery details. Please try again.';
                return;
            }

            saveFormDetails();
            form.hidden = true;
            message.textContent = result.message;
        } catch (error) {
            message.textContent = 'Could not save delivery details. Check your connection and try again.';
        }
    });

    confirmButton.addEventListener('click', async () => {
        if (!items.length) return;
        if (!form.hidden) {
            form.requestSubmit();
            return;
        }
        if (!details.phone || !hasCompleteAddress(details)) {
            form.hidden = false;
            form.reportValidity();
            message.textContent = 'Please add your phone number and complete delivery address before confirming.';
            return;
        }

        const cart = JSON.parse(localStorage.getItem(cartKey) || '[]');
        confirmButton.disabled = true;
        message.textContent = 'Placing your order…';

        let response;
        let result;
        try {
            response = await fetch('/orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    items: items.map(item => ({
                        id: item.id,
                        quantity: item.quantity,
                        color: item.color || null
                    })),
                    customer: {
                        name: details.name,
                        phone: details.phone,
                        street: details.street,
                        barangay: details.barangay,
                        city: details.city,
                        province: details.province,
                        postal_code: details.postal_code
                    },
                    payment_method: details.paymentMethod
                })
            });
            result = await response.json();
        } catch (error) {
            confirmButton.disabled = false;
            message.textContent = 'Could not place the order. Check your connection and try again.';
            return;
        }

        if (!response.ok) {
            const errors = Object.values(result.errors || {}).flat();
            confirmButton.disabled = false;
            message.textContent = errors.join(' ') || 'Could not place the order. Please try again.';
            return;
        }

        if (checkoutSource === 'cart') {
            const remaining = [...cart];
            items.forEach(orderedItem => {
                const index = remaining.findIndex(item =>
                    item.id === orderedItem.id &&
                    item.color === orderedItem.color &&
                    item.quantity === orderedItem.quantity
                );
                if (index >= 0) remaining.splice(index, 1);
            });
            localStorage.setItem(cartKey, JSON.stringify(remaining));
        }

        sessionStorage.removeItem(checkoutItemsKey);
        sessionStorage.removeItem(checkoutSourceKey);
        items = [];
        renderItems();
        updateBadge();
        confirmButton.disabled = true;
        document.getElementById('successOrderId').textContent = result.order.number;
        successSplash.hidden = false;
        window.setTimeout(() => {
            window.location.href = '/orders';
        }, 2400);
    });

    showSavedDetails();
    updateAddressPreview();
    updateBadge();
    renderItems();
})();
