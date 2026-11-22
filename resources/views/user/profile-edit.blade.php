<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Profile | OversmokeVlounge</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/profile-edit.css') }}?v={{ filemtime(public_path('css/profile-edit.css')) }}">
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
        <h1>Edit profile</h1>
        <p class="intro">Update your account information below.</p>

        @if (session('status'))
            <p class="status" role="status">{{ session('status') }}</p>
        @endif

        <form class="profile-form" method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="name">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', auth()->user()->name) }}" autocomplete="name" required maxlength="255">
                @error('name')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" autocomplete="email" required maxlength="255">
                @error('email')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="phone">Phone number</label>
                <input id="phone" name="phone" type="tel" value="{{ preg_replace('/\D+/', '', (string) old('phone', auth()->user()->phone)) }}" autocomplete="tel" minlength="11" maxlength="11" pattern="09[0-9]{9}" inputmode="numeric" title="Enter an 11-digit Philippine mobile number starting with 09." placeholder="09XXXXXXXXX">
                @error('phone')<p class="error">{{ $message }}</p>@enderror
            </div>

            @if (! auth()->user()->street && auth()->user()->address)
                <p class="field-hint">Your currently saved address: {{ auth()->user()->address }}. Enter it in the fields below to save each part separately.</p>
            @endif

            <div class="field">
                <label for="street">Street address</label>
                <input id="street" name="street" type="text" value="{{ old('street', auth()->user()->street) }}" autocomplete="address-line1" required maxlength="200" placeholder="House / unit number and street">
                @error('street')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="barangay">Barangay</label>
                <input id="barangay" name="barangay" type="text" value="{{ old('barangay', auth()->user()->barangay) }}" autocomplete="address-line2" required maxlength="80">
                @error('barangay')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="city">City / municipality</label>
                <input id="city" name="city" type="text" value="{{ old('city', auth()->user()->city) }}" autocomplete="address-level2" required maxlength="80">
                @error('city')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="province">Province</label>
                <input id="province" name="province" type="text" value="{{ old('province', auth()->user()->province) }}" autocomplete="address-level1" required maxlength="80">
                @error('province')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="postal_code">Postal code</label>
                <input id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', auth()->user()->postal_code) }}" autocomplete="postal-code" required minlength="4" maxlength="4" pattern="[0-9]{4}" inputmode="numeric" title="Enter a 4-digit Philippine postal code.">
                @error('postal_code')<p class="error">{{ $message }}</p>@enderror
            </div>

            <hr>
            <p class="password-note">Leave password fields blank if you do not want to change your password.</p>

            <div class="field">
                <label for="current_password">Current password</label>
                <input id="current_password" name="current_password" type="password" autocomplete="current-password">
                @error('current_password')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="password">New password</label>
                <input id="password" name="password" type="password" autocomplete="new-password">
                @error('password')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
            </div>

            <div class="form-actions">
                <a class="cancel-button" href="{{ url('/home') }}">Cancel</a>
                <button type="submit">Save changes</button>
            </div>
        </form>
    </main>
    <script>
        const cartKey = `oversmoke-cart-user-${document.body.dataset.userId}`;
        document.getElementById('cartCount').textContent = JSON.parse(localStorage.getItem(cartKey) || '[]').length;
    </script>
    <script src="{{ asset('js/account-menu.js') }}?v={{ filemtime(public_path('js/account-menu.js')) }}"></script>
</body>
</html>
