<?php

namespace Tests\Feature\User;

use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RefundRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_a_private_damage_refund_request_with_evidence(): void
    {
        Storage::fake('local');
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $order = $this->createOrder($customer);
        $otherOrder = $this->createOrder($otherCustomer);

        $this->actingAs($customer, 'web')
            ->post(route('orders.refunds.store', $order), [
                'refund_order_id' => $order->id,
                'description' => 'The bottle arrived cracked and leaked.',
                'attachments' => [
                    UploadedFile::fake()->createWithContent(
                        'damaged-bottle.png',
                        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lS8AAAAASUVORK5CYII=')
                    ),
                    UploadedFile::fake()->create('unboxing.mp4', 100, 'video/mp4'),
                ],
            ])
            ->assertRedirect(route('orders'))
            ->assertSessionHas('status', 'Your refund request was submitted for review.');

        $refundRequest = RefundRequest::query()->with('evidences')->firstOrFail();
        $this->assertSame($order->id, $refundRequest->order_id);
        $this->assertSame($customer->id, $refundRequest->user_id);
        $this->assertSame('pending', $refundRequest->status);
        $this->assertSame('The bottle arrived cracked and leaked.', $refundRequest->description);
        $this->assertCount(2, $refundRequest->evidences);
        Storage::disk('local')->assertExists($refundRequest->evidences->first()->path);
        Storage::disk('local')->assertExists($refundRequest->evidences->last()->path);

        $this->actingAs($customer, 'web')
            ->get(route('refund-evidence.download', $refundRequest->evidences->first()))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($otherCustomer, 'web')
            ->get(route('refund-evidence.download', $refundRequest->evidences->first()))
            ->assertNotFound();

        $this->actingAs($customer, 'web')
            ->get(route('orders'))
            ->assertOk()
            ->assertSee('Refund request:')
            ->assertSee('Pending')
            ->assertDontSee('Request a refund');

        $this->actingAs($customer, 'web')
            ->from(route('orders'))
            ->post(route('orders.refunds.store', $order), [
                'description' => 'A second request for the same order.',
            ])
            ->assertRedirect(route('orders'))
            ->assertSessionHasErrors('refund_request');

        $this->assertDatabaseCount('refund_requests', 1);

        $this->actingAs($customer, 'web')
            ->from(route('orders'))
            ->post(route('orders.cancel', $order))
            ->assertRedirect(route('orders'))
            ->assertSessionHasErrors('order');
    }

    public function test_customer_cannot_request_refund_for_another_customers_or_cancelled_order(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $otherOrder = $this->createOrder($otherCustomer);
        $cancelledOrder = $this->createOrder($customer, 'cancelled');

        $this->actingAs($customer, 'web')
            ->post(route('orders.refunds.store', $otherOrder), [
                'description' => 'This is not my order.',
            ])
            ->assertNotFound();

        $this->actingAs($customer, 'web')
            ->from(route('orders'))
            ->post(route('orders.refunds.store', $cancelledOrder), [
                'description' => 'The item arrived damaged.',
            ])
            ->assertRedirect(route('orders'))
            ->assertSessionHasErrors('refund_request');

        $this->assertDatabaseCount('refund_requests', 0);
    }

    public function test_orders_page_offers_the_form_only_for_non_cancelled_orders_without_a_request(): void
    {
        $customer = User::factory()->create();
        $processingOrder = $this->createOrder($customer);
        $cancelledOrder = $this->createOrder($customer, 'cancelled');

        $this->actingAs($customer, 'web')
            ->get(route('orders'))
            ->assertOk()
            ->assertSee('Request a refund')
            ->assertSee(route('orders.refunds.store', $processingOrder), false)
            ->assertDontSee(route('orders.refunds.store', $cancelledOrder), false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('video/mp4');
    }

    public function test_customer_cannot_submit_unsupported_evidence(): void
    {
        $customer = User::factory()->create();
        $order = $this->createOrder($customer);

        $this->actingAs($customer, 'web')
            ->from(route('orders'))
            ->post(route('orders.refunds.store', $order), [
                'description' => 'The item arrived damaged.',
                'attachments' => [UploadedFile::fake()->create('unsafe.svg', 1, 'image/svg+xml')],
            ])
            ->assertRedirect(route('orders'))
            ->assertSessionHasErrors('attachments.0');

        $this->assertDatabaseCount('refund_requests', 0);
    }

    private function createOrder(User $customer, string $status = 'processing'): Order
    {
        return Order::create([
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '09123456789',
            'delivery_address' => '123 Example Street',
            'payment_method' => 'Cash on delivery',
            'status' => $status,
            'subtotal' => 900,
            'shipping_fee' => 0,
            'total' => 900,
        ]);
    }
}
