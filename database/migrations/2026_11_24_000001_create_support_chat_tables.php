<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('support_chat_conversations')) {
            Schema::create('support_chat_conversations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('support_chat_messages')) {
            Schema::create('support_chat_messages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('conversation_id')->constrained('support_chat_conversations')->cascadeOnDelete();
                $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('sender_role', 20);
                $table->string('sender_name');
                $table->text('message');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['conversation_id', 'id']);
                $table->index(['conversation_id', 'sender_role', 'read_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_chat_messages');
        Schema::dropIfExists('support_chat_conversations');
    }
};
