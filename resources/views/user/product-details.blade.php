<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $product->name }} | OversmokeVlounge</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/product-details.css') }}?v={{ filemtime(public_path('css/product-details.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/account-menu.css') }}?v={{ filemtime(public_path('css/account-menu.css')) }}">
</head>
<body data-user-id="{{ auth('web')->id() }}">
    <div class="smoke"></div>
    <header class="topbar site-topbar">
        <a class="logo" href="{{ url('/home') }}">
            <img src="{{ asset('images/oversmokev-mascot.png') }}" alt="">
            <span>OVERSMOKE</span>
        </a>
        <nav class="site-nav">
            <a href="{{ url('/home') }}">Home</a>
            <a href="{{ route('orders') }}">Orders</a>
        </nav>
        <div class="topbar-actions">
        <a class="cart-link" href="{{ route('cart') }}" aria-label="Cart">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
            <span class="cart-count" id="cartCount">0</span>
        </a>
        <x-user.account-menu />
        </div>
    </header>

    <main class="page">
        <a class="back-link" href="{{ url('/home') }}">← <span>Back to shop</span></a>
        <p class="eyebrow">PRODUCT DETAILS</p>
        @php
            $availableVariant = $product->variants->first(fn ($variant) => $variant->stock > 0) ?? $product->variants->first();
            $previewImagePath = $availableVariant?->image_path ?: $product->image_path;
            $initialOptionStock = $availableVariant?->stock ?? $product->stock;
        @endphp
        <section class="product-panel" id="product"
                 data-id="{{ $product->id }}"
                 data-name="{{ $product->name }}"
                 data-brand="{{ $product->brand }}"
                 data-price="{{ $product->price }}"
                 data-stock="{{ $product->stock }}"
                 data-option-label="{{ $product->optionLabel() ?? '' }}"
                 data-options="{{ $product->variants->map(fn ($variant) => ['name' => $variant->name, 'stock' => $variant->stock, 'image' => $variant->image_path ? asset('storage/' . $variant->image_path) : null])->values()->toJson() }}"
                 data-authenticated="{{ auth('web')->check() ? 'true' : 'false' }}"
                 data-image="{{ $product->image_path ? asset('storage/' . $product->image_path) : '' }}">
            <div class="product-visual" id="productImagePreview">
                @if ($previewImagePath)
                    <img src="{{ asset('storage/' . $previewImagePath) }}" alt="{{ $product->name }}">
                    <span class="image-preview-hint">Move your cursor to preview</span>
                @else
                    <div class="device-placeholder" aria-hidden="true"></div>
                @endif
            </div>

            <div class="product-info">
                @if ($product->stock === 0)
                    <span class="badge">Out of stock</span>
                @elseif ($product->stock <= 10)
                    <span class="badge">Low stock</span>
                @endif
                <p class="category">{{ $product->category }}</p>
                <h1>{{ $product->name }}</h1>
                <p class="brand">{{ $product->brand }}</p>
                <p class="price">₱ <span id="unitPrice">{{ number_format((float) $product->price) }}</span></p>
                <p class="description">{{ $product->description ?: 'A quality ' . strtolower($product->category) . ' product' . ($product->brand ? ' from ' . $product->brand : '') . '. Choose an option and quantity before adding it to your cart.' }}</p>

                @if ($product->variants->isNotEmpty())
                    <fieldset class="color-picker">
                        <legend>{{ $product->optionLabel() }} <span id="selectedOption">{{ $availableVariant?->name ?? $product->variants->first()->name }}</span></legend>
                        @if ($product->variants->count() > 8)
                            <label class="option-search-label" for="optionSearch">Search {{ strtolower($product->optionLabel()) }}s</label>
                            <input class="option-search" id="optionSearch" type="search" autocomplete="off" placeholder="Type to filter options">
                        @endif
                        <div class="color-options">
                            @foreach ($product->variants as $variant)
                                <button type="button" class="color-option{{ $variant->stock <= 0 ? ' is-out-of-stock' : '' }}{{ $availableVariant?->id === $variant->id ? ' selected' : '' }}" data-option="{{ $variant->name }}" data-stock="{{ $variant->stock }}" data-image="{{ $variant->image_path ? asset('storage/' . $variant->image_path) : '' }}" aria-pressed="{{ $availableVariant?->id === $variant->id ? 'true' : 'false' }}">
                                    @if ($variant->image_path)
                                        <img class="color-option-image" src="{{ asset('storage/' . $variant->image_path) }}" alt="">
                                    @endif
                                    <span>{{ $variant->name }}{{ $variant->stock <= 0 ? ' (Out of stock)' : '' }}</span>
                                </button>
                            @endforeach
                        </div>
                    </fieldset>
                @else
                    <fieldset class="color-picker" hidden></fieldset>
                    <span id="selectedOption" hidden></span>
                @endif
                <p class="option-stock{{ $initialOptionStock === 0 ? ' is-out' : '' }}" id="optionStock" role="status" aria-live="polite">
                    {{ $initialOptionStock > 0 ? number_format($initialOptionStock) . ' available' : 'Out of stock' }}
                </p>

                <div class="purchase-row">
                    <div class="quantity-control">
                        <span>Quantity</span>
                        <div class="quantity-input">
                            <button type="button" id="decreaseQuantity" aria-label="Decrease quantity">−</button>
                            <output id="quantity" aria-live="polite">1</output>
                            <button type="button" id="increaseQuantity" aria-label="Increase quantity">+</button>
                        </div>
                    </div>
                    <p class="subtotal">Subtotal <strong>₱ <span id="subtotal">{{ number_format((float) $product->price) }}</span></strong></p>
                </div>

                <div class="actions">
                    <button type="button" class="add-to-cart" id="addToCart" @disabled($initialOptionStock <= 0)>Add to Cart</button>
                    <button type="button" class="buy-now" id="buyNow" @disabled($initialOptionStock <= 0)>Buy Now</button>
                </div>
                <p class="status-message" id="statusMessage" role="status" aria-live="polite"></p>
            </div>
        </section>
    </main>
    <dialog class="confirm-dialog" id="buyNowDialog" aria-labelledby="confirmTitle" aria-describedby="confirmMessage">
        <div class="confirm-icon" aria-hidden="true">?</div>
        <p class="confirm-eyebrow">READY TO CHECK OUT?</p>
        <h2 id="confirmTitle">Confirm your purchase</h2>
        <p id="confirmMessage"></p>
        <div class="confirm-actions">
            <button type="button" class="confirm-cancel" id="cancelBuyNow">Cancel</button>
            <button type="button" class="confirm-proceed" id="confirmBuyNow">Continue to checkout</button>
        </div>
    </dialog>
    <dialog class="confirm-dialog auth-dialog" id="authDialog" aria-labelledby="authTitle" aria-describedby="authMessage">
        <div class="confirm-icon" aria-hidden="true">✓</div>
        <p class="confirm-eyebrow">ONE MORE STEP</p>
        <h2 id="authTitle">Sign in to continue</h2>
        <p id="authMessage">Log in or create an account to add this item to your cart.</p>
        <div class="confirm-actions">
            <a class="confirm-proceed" href="{{ url('/go/login') }}">Log in</a>
            <a class="auth-signup" href="{{ url('/go/register') }}">Sign up</a>
            <button type="button" class="confirm-cancel" id="closeAuthDialog">Not now</button>
        </div>
    </dialog>
    <div class="cart-success-toast" id="cartSuccessToast" role="status" aria-live="polite" aria-atomic="true" hidden>
        <span class="cart-success-icon" aria-hidden="true">✓</span>
        <span>Successfully added to cart.</span>
    </div>
    <script src="{{ asset('js/product-details.js') }}?v={{ filemtime(public_path('js/product-details.js')) }}"></script>
    <script src="{{ asset('js/account-menu.js') }}?v={{ filemtime(public_path('js/account-menu.js')) }}"></script>
</body>
</html>
