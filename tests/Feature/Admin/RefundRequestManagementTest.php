<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RefundRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_review_a_request_and_record_manual_refund_completion(): void
    {
        $admin = $this->createAdmin();
        $refundRequest = $this->createRefundRequest();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.refunds.index'))
            ->assertOk()
            ->assertSee('Refund requests')
            ->assertSee($refundRequest->order->order_number)
            ->assertSee('Needs review');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.refunds.index', ['q' => $refundRequest->order->order_number]))
            ->assertOk()
            ->assertSee($refundRequest->order->order_number);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.refunds.show', $refundRequest))
            ->assertOk()
            ->assertSee('Damage description')
            ->assertSee('The package arrived damaged.')
            ->assertSee('Approve request')
            ->assertSee('Approval records an amount but does not transfer money.');

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.refunds.update', $refundRequest), [
                'status' => 'approved',
                'approved_amount' => '125.50',
                'admin_note' => 'Approved for the damaged item.',
            ])
            ->assertRedirect(route('admin.refunds.show', $refundRequest));

        $this->assertDatabaseHas('refund_requests', [
            'id' => $refundRequest->id,
            'status' => 'approved',
            'approved_amount' => '125.50',
            'reviewed_by' => $admin->id,
            'admin_note' => 'Approved for the damaged item.',
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.refunds.update', $refundRequest), [
                'status' => 'refunded',
            ])
            ->assertRedirect(route('admin.refunds.show', $refundRequest));

        $this->assertDatabaseHas('refund_requests', [
            'id' => $refundRequest->id,
            'status' => 'refunded',
            'completed_by' => $admin->id,
        ]);
        $this->assertNotNull($refundRequest->fresh()->completed_at);
    }

    public function test_admin_must_provide_a_reason_to_decline_and_cannot_approve_more_than_order_total(): void
    {
        $admin = $this->createAdmin();
        $refundRequest = $this->createRefundRequest();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.refunds.show', $refundRequest))
            ->patch(route('admin.refunds.update', $refundRequest), [
                'status' => 'rejected',
            ])
            ->assertRedirect(route('admin.refunds.show', $refundRequest))
            ->assertSessionHasErrors('admin_note');

        $this->actingAs($admin, 'admin')
            ->from(route('admin.refunds.show', $refundRequest))
            ->patch(route('admin.refunds.update', $refundRequest), [
                'status' => 'approved',
                'approved_amount' => '901',
            ])
            ->assertRedirect(route('admin.refunds.show', $refundRequest))
            ->assertSessionHasErrors('approved_amount');

        $this->assertSame('pending', $refundRequest->fresh()->status);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.refunds.update', $refundRequest), [
                'status' => 'rejected',
                'admin_note' => 'Please contact the store for more details.',
            ])
            ->assertRedirect(route('admin.refunds.show', $refundRequest));

        $this->assertDatabaseHas('refund_requests', [
            'id' => $refundRequest->id,
            'status' => 'rejected',
            'admin_note' => 'Please contact the store for more details.',
            'approved_amount' => null,
        ]);
    }

    public function test_staff_can_review_refund_requests(): void
    {
        $staff = User::factory()->create(['is_staff' => true]);
        $refundRequest = $this->createRefundRequest();

        $this->actingAs($staff, 'admin')
            ->get(route('admin.refunds.index'))
            ->assertOk()
            ->assertSee($refundRequest->order->order_number);
    }

    public function test_admin_can_preview_uploaded_image_evidence(): void
    {
        Storage::fake('local');
        $admin = $this->createAdmin();
        $refundRequest = $this->createRefundRequest();
        $path = 'refund-evidence/damaged-bottle.png';
        Storage::disk('local')->put(
            $path,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lS8AAAAASUVORK5CYII=')
        );
        $evidence = $refundRequest->evidences()->create([
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'damaged-bottle.png',
            'mime_type' => 'image/png',
            'size' => 100,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.refunds.show', $refundRequest))
            ->assertOk()
            ->assertSee('alt="Refund evidence photo 1"', false)
            ->assertSee(route('admin.refund-evidence.preview', $evidence), false)
            ->assertDontSee('damaged-bottle.png');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.refund-evidence.preview', $evidence))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_only_admins_and_staff_can_review_refund_requests(): void
    {
        $customer = User::factory()->create();
        $refundRequest = $this->createRefundRequest();
        $evidence = $refundRequest->evidences()->create([
            'disk' => 'local',
            'path' => 'refund-evidence/private.png',
            'original_name' => 'private.png',
            'mime_type' => 'image/png',
            'size' => 100,
        ]);

        $this->actingAs($customer, 'web')
            ->get(route('admin.refunds.index'))
            ->assertForbidden();

        $this->actingAs($customer, 'web')
            ->get(route('admin.refund-evidence.download', $evidence))
            ->assertForbidden();

        $this->actingAs($customer, 'web')
            ->get(route('admin.refund-evidence.preview', $evidence))
            ->assertForbidden();
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        return $admin;
    }

    private function createRefundRequest(): RefundRequest
    {
        $customer = User::factory()->create();
        $order = Order::create([
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '09123456789',
            'delivery_address' => '123 Example Street',
            'payment_method' => 'Cash on delivery',
            'status' => 'completed',
            'subtotal' => 900,
            'shipping_fee' => 0,
            'total' => 900,
        ]);

        return $order->refundRequest()->create([
            'user_id' => $customer->id,
            'description' => 'The package arrived damaged.',
            'status' => 'pending',
        ]);
    }
}
