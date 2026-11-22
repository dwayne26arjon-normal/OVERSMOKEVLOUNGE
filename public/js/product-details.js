(function () {
    const product = document.getElementById('product');
    const quantityOutput = document.getElementById('quantity');
    const subtotal = document.getElementById('subtotal');
    const optionStock = document.getElementById('optionStock');
    const addToCartButton = document.getElementById('addToCart');
    const buyNowButton = document.getElementById('buyNow');
    const cartSuccessToast = document.getElementById('cartSuccessToast');
    const buyNowDialog = document.getElementById('buyNowDialog');
    const authDialog = document.getElementById('authDialog');
    const userId = document.body.dataset.userId;
    const cartKey = `oversmoke-cart-user-${userId}`;
    const checkoutItemsKey = `oversmoke-checkout-items-user-${userId}`;
    const checkoutSourceKey = `oversmoke-checkout-source-user-${userId}`;
    const unitPrice = Number(product.dataset.price);
    const options = JSON.parse(product.dataset.options || '[]');
    let quantity = Number(quantityOutput.textContent);
    let selectedOption = document.querySelector('.color-option.selected');
    let cartSuccessTimer;

    function formatPrice(price) {
        return new Intl.NumberFormat('en-PH').format(price);
    }

    function syncCartCount() {
        const cart = JSON.parse(localStorage.getItem(cartKey) || '[]');
        document.getElementById('cartCount').textContent = cart.length;
    }

    function showCartSuccessToast() {
        cartSuccessToast.hidden = false;
        window.clearTimeout(cartSuccessTimer);
        cartSuccessTimer = window.setTimeout(() => {
            cartSuccessToast.hidden = true;
        }, 3500);
    }

    function animateCartFly(imageUrl) {
        const cartIcon = document.querySelector('.cart-link svg');
        if (!cartIcon) return;

        const sourceElement = document.querySelector('#productImagePreview img') || document.querySelector('#productImagePreview .device-placeholder');
        const source = (sourceElement || document.getElementById('addToCart')).getBoundingClientRect();
        const target = cartIcon.getBoundingClientRect();
        const flyer = document.createElement(imageUrl ? 'img' : 'span');
        flyer.className = 'cart-flyer';
        flyer.setAttribute('aria-hidden', 'true');
        if (imageUrl) {
            flyer.src = imageUrl;
            flyer.alt = '';
        } else {
            flyer.classList.add('cart-flyer-placeholder');
        }
        const startX = source.left + source.width / 2;
        const startY = source.top + source.height / 2;
        const endX = target.left + target.width / 2;
        const endY = target.top + target.height / 2;
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const duration = reducedMotion ? 300 : 850;
        const startTime = performance.now();
        flyer.style.left = `${startX}px`;
        flyer.style.top = `${startY}px`;
        document.body.append(flyer);

        function animateFrame(timestamp) {
            const progress = Math.min((timestamp - startTime) / duration, 1);
            const eased = 1 - (1 - progress) ** 3;
            const arc = reducedMotion ? 0 : Math.sin(progress * Math.PI) * 70;
            flyer.style.left = `${startX + (endX - startX) * eased}px`;
            flyer.style.top = `${startY + (endY - startY) * eased - arc}px`;
            flyer.style.transform = `translate(-50%, -50%) scale(${1 - eased * .72})`;
            flyer.style.opacity = String(1 - eased * .25);

            if (progress < 1) {
                window.requestAnimationFrame(animateFrame);
                return;
            }

            flyer.remove();
            cartIcon.classList.add('cart-arrived');
            window.setTimeout(() => cartIcon.classList.remove('cart-arrived'), 450);
        }

        window.requestAnimationFrame(animateFrame);
    }

    const imagePreview = document.getElementById('productImagePreview');
    let previewImage = imagePreview.querySelector('img');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const supportsHover = window.matchMedia('(hover: hover) and (pointer: fine)');

    function showProductImage(imageUrl) {
        if (imageUrl) {
            if (!previewImage) {
                previewImage = document.createElement('img');
                previewImage.alt = product.dataset.name;
                imagePreview.querySelector('.device-placeholder')?.remove();
                imagePreview.prepend(previewImage);
            }
            previewImage.src = imageUrl;
            return;
        }

        if (previewImage) {
            previewImage.remove();
            previewImage = null;
        }
        if (!imagePreview.querySelector('.device-placeholder')) {
            const placeholder = document.createElement('div');
            placeholder.className = 'device-placeholder';
            placeholder.setAttribute('aria-hidden', 'true');
            imagePreview.prepend(placeholder);
        }
    }

    if (supportsHover.matches && ! reducedMotion.matches) {
        function updatePreview(event) {
            if (!previewImage) return;
            const bounds = imagePreview.getBoundingClientRect();
            const x = (event.clientX - bounds.left) / bounds.width;
            const y = (event.clientY - bounds.top) / bounds.height;
            const rotateX = (0.5 - y) * 32;
            const rotateY = (x - 0.5) * 40;

            imagePreview.style.setProperty('--tilt-x', `${rotateX.toFixed(2)}deg`);
            imagePreview.style.setProperty('--tilt-y', `${rotateY.toFixed(2)}deg`);
            imagePreview.style.setProperty('--pointer-x', `${(x * 100).toFixed(2)}%`);
            imagePreview.style.setProperty('--pointer-y', `${(y * 100).toFixed(2)}%`);
            imagePreview.classList.add('is-interactive');
        }

        imagePreview.addEventListener('pointerenter', updatePreview);
        imagePreview.addEventListener('pointermove', updatePreview);

        imagePreview.addEventListener('pointerleave', () => {
            imagePreview.style.setProperty('--tilt-x', '0deg');
            imagePreview.style.setProperty('--tilt-y', '0deg');
            imagePreview.style.setProperty('--pointer-x', '50%');
            imagePreview.style.setProperty('--pointer-y', '50%');
            imagePreview.classList.remove('is-interactive');
        });
    }

    syncCartCount();
    window.addEventListener('pageshow', syncCartCount);
    window.addEventListener('storage', event => {
        if (event.key === cartKey) syncCartCount();
    });

    function updateQuantity(nextQuantity) {
        const stock = selectedOption
            ? Number(selectedOption.dataset.stock)
            : Number(product.dataset.stock);
        quantity = Math.min(Math.max(1, nextQuantity), Math.max(1, stock));
        quantityOutput.textContent = quantity;
        subtotal.textContent = formatPrice(unitPrice * quantity);
    }

    function updateOptionStock() {
        const stock = selectedOption
            ? Number(selectedOption.dataset.stock)
            : Number(product.dataset.stock);
        optionStock.textContent = stock > 0 ? `${formatPrice(stock)} available` : 'Out of stock';
        optionStock.classList.toggle('is-out', stock <= 0);
        addToCartButton.disabled = stock <= 0;
        buyNowButton.disabled = stock <= 0;
    }

    function productImage() {
        const imageUrl = selectedOption?.dataset.image || product.dataset.image;
        if (!imageUrl) return null;

        const parsedUrl = new URL(imageUrl, window.location.origin);
        return parsedUrl.origin === window.location.origin && parsedUrl.pathname.startsWith('/storage/')
            ? parsedUrl.href
            : null;
    }

    document.getElementById('decreaseQuantity').addEventListener('click', () => updateQuantity(quantity - 1));
    document.getElementById('increaseQuantity').addEventListener('click', () => updateQuantity(quantity + 1));

    document.querySelectorAll('.color-option').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.color-option').forEach(option => {
                option.classList.remove('selected');
                option.setAttribute('aria-pressed', 'false');
            });
            button.classList.add('selected');
            button.setAttribute('aria-pressed', 'true');
            selectedOption = button;
            document.getElementById('selectedOption').textContent = button.dataset.option;
            showProductImage(button.dataset.image || product.dataset.image);
            updateQuantity(quantity);
            updateOptionStock();
        });
    });

    document.getElementById('optionSearch')?.addEventListener('input', event => {
        const searchTerm = event.target.value.trim().toLowerCase();
        document.querySelectorAll('.color-option').forEach(button => {
            button.hidden = !button.dataset.option.toLowerCase().includes(searchTerm);
        });
    });

    function addToCart() {
        if (product.dataset.authenticated !== 'true') {
            document.getElementById('authTitle').textContent = 'Sign in to add to cart';
            document.getElementById('authMessage').textContent =
                'Log in or create an account to add this item to your cart.';
            authDialog.showModal();
            return;
        }

        const availableStock = selectedOption
            ? Number(selectedOption.dataset.stock)
            : Number(product.dataset.stock);
        if ((options.length && !selectedOption) || availableStock < quantity) {
            document.getElementById('statusMessage').textContent = 'Choose an available option and quantity.';
            return;
        }

        const cart = JSON.parse(localStorage.getItem(cartKey) || '[]');
        const option = selectedOption?.dataset.option || null;
        const image = productImage();
        const matchingItem = cart.find(item => item.id === Number(product.dataset.id) && item.color === option);

        if (matchingItem && matchingItem.quantity + quantity > availableStock) {
            document.getElementById('statusMessage').textContent = `Only ${availableStock} available for this option.`;
            return;
        }

        if (matchingItem) {
            matchingItem.quantity += quantity;
            matchingItem.image = image;
        } else {
            cart.push({
                id: Number(product.dataset.id),
                name: product.dataset.name,
                brand: product.dataset.brand,
                price: unitPrice,
                quantity,
                color: option,
                image
            });
        }

        localStorage.setItem(cartKey, JSON.stringify(cart));
        syncCartCount();
        animateCartFly(image);
        document.getElementById('statusMessage').textContent = '';
        showCartSuccessToast();
    }

    document.getElementById('addToCart').addEventListener('click', addToCart);
    document.getElementById('buyNow').addEventListener('click', () => {
        if (product.dataset.authenticated !== 'true') {
            document.getElementById('authTitle').textContent = 'Sign in to purchase';
            document.getElementById('authMessage').textContent =
                'Log in or create an account before purchasing this item.';
            authDialog.showModal();
            return;
        }

        if ((options.length && !selectedOption) ||
            (selectedOption && Number(selectedOption.dataset.stock) < quantity) ||
            (!options.length && Number(product.dataset.stock) < quantity)) {
            document.getElementById('statusMessage').textContent = 'There is not enough stock for that quantity.';
            return;
        }

        const option = selectedOption?.dataset.option;
        document.getElementById('confirmMessage').textContent =
            `Proceed to checkout with ${quantity} × ${product.dataset.name}${option ? ` in ${option}` : ''}?`;
        buyNowDialog.showModal();
    });

    document.getElementById('closeAuthDialog').addEventListener('click', () => authDialog.close());
    document.getElementById('cancelBuyNow').addEventListener('click', () => buyNowDialog.close());
    document.getElementById('confirmBuyNow').addEventListener('click', () => {
        const item = {
            id: Number(product.dataset.id),
            name: product.dataset.name,
            brand: product.dataset.brand,
            price: unitPrice,
            quantity,
            color: selectedOption?.dataset.option || null,
            image: productImage()
        };

        sessionStorage.setItem(checkoutItemsKey, JSON.stringify([item]));
        sessionStorage.setItem(checkoutSourceKey, 'buy-now');
        window.location.href = '/checkout';
    });

    updateOptionStock();
})();
