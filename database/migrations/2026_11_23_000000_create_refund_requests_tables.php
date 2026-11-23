<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('refund_requests')) {
            Schema::create('refund_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('pending');
                $table->text('description');
                $table->text('admin_note')->nullable();
                $table->decimal('approved_amount', 10, 2)->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'created_at']);
            });
        }

        if (! Schema::hasTable('refund_evidences')) {
            Schema::create('refund_evidences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('refund_request_id')->constrained()->cascadeOnDelete();
                $table->string('disk')->default('local');
                $table->string('path')->unique();
                $table->string('original_name');
                $table->string('mime_type', 100);
                $table->unsignedBigInteger('size');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_evidences');
        Schema::dropIfExists('refund_requests');
    }
};
