<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminPasswordResetController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\RefundRequestController;
use App\Http\Controllers\Admin\AdminStaffController;
use App\Http\Controllers\Admin\SupportChatController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AdminAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AdminAuthController::class, 'register'])->name('register.store');
    Route::get('/forgot-password', [AdminPasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [AdminPasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/verify-reset-code', [AdminPasswordResetController::class, 'showCodeForm'])->name('password.code');
    Route::post('/verify-reset-code', [AdminPasswordResetController::class, 'verifyCode'])->middleware('throttle:10,1')->name('password.code.verify');
    Route::get('/reset-password', [AdminPasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AdminPasswordResetController::class, 'reset'])->name('password.store');

    Route::middleware('admin')->group(function () {
        Route::get('/dashboard', [AdminProductController::class, 'dashboard'])->name('dashboard');
        Route::get('/support-chat', [SupportChatController::class, 'index'])->name('support-chat.index');
        Route::get('/support-chat/unread-count', [SupportChatController::class, 'unreadCount'])->name('support-chat.unread-count');
        Route::get('/support-chat/conversations', [SupportChatController::class, 'conversations'])->name('support-chat.conversations');
        Route::get('/support-chat/conversations/{conversation}', [SupportChatController::class, 'messages'])->name('support-chat.messages');
        Route::post('/support-chat/conversations/{conversation}/messages', [SupportChatController::class, 'store'])->middleware('throttle:30,1')->name('support-chat.messages.store');
        Route::delete('/support-chat/conversations/{conversation}', [SupportChatController::class, 'destroyConversation'])->name('support-chat.conversations.destroy');
        Route::get('/orders/notifications', [AdminOrderController::class, 'notifications'])->name('orders.notifications');
        Route::patch('/orders/notifications/read-all', [AdminOrderController::class, 'markAllNotificationsRead'])->name('orders.notifications.read-all');
        Route::patch('/orders/notifications/{notification}/read', [AdminOrderController::class, 'markNotificationRead'])->name('orders.notifications.read');
        Route::get('/orders/export/sales', [AdminOrderController::class, 'exportSalesCsv'])->name('orders.sales.export');
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::put('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
        Route::get('/orders/{order}/invoice', [AdminOrderController::class, 'printInvoice'])->name('orders.invoice');
        Route::post('/orders/{order}/invoice/email', [AdminOrderController::class, 'emailInvoice'])->name('orders.invoice.email');
        Route::get('/refund-requests', [RefundRequestController::class, 'index'])->name('refunds.index');
        Route::get('/refund-requests/{refundRequest}', [RefundRequestController::class, 'show'])->name('refunds.show');
        Route::patch('/refund-requests/{refundRequest}', [RefundRequestController::class, 'update'])->name('refunds.update');
        Route::get('/refund-evidence/{evidence}/download', [RefundRequestController::class, 'downloadEvidence'])->whereNumber('evidence')->name('refund-evidence.download');
        Route::get('/refund-evidence/{evidence}/preview', [RefundRequestController::class, 'previewEvidence'])->whereNumber('evidence')->name('refund-evidence.preview');
        Route::get('/products', [AdminProductController::class, 'products'])->name('products.index');
        Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
        Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
        Route::get('/inventory', [AdminProductController::class, 'inventory'])->name('inventory.index');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::middleware('admin.owner')->group(function () {
            Route::get('/staff', [AdminStaffController::class, 'index'])->name('staff.index');
            Route::post('/staff', [AdminStaffController::class, 'store'])->name('staff.store');
            Route::post('/staff/admin', [AdminStaffController::class, 'storeAdmin'])->name('staff.admin.store');
            Route::delete('/staff/{staff}', [AdminStaffController::class, 'destroy'])->name('staff.destroy');
        });
    });
});
