<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\User;
use App\Notifications\RefundRequestReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RefundRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_refund_requests_by_submission_date_and_status(): void
    {
        $admin = $this->createAdmin();
        $today = now()->toDateString();
        $needsReview = $this->createRefundRequest();
        $approved = $this->createRefundRequest();
        $approved->update(['status' => 'approved']);
        $declined = $this->createRefundRequest();
        $declined->update(['status' => 'rejected']);
        $completed = $this->createRefundRequest();
        $completed->update(['status' => 'refunded']);
        $older = $this->createRefundRequest();
        $older->forceFill(['created_at' => now()->subDay()])->save();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.refunds.index', ['date' => $today]))
            ->assertOk()
            ->assertSee($needsReview->order->order_number)
            ->assertSee($approved->order->order_number)
            ->assertSee($declined->order->order_number)
            ->assertSee($completed->order->order_number)
            ->assertDontSee($older->order->order_number)
            ->assertSee('value="'.$today.'"', false)
            ->assertSee('type="hidden" name="date" value="'.$today.'"', false)
            ->assertSee('Needs review')
            ->assertSee('class="refund-row-needs-review"', false)
            ->assertSee('Review now')
            ->assertSee('Approved')
            ->assertSee('Declined')
            ->assertSee('Completed')
            ->assertSee('All requests');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.refunds.index', ['date' => $today, 'status' => 'approved']))
            ->assertOk()
            ->assertSee('type="hidden" name="status" value="approved"', false)
            ->assertSee($approved->order->order_number)
            ->assertDontSee($needsReview->order->order_number)
            ->assertDontSee($declined->order->order_number)
            ->assertDontSee($completed->order->order_number);
    }

    public function test_refund_list_shows_one_page_number_and_advances_with_the_next_arrow(): void
    {
        $admin = $this->createAdmin();
        for ($i = 0; $i < 11; $i++) {
            $this->createRefundRequest();
        }

        $this->actingAs($admin, 'admin')
            ->get(route('admin.refunds.index'))
            ->assertOk()
            ->assertSee('Showing 1-10 of 11 requests')
            ->assertSee('Page 1 of 2')
            ->assertSee('Current page, 1', false)
            ->assertSee('rel="next"', false)
            ->assertDontSee('Go to page', false);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.refunds.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Showing 11-11 of 11 requests')
            ->assertSee('Page 2 of 2')
            ->assertSee('Current page, 2', false)
            ->assertSee('rel="prev"', false)
            ->assertDontSee('rel="next"', false);
    }

    public function test_admin_can_review_a_request_and_record_manual_refund_completion(): void
    {
        $admin = $this->createAdmin();
        $refundRequest = $this->createRefundRequest();
        $refundRequest->customer->update(['order_notifications_enabled' => false]);

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
            ->assertSee('class="refund-status status-pending">Pending</span>', false)
            ->assertSee('data-refund-review-checklist', false)
            ->assertSee('The package arrived damaged.')
            ->assertSee('Approve request')
            ->assertSee('id="refundConfirmDialog"', false)
            ->assertSee('Approve this refund request for', false)
            ->assertSee('Decline this refund request?', false)
            ->assertDontSee('window.confirm', false)
            ->assertSee('Approval records an amount but does not transfer money.');

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.refunds.update', $refundRequest), [
                'status' => 'approved',
                'approved_amount' => '125.50',
                'admin_note' => 'Approved for the damaged item.',
            ])
            ->assertRedirect(route('admin.refunds.show', $refundRequest))
            ->assertSessionHas('status', 'Refund request approved. The customer can view the decision in their order history.');

        $this->assertDatabaseHas('refund_requests', [
            'id' => $refundRequest->id,
            'status' => 'approved',
            'approved_amount' => '125.50',
            'reviewed_by' => $admin->id,
            'admin_note' => 'Approved for the damaged item.',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $refundRequest->user_id,
            'type' => RefundRequestReviewed::class,
        ]);
        $this->actingAs($refundRequest->customer, 'web')
            ->getJson(route('user.notifications.index'))
            ->assertOk()
            ->assertJsonPath('enabled', false)
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.kind', 'refund_review')
            ->assertJsonPath('notifications.0.order_number', $refundRequest->order->order_number)
            ->assertJsonPath('notifications.0.message', 'Your refund request was approved.');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.refunds.show', $refundRequest))
            ->assertOk()
            ->assertSee('data-refund-confirmation="complete"', false)
            ->assertSee('Mark refund as complete?', false)
            ->assertDontSee('window.confirm', false);

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
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $refundRequest->user_id,
            'type' => RefundRequestReviewed::class,
        ]);
        $this->assertSame(
            'Your refund request was declined.',
            $refundRequest->customer->notifications()->latest()->firstOrFail()->data['message']
        );
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
            'purpose' => 'evidence',
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'damaged-bottle.png',
            'mime_type' => 'image/png',
            'size' => 100,
        ]);
        $qrPath = 'refund-evidence/customer-wallet-qr.png';
        Storage::disk('local')->put(
            $qrPath,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lS8AAAAASUVORK5CYII=')
        );
        $paymentQr = $refundRequest->evidences()->create([
            'purpose' => 'payment_qr',
            'disk' => 'local',
            'path' => $qrPath,
            'original_name' => 'customer-wallet-qr.png',
            'mime_type' => 'image/png',
            'size' => 100,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.refunds.show', $refundRequest))
            ->assertOk()
            ->assertSee('alt="Refund evidence photo 1"', false)
            ->assertSee(route('admin.refund-evidence.preview', $evidence), false)
            ->assertSee('E-wallet payment QR')
            ->assertSee('alt="Customer e-wallet payment QR code"', false)
            ->assertSee(route('admin.refund-evidence.preview', $paymentQr), false)
            ->assertSee(route('admin.refund-evidence.download', $paymentQr), false)
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
