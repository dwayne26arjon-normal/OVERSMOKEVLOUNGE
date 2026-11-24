<?php

namespace Tests\Feature;

use App\Models\SupportChatConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_shared_staff_can_exchange_support_messages(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $staff = User::factory()->create(['is_staff' => true]);

        $this->actingAs($customer, 'web')
            ->getJson(route('support-chat.index'))
            ->assertOk()
            ->assertJsonPath('conversation_id', null)
            ->assertJsonPath('messages', []);
        $this->actingAs($customer, 'web')
            ->getJson(route('support-chat.unread-count'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->actingAs($customer, 'web')
            ->postJson(route('support-chat.messages.store'), [
                'message' => 'Can you help me with my order?',
            ])
            ->assertCreated()
            ->assertJsonPath('message.is_mine', true);

        $conversation = SupportChatConversation::query()->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->getJson(route('admin.support-chat.conversations'))
            ->assertOk()
            ->assertJsonPath('conversations.0.customer', $customer->name)
            ->assertJsonPath('conversations.0.unread_count', 1);
        $this->actingAs($admin, 'admin')
            ->getJson(route('admin.support-chat.unread-count'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $this->actingAs($admin, 'admin')
            ->getJson(route('admin.support-chat.messages', $conversation))
            ->assertOk()
            ->assertJsonPath('messages.0.message', 'Can you help me with my order?')
            ->assertJsonPath('messages.0.is_mine', false);
        $this->actingAs($admin, 'admin')
            ->getJson(route('admin.support-chat.conversations'))
            ->assertOk()
            ->assertJsonPath('conversations.0.unread_count', 0);
        $this->actingAs($admin, 'admin')
            ->getJson(route('admin.support-chat.unread-count'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->actingAs($staff, 'admin')
            ->postJson(route('admin.support-chat.messages.store', $conversation), [
                'message' => 'Of course, we are happy to help.',
            ])
            ->assertCreated()
            ->assertJsonPath('message.sender_role', 'staff');

        $this->actingAs($customer, 'web')
            ->getJson(route('support-chat.unread-count'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $this->actingAs($customer, 'web')
            ->getJson(route('support-chat.index'))
            ->assertOk()
            ->assertJsonPath('messages.1.message', 'Of course, we are happy to help.')
            ->assertJsonPath('messages.1.sender_role', 'staff')
            ->assertJsonPath('messages.1.is_mine', false);
        $this->actingAs($customer, 'web')
            ->getJson(route('support-chat.unread-count'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->actingAs($customer, 'web')
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-user-support-chat', false)
            ->assertSee(route('support-chat.index'), false)
            ->assertSee(route('support-chat.unread-count'), false);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.support-chat.index'))
            ->assertOk()
            ->assertSee('data-admin-support-chat', false)
            ->assertSee('Support chat');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.support-chat.unread-count'), false);
    }

    public function test_customers_cannot_read_other_conversations_or_use_admin_chat_routes(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $this->actingAs($customer, 'web')
            ->postJson(route('support-chat.messages.store'), ['message' => 'Private support request'])
            ->assertCreated();
        $conversation = SupportChatConversation::query()->firstOrFail();

        $this->actingAs($otherCustomer, 'web')
            ->getJson(route('support-chat.index'))
            ->assertOk()
            ->assertJsonPath('conversation_id', null)
            ->assertJsonPath('messages', []);

        $this->actingAs($otherCustomer, 'web')
            ->getJson(route('admin.support-chat.messages', $conversation))
            ->assertForbidden();
    }
}
