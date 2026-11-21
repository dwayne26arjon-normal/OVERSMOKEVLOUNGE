<?php

namespace Tests\Feature\Admin;

use App\Mail\OrderInvoiceMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewOrderReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_order_list_details_and_printable_invoice(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Customer Example')
            ->assertSee('Download CSV')
            ->assertSee(route('admin.orders.sales.export', ['range' => 'day']), false)
            ->assertSee(route('admin.orders.sales.export', ['range' => 'month']), false)
            ->assertSee(route('admin.orders.sales.export', ['range' => 'year']), false);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Customer information')
            ->assertSee('Ordered items')
            ->assertSee('statusConfirmDialog', false)
            ->assertSee('Update order status?')
            ->assertSee('Cloud Berry')
            ->assertSee('₱900.00')
            ->assertSee(route('admin.orders.invoice', $order), false)
            ->assertSee(route('admin.orders.invoice.email', $order), false);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.invoice', $order))
            ->assertOk()
            ->assertSee('Invoice '.$order->order_number)
            ->assertSee('Cloud Berry');
    }

    public function test_admin_order_notifications_are_persistent_and_can_be_marked_read(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder();
        $admin->notify(new NewOrderReceived($order));
        $notification = $admin->notifications()->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->getJson(route('admin.orders.notifications'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.order_number', $order->order_number)
            ->assertJsonPath('notifications.0.buyer', $order->customer_name);

        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.orders.notifications.read', $notification->id))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->actingAs($admin, 'admin')
            ->getJson(route('admin.orders.notifications'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0)
            ->assertJsonPath('notifications.0.read_at', fn ($value) => $value !== null);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('id="notificationButton"', false)
            ->assertSee('Order notifications')
            ->assertSee('Mark all read')
            ->assertSee('admin-notifications.js');
    }

    public function test_notification_order_links_use_https_behind_a_trusted_proxy(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder();
        $admin->notify(new NewOrderReceived($order));

        $this->actingAs($admin, 'admin')
            ->withHeader('X-Forwarded-Proto', 'https')
            ->getJson('/admin/orders/notifications')
            ->assertOk()
            ->assertJsonPath('notifications.0.url', fn (string $url): bool => str_starts_with($url, 'https://'));
    }

    public function test_admin_can_email_invoice_to_customer(): void
    {
        config(['mail.default' => 'smtp']);
        Mail::fake();
        $admin = $this->createAdmin();
        $order = $this->createOrder();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.orders.invoice.email', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('status', 'Invoice emailed to the customer.');

        Mail::assertSent(OrderInvoiceMail::class, function (OrderInvoiceMail $mail) use ($order): bool {
            return $mail->hasTo($order->customer_email)
                && $mail->order->is($order);
        });
    }

    public function test_invoice_is_not_reported_as_sent_when_mailer_only_logs_messages(): void
    {
        config(['mail.default' => 'log']);
        Mail::fake();
        $admin = $this->createAdmin();
        $order = $this->createOrder();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.invoice.email', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors([
                'invoice' => 'Email delivery is not configured on this server. Configure SMTP in the hosting environment and try again.',
            ]);

        Mail::assertNothingSent();
    }

    public function test_admin_can_download_sales_csv_for_today(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.sales.export', ['range' => 'day']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition');
    }

    public function test_cancelling_order_restores_stock_and_reopening_reserves_it_again(): void
    {
        $admin = $this->createAdmin();
        $product = Product::create([
            'name' => 'Cloud Berry',
            'category' => 'E-liquids',
            'price' => 450,
            'stock' => 8,
            'is_active' => true,
        ]);
        $order = $this->createOrder($product);
        $this->assertSame(8, $product->fresh()->stock);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.orders.status', $order), ['status' => 'cancelled'])
            ->assertRedirect(route('admin.orders.show', $order));
        $this->assertSame(10, $product->fresh()->stock);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.orders.status', $order), ['status' => 'processing'])
            ->assertRedirect(route('admin.orders.show', $order));
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_cancelling_variant_order_restores_and_reopen_reserves_variant_stock(): void
    {
        $admin = $this->createAdmin();
        $product = Product::create([
            'name' => 'Berry Juice',
            'category' => 'E-liquids',
            'price' => 450,
            'stock' => 8,
            'is_active' => true,
        ]);
        $variant = $product->variants()->create(['name' => 'Strawberry Ice', 'stock' => 8]);
        $order = $this->createOrder($product);
        $order->items()->update(['color' => 'Strawberry Ice']);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.orders.status', $order), ['status' => 'cancelled'])
            ->assertRedirect(route('admin.orders.show', $order));
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame(10, $variant->fresh()->stock);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.orders.status', $order), ['status' => 'processing'])
            ->assertRedirect(route('admin.orders.show', $order));
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame(8, $variant->fresh()->stock);
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        return $admin;
    }

    private function createOrder(?Product $product = null): Order
    {
        $customer = User::factory()->create();
        $order = Order::create([
            'user_id' => $customer->id,
            'customer_name' => 'Customer Example',
            'customer_email' => $customer->email,
            'customer_phone' => '09123456789',
            'delivery_address' => '123 Example Street, Quezon City',
            'payment_method' => 'Cash on delivery',
            'status' => 'processing',
            'subtotal' => 900,
            'shipping_fee' => 0,
            'total' => 900,
        ]);
        $product ??= Product::create([
            'name' => 'Cloud Berry',
            'category' => 'E-liquids',
            'price' => 450,
            'stock' => 10,
            'is_active' => true,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'brand' => 'Vape Co',
            'category' => $product->category,
            'quantity' => 2,
            'unit_price' => 450,
        ]);

        return $order;
    }
}
