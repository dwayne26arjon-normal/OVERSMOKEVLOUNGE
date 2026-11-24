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
            ->assertSee(route('support-chat.unread-count'), false)
            ->assertSee(route('support-chat.messages.destroy', ['message' => '__MESSAGE_ID__']), false)
            ->assertSee('data-chat-confirm-dialog', false)
            ->assertSee(asset('js/chat-confirmation.js'), false);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.support-chat.index'))
            ->assertOk()
            ->assertSee('data-admin-support-chat', false)
            ->assertSee('Support chat')
            ->assertSee('data-chat-delete', false)
            ->assertSee(route('admin.support-chat.conversations.destroy', ['conversation' => '__CONVERSATION_ID__']), false)
            ->assertSee('data-chat-confirm-dialog', false)
            ->assertSee(asset('js/chat-confirmation.js'), false);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.support-chat.unread-count'), false);
    }

    public function test_customer_can_delete_only_their_own_chat_message(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $staff = User::factory()->create(['is_staff' => true]);

        $this->actingAs($customer, 'web')
            ->postJson(route('support-chat.messages.store'), ['message' => 'My message'])
            ->assertCreated();
        $customerMessage = SupportChatConversation::query()
            ->where('user_id', $customer->id)
            ->firstOrFail()
            ->messages()
            ->firstOrFail();

        $this->actingAs($otherCustomer, 'web')
            ->postJson(route('support-chat.messages.store'), ['message' => 'Another customer message'])
            ->assertCreated();
        $otherMessage = SupportChatConversation::query()
            ->where('user_id', $otherCustomer->id)
            ->firstOrFail()
            ->messages()
            ->firstOrFail();

        $this->actingAs($staff, 'admin')
            ->postJson(route('admin.support-chat.messages.store', $customerMessage->conversation), [
                'message' => 'A support reply',
            ])
            ->assertCreated();
        $supportMessage = $customerMessage->conversation->messages()->where('sender_role', 'staff')->firstOrFail();

        $this->actingAs($customer, 'web')
            ->deleteJson(route('support-chat.messages.destroy', ['message' => $customerMessage->id]))
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertDatabaseMissing('support_chat_messages', ['id' => $customerMessage->id]);

        $this->actingAs($customer, 'web')
            ->deleteJson(route('support-chat.messages.destroy', ['message' => $supportMessage->id]))
            ->assertNotFound();
        $this->actingAs($customer, 'web')
            ->deleteJson(route('support-chat.messages.destroy', ['message' => $otherMessage->id]))
            ->assertNotFound();

        $this->assertDatabaseHas('support_chat_messages', ['id' => $supportMessage->id]);
        $this->assertDatabaseHas('support_chat_messages', ['id' => $otherMessage->id]);
    }

    public function test_admin_can_delete_a_conversation_and_its_messages(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($customer, 'web')
            ->postJson(route('support-chat.messages.store'), ['message' => 'Please help'])
            ->assertCreated();
        $conversation = SupportChatConversation::query()->firstOrFail();
        $messageId = $conversation->messages()->value('id');

        $this->actingAs($admin, 'admin')
            ->deleteJson(route('admin.support-chat.conversations.destroy', $conversation))
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertDatabaseMissing('support_chat_conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('support_chat_messages', ['id' => $messageId]);
        $this->actingAs($customer, 'web')
            ->getJson(route('support-chat.index'))
            ->assertOk()
            ->assertJsonPath('conversation_id', null)
            ->assertJsonPath('messages', []);
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

        $this->actingAs($customer, 'web')
            ->deleteJson(route('admin.support-chat.conversations.destroy', $conversation))
            ->assertForbidden();
    }
}
