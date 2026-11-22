<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('street', 200)->nullable();
            $table->string('barangay', 80)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('province', 80)->nullable();
            $table->string('postal_code', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['street', 'barangay', 'city', 'province', 'postal_code']);
        });
    }
};
