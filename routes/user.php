<?php

use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\NotificationController;
use App\Http\Controllers\User\SupportChatController;
use App\Http\Controllers\User\OrderController;
use App\Http\Controllers\User\PasswordResetController;
use App\Models\Product;
use App\Support\DeliveryAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('home');
});

Route::view('/terms-and-conditions', 'user.legal', [
    'title' => 'Terms & Conditions',
    'updated' => 'November 9, 2026',
    'sections' => [
        [
            'heading' => 'Using this site',
            'paragraphs' => [
                'By creating an account or placing an order, you agree to these terms. You must provide accurate account and order details and keep your password confidential.',
                'You must meet the legal age requirement for the products in your location and follow all laws that apply to purchasing, possessing, and receiving them. You may not use this site for unlawful activity or attempt to interfere with its operation.',
            ],
        ],
        [
            'heading' => 'Accounts',
            'paragraphs' => [
                'You are responsible for activity carried out through your account. Contact the store through its official business channel if you believe your account has been accessed without permission.',
            ],
        ],
        [
            'heading' => 'Products and orders',
            'paragraphs' => [
                'Product listings, prices, and stock are subject to change. An order is subject to product availability and confirmation by the store. If an item is unavailable or an order cannot be fulfilled, the store will contact you using the details provided with your account or order.',
                'The checkout currently offers cash on delivery only; this site does not process online payments. Delivery arrangements and order updates use the contact and address details you provide.',
                'Signed-in customers may submit one damage-related refund request for each non-cancelled order, with a description and optional image or video evidence. Store staff review requests and decide the refund amount. Any refund payment is arranged by the store outside this website; the site only records the decision and manual completion.',
            ],
        ],
        [
            'heading' => 'Site availability and changes',
            'paragraphs' => [
                'The site and its content may be updated, suspended, or changed. The store may revise these terms by posting an updated version here. Continued use after an update means you accept the revised terms.',
            ],
        ],
        [
            'heading' => 'Contact',
            'paragraphs' => [
                'For questions about these terms or an order, contact OversmokeVlounge through its official business contact channel.',
            ],
        ],
    ],
])->name('terms');

Route::view('/privacy-policy', 'user.legal', [
    'title' => 'Privacy Policy',
    'updated' => 'November 9, 2026',
    'sections' => [
        [
            'heading' => 'Information we collect',
            'paragraphs' => [
                'When you use the store, we collect the information you provide, such as your name, email address, and password. If you save profile or checkout details or place an order, we also process the phone number, delivery address, items, and order details you provide. If you request a refund, we also process your damage description and any photos or videos you attach.',
                'Passwords are stored as hashes rather than readable text. The checkout currently accepts cash on delivery and does not collect online payment card details.',
            ],
        ],
        [
            'heading' => 'How we use information',
            'paragraphs' => [
                'We use account and order information to create and secure accounts, provide password-reset assistance, process and manage orders, arrange delivery, respond to customer requests, and maintain the store.',
            ],
        ],
        [
            'heading' => 'Sharing and storage',
            'paragraphs' => [
                'Information may be processed by service providers needed to host, secure, or operate the store, and may be disclosed when required by law. Refund evidence is stored privately and can be accessed by the customer who submitted it and authorized store staff reviewing the request.',
                'We retain information for as long as needed to provide the service, manage orders and refund requests, and meet applicable legal or operational requirements. Reasonable safeguards are used to protect information, but no online service can guarantee absolute security.',
            ],
        ],
        [
            'heading' => 'Your choices and requests',
            'paragraphs' => [
                'You can update profile information in your account. For questions or requests about your personal information, contact OversmokeVlounge through its official business contact channel. Requests are handled subject to applicable law and records that must be retained.',
            ],
        ],
        [
            'heading' => 'Age and policy updates',
            'paragraphs' => [
                'The store is intended for people who meet the legal age requirement for the products in their location. We may update this policy by posting a revised version here; the date above shows when it was last updated.',
            ],
        ],
        [
            'heading' => 'Contact',
            'paragraphs' => [
                'For privacy questions, contact OversmokeVlounge through its official business contact channel.',
            ],
        ],
    ],
])->name('privacy');

Route::get('/go/{page}', function ($page) {
    abort_unless(in_array($page, ['login', 'register']), 404);

    return view('user.splash', ['next' => url('/'.$page), 'quick' => true]);
})->middleware('guest');

Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => view('user.login'))->name('login');
    Route::get('/register', fn () => view('user.register'))->name('register');

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/verify-reset-code', [PasswordResetController::class, 'showCodeForm'])->name('password.code');
    Route::post('/verify-reset-code', [PasswordResetController::class, 'verifyCode'])->middleware('throttle:10,1')->name('password.code.verify');
    Route::get('/reset-password', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.store');
});

Route::get('/welcome', function () {
    if (! session()->pull('show_splash')) {
        return redirect('/home');
    }

    return view('user.splash', ['next' => url('/home')]);
})->middleware(['auth:web', 'customer']);

Route::get('/home', function () {
    return view('user.home', [
        'products' => Product::query()->where('is_active', true)->orderByDesc('is_featured')->latest()->get(),
        'featuredProducts' => Product::query()->where('is_active', true)->where('is_featured', true)->latest()->get(),
        'categories' => Product::query()->where('is_active', true)->whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category'),
        'brands' => Product::query()->where('is_active', true)->whereNotNull('brand')->where('brand', '!=', '')->distinct()->orderBy('brand')->pluck('brand'),
        'maximumPrice' => max(5000, (float) Product::query()->where('is_active', true)->max('price')),
    ]);
})->name('home');

Route::get('/products/{id}', function (int $id) {
    $product = Product::query()->with('variants')->where('is_active', true)->findOrFail($id);

    return view('user.product-details', compact('product'));
})->whereNumber('id')->name('products.show');

Route::view('/cart', 'user.cart')->middleware(['auth:web', 'customer'])->name('cart');
Route::view('/checkout', 'user.checkout')->middleware(['auth:web', 'customer'])->name('checkout');
Route::middleware(['auth:web', 'customer'])->group(function () {
    Route::get('/support-chat', [SupportChatController::class, 'index'])->name('support-chat.index');
    Route::get('/support-chat/unread-count', [SupportChatController::class, 'unreadCount'])->name('support-chat.unread-count');
    Route::post('/support-chat/messages', [SupportChatController::class, 'store'])->middleware('throttle:30,1')->name('support-chat.messages.store');
    Route::delete('/support-chat/messages/{message}', [SupportChatController::class, 'destroyMessage'])->name('support-chat.messages.destroy');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('user.notifications.index');
    Route::patch('/notifications/settings', [NotificationController::class, 'updateSettings'])->name('user.notifications.settings');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('user.notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('user.notifications.read');
    Route::get('/orders/updates', [OrderController::class, 'updates'])->name('orders.updates');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->whereNumber('order')->name('orders.cancel');
    Route::post('/orders/{order}/refund-requests', [\App\Http\Controllers\User\RefundRequestController::class, 'store'])->whereNumber('order')->name('orders.refunds.store');
    Route::get('/refund-evidence/{evidence}/download', [\App\Http\Controllers\User\RefundRequestController::class, 'downloadEvidence'])->whereNumber('evidence')->name('refund-evidence.download');
});

Route::middleware(['auth:web', 'customer'])->group(function () {
    Route::get('/profile/edit', fn () => view('user.profile-edit'))->name('profile.edit');
    Route::put('/profile', function (Request $request) {
        $user = $request->user('web');
        $phone = $request->input('phone');
        if (is_scalar($phone) && filled($phone)) {
            $request->merge(['phone' => preg_replace('/\D+/', '', (string) $phone)]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'regex:/^09[0-9]{9}$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'street' => ['required_without:address', 'nullable', 'string', 'max:200'],
            'barangay' => ['required_without:address', 'nullable', 'string', 'max:80'],
            'city' => ['required_without:address', 'nullable', 'string', 'max:80'],
            'province' => ['required_without:address', 'nullable', 'string', 'max:80'],
            'postal_code' => ['required_without:address', 'nullable', 'string', 'regex:/^[0-9]{4}$/'],
            'current_password' => ['required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        if (DeliveryAddress::hasComponents($data)) {
            $user->address = DeliveryAddress::format($data);
            $user->street = $data['street'];
            $user->barangay = $data['barangay'];
            $user->city = $data['city'];
            $user->province = $data['province'];
            $user->postal_code = $data['postal_code'];
        } elseif (array_key_exists('address', $data)) {
            $user->address = $data['address'];
            $user->street = null;
            $user->barangay = null;
            $user->city = null;
            $user->province = null;
            $user->postal_code = null;
        }
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return redirect()->route('profile.edit')->with('status', 'Profile updated successfully.');
    })->name('profile.update');

    Route::put('/profile/delivery-details', function (Request $request) {
        $phone = $request->input('phone');
        if (is_scalar($phone) && filled($phone)) {
            $request->merge(['phone' => preg_replace('/\D+/', '', (string) $phone)]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'street' => ['required_without:address', 'nullable', 'string', 'max:200'],
            'barangay' => ['required_without:address', 'nullable', 'string', 'max:80'],
            'city' => ['required_without:address', 'nullable', 'string', 'max:80'],
            'province' => ['required_without:address', 'nullable', 'string', 'max:80'],
            'postal_code' => ['required_without:address', 'nullable', 'string', 'regex:/^[0-9]{4}$/'],
        ]);

        $user = $request->user('web');
        if (DeliveryAddress::hasComponents($data)) {
            $user->update([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'address' => DeliveryAddress::format($data),
                'street' => $data['street'],
                'barangay' => $data['barangay'],
                'city' => $data['city'],
                'province' => $data['province'],
                'postal_code' => $data['postal_code'],
            ]);
        } else {
            $user->update($data);
            $user->update([
                'street' => null,
                'barangay' => null,
                'city' => null,
                'province' => null,
                'postal_code' => null,
            ]);
        }

        return response()->json(['message' => 'Delivery details saved to your profile.']);
    })->name('profile.delivery.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth:web', 'customer'])->name('logout');
