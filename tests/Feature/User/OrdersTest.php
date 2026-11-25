<?php

namespace Tests\Feature\User;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_place_an_order_using_database_prices_and_stock(): void
    {
        $customer = User::factory()->create(['email' => 'customer@example.com']);
        $customer->refresh();
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();
        $staff = User::factory()->create();
        $staff->is_staff = true;
        $staff->save();
        $product = Product::create([
            'name' => 'Cloud Berry',
            'brand' => 'Vape Co',
            'category' => 'E-liquids',
            'price' => 450,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($customer)
            ->postJson(route('orders.store'), [
                'items' => [
                    ['id' => $product->id, 'quantity' => 1, 'color' => 'Violet', 'price' => 1],
                    ['id' => $product->id, 'quantity' => 1, 'color' => 'Blue', 'price' => 1],
                ],
                'customer' => [
                    'name' => 'Delivery Customer',
                    'phone' => '0912 345 6789',
                    'street' => '123 Example Street',
                    'barangay' => 'San Isidro',
                    'city' => 'Makati',
                    'province' => 'Metro Manila',
                    'postal_code' => '1234',
                ],
                'payment_method' => 'Cash on delivery',
            ])
            ->assertCreated()
            ->assertJsonPath('order.number', 'OS-000001')
            ->assertJsonPath('order.total', 900);

        $order = Order::with('items')->firstOrFail();
        $this->assertSame($customer->id, $order->user_id);
        $this->assertSame('customer@example.com', $order->customer_email);
        $this->assertSame('09123456789', $order->customer_phone);
        $this->assertSame('Cloud Berry', $order->items->first()->product_name);
        $this->assertSame('450.00', $order->items->first()->unit_price);
        $this->assertSame('123 Example Street, San Isidro, Makati, Metro Manila, 1234', $order->delivery_address);
        $this->assertCount(2, $order->items);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame('0.00', $order->shipping_fee);
        $this->assertDatabaseCount('notifications', 3);

        $this->actingAs($customer, 'web')
            ->getJson(route('user.notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.order_number', 'OS-000001')
            ->assertJsonPath('notifications.0.message', 'We received your order and are processing it.');

        $adminNotification = $admin->notifications()->firstOrFail();
        $this->actingAs($admin, 'admin')
            ->getJson(route('admin.orders.notifications'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.order_number', 'OS-000001')
            ->assertJsonPath('notifications.0.items', 2);

        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.orders.notifications.read', $adminNotification->id))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->actingAs($staff, 'admin')
            ->getJson(route('admin.orders.notifications'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.order_number', 'OS-000001');
    }

    public function test_customer_order_is_rejected_if_inventory_is_insufficient(): void
    {
        $customer = User::factory()->create();
        $product = Product::create([
            'name' => 'Limited Pod',
            'category' => 'Devices',
            'price' => 1200,
            'stock' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($customer)
            ->postJson(route('orders.store'), [
                'items' => [['id' => $product->id, 'quantity' => 2]],
                'customer' => [
                    'name' => 'Delivery Customer',
                    'phone' => '09123456789',
                    'address' => '123 Example Street',
                ],
                'payment_method' => 'Cash on delivery',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_order_submission_rejects_invalid_phone_and_postal_code(): void
    {
        $customer = User::factory()->create();
        $product = Product::create([
            'name' => 'Test Pod',
            'category' => 'Devices',
            'price' => 1200,
            'stock' => 5,
            'is_active' => true,
        ]);
        $payload = [
            'items' => [['id' => $product->id, 'quantity' => 1]],
            'customer' => [
                'name' => 'Delivery Customer',
                'phone' => '0912345678',
                'street' => '123 Example Street',
                'barangay' => 'San Isidro',
                'city' => 'Makati',
                'province' => 'Metro Manila',
                'postal_code' => '1234',
            ],
            'payment_method' => 'Cash on delivery',
        ];

        $this->actingAs($customer)
            ->postJson(route('orders.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('customer.phone');

        $payload['customer']['phone'] = '09123456789';
        $payload['customer']['postal_code'] = '123';

        $this->actingAs($customer)
            ->postJson(route('orders.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('customer.postal_code');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_customer_order_reserves_the_selected_variant_stock(): void
    {
        $customer = User::factory()->create();
        $product = Product::create([
            'name' => 'Berry Juice',
            'category' => 'Juices',
            'price' => 450,
            'stock' => 7,
            'is_active' => true,
        ]);
        $berry = $product->variants()->create([
            'name' => 'Berry Ice',
            'stock' => 5,
            'image_path' => 'products/variants/berry-ice.png',
        ]);
        $mint = $product->variants()->create(['name' => 'Cool Mint', 'stock' => 2]);

        $payload = [
            'items' => [['id' => $product->id, 'quantity' => 3, 'color' => 'Berry Ice']],
            'customer' => [
                'name' => 'Delivery Customer',
                'phone' => '09123456789',
                'address' => '123 Example Street',
            ],
            'payment_method' => 'Cash on delivery',
        ];

        $this->actingAs($customer)
            ->postJson(route('orders.store'), $payload)
            ->assertCreated();

        $this->assertSame(2, $berry->fresh()->stock);
        $this->assertSame(2, $mint->fresh()->stock);
        $this->assertSame(4, $product->fresh()->stock);
        $this->assertSame('Berry Ice', Order::firstOrFail()->items->first()->color);
        $this->assertSame('products/variants/berry-ice.png', Order::firstOrFail()->items->first()->image_path);

        $payload['items'][0]['quantity'] = 3;
        $this->actingAs($customer)
            ->postJson(route('orders.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertSame(2, $berry->fresh()->stock);
    }

    public function test_customer_orders_page_shows_only_that_customers_database_orders(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $attributes = [
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '09123456789',
            'delivery_address' => '123 Example Street',
            'payment_method' => 'Cash on delivery',
            'subtotal' => 450,
            'shipping_fee' => 0,
            'total' => 450,
        ];
        $ownOrder = Order::create($attributes + ['user_id' => $customer->id]);
        Order::create($attributes + ['user_id' => $otherCustomer->id]);

        $this->actingAs($customer)
            ->get(route('orders'))
            ->assertOk()
            ->assertSee($ownOrder->order_number)
            ->assertDontSee('OS-000002')
            ->assertSee('id="userLogoutConfirmDialog"', false)
            ->assertSee('Log out of your account?')
            ->assertSee('Stay signed in')
            ->assertSee('id="orderCancelDialog"', false)
            ->assertSee('Cancel this order?')
            ->assertSee('Keep order')
            ->assertSee('Yes, cancel order')
            ->assertDontSee('onsubmit="return confirm(', false)
            ->assertDontSee('window.confirm', false)
            ->assertSee('Saved to your account');
    }

    public function test_customer_orders_page_groups_orders_by_status_and_counts_only_their_orders(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $attributes = [
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '09123456789',
            'delivery_address' => '123 Example Street',
            'payment_method' => 'Cash on delivery',
            'subtotal' => 450,
            'shipping_fee' => 0,
            'total' => 450,
        ];

        foreach (['processing', 'shipped', 'completed', 'cancelled'] as $status) {
            Order::create($attributes + ['user_id' => $customer->id, 'status' => $status]);
        }
        Order::create($attributes + ['user_id' => $otherCustomer->id, 'status' => 'processing']);

        $this->actingAs($customer)
            ->get(route('orders'))
            ->assertOk()
            ->assertSee('To Ship')
            ->assertSee('To Receive')
            ->assertSee('Completed')
            ->assertSee('Cancelled')
            ->assertSee('data-order-filter-count="all">4</span>', false)
            ->assertSee('data-order-filter-count="processing">1</span>', false)
            ->assertSee('data-order-filter-count="shipped">1</span>', false)
            ->assertSee('data-order-filter-count="completed">1</span>', false)
            ->assertSee('data-order-filter-count="cancelled">1</span>', false);
    }

    public function test_customer_can_cancel_a_processing_order_and_restore_inventory(): void
    {
        $customer = User::factory()->create();
        $product = Product::create([
            'name' => 'Cloud Berry',
            'category' => 'E-liquids',
            'price' => 450,
            'stock' => 8,
            'is_active' => true,
        ]);
        $variant = $product->variants()->create(['name' => 'Berry Ice', 'stock' => 3]);
        $order = Order::create([
            'user_id' => $customer->id,
            'customer_name' => 'Test Customer',
            'customer_email' => $customer->email,
            'customer_phone' => '09123456789',
            'delivery_address' => '123 Example Street',
            'payment_method' => 'Cash on delivery',
            'status' => 'processing',
            'subtotal' => 900,
            'shipping_fee' => 0,
            'total' => 900,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'category' => $product->category,
            'color' => $variant->name,
            'quantity' => 2,
            'unit_price' => 450,
        ]);

        $this->actingAs($customer)
            ->post(route('orders.cancel', $order))
            ->assertRedirect(route('orders'))
            ->assertSessionHas('status', 'Your order has been cancelled.');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame(5, $variant->fresh()->stock);

        $this->actingAs($customer)
            ->get(route('orders'))
            ->assertOk()
            ->assertSee('Your order has been cancelled.')
            ->assertSee('Cancelled')
            ->assertSee('data-order-cancel', false)
            ->assertSee('hidden', false);
    }

    public function test_customer_cannot_cancel_another_customers_order_or_an_order_past_processing(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $attributes = [
            'customer_name' => 'Test Customer',
            'customer_email' => $customer->email,
            'customer_phone' => '09123456789',
            'delivery_address' => '123 Example Street',
            'payment_method' => 'Cash on delivery',
            'subtotal' => 450,
            'shipping_fee' => 0,
            'total' => 450,
        ];
        $otherOrder = Order::create($attributes + ['user_id' => $otherCustomer->id]);
        $shippedOrder = Order::create($attributes + ['user_id' => $customer->id, 'status' => 'shipped']);

        $this->actingAs($customer)
            ->post(route('orders.cancel', $otherOrder))
            ->assertNotFound();

        $this->actingAs($customer)
            ->from(route('orders'))
            ->post(route('orders.cancel', $shippedOrder))
            ->assertRedirect(route('orders'))
            ->assertSessionHasErrors('order');

        $this->assertSame('processing', $otherOrder->fresh()->status);
        $this->assertSame('shipped', $shippedOrder->fresh()->status);
    }

    public function test_customer_order_updates_return_only_their_current_statuses(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $attributes = [
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '09123456789',
            'delivery_address' => '123 Example Street',
            'payment_method' => 'Cash on delivery',
            'subtotal' => 450,
            'shipping_fee' => 0,
            'total' => 450,
        ];
        $ownOrder = Order::create($attributes + ['user_id' => $customer->id]);
        Order::create($attributes + ['user_id' => $otherCustomer->id]);

        $response = $this->actingAs($customer)
            ->getJson(route('orders.updates'))
            ->assertOk();

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.id', $ownOrder->id)
            ->assertJsonPath('orders.0.status', 'processing');

        $ownOrder->update(['status' => 'shipped']);

        $this->actingAs($customer)
            ->getJson(route('orders.updates'))
            ->assertJsonPath('orders.0.status', 'shipped');
    }

    public function test_customer_order_page_displays_manila_purchase_time(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
        $customer = User::factory()->create();
        $order = Order::create([
            'user_id' => $customer->id,
            'customer_name' => 'Test Customer',
            'customer_email' => $customer->email,
            'customer_phone' => '09123456789',
            'delivery_address' => '123 Example Street',
            'payment_method' => 'Cash on delivery',
            'subtotal' => 450,
            'shipping_fee' => 0,
            'total' => 450,
        ]);
        $order->created_at = Carbon::parse('2026-11-20 09:00:00', 'Asia/Manila');
        $order->save();

        $this->actingAs($customer)
            ->get(route('orders'))
            ->assertOk()
            ->assertSee('Placed Nov 20, 2026 · 9:00 AM')
            ->assertSee($order->order_number);
    }
}
