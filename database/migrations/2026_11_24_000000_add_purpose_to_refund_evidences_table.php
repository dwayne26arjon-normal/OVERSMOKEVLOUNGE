<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('refund_evidences') && ! Schema::hasColumn('refund_evidences', 'purpose')) {
            Schema::table('refund_evidences', function (Blueprint $table): void {
                $table->string('purpose')->default('evidence')->after('refund_request_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('refund_evidences') && Schema::hasColumn('refund_evidences', 'purpose')) {
            Schema::table('refund_evidences', function (Blueprint $table): void {
                $table->dropColumn('purpose');
            });
        }
    }
};
