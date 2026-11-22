<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'street' => 200,
            'barangay' => 80,
            'city' => 80,
            'province' => 80,
            'postal_code' => 20,
        ];
        $missingColumns = [];

        foreach ($columns as $name => $length) {
            if (! Schema::hasColumn('users', $name)) {
                $missingColumns[$name] = $length;
            }
        }

        if ($missingColumns === []) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($missingColumns) {
            foreach ($missingColumns as $name => $length) {
                $table->string($name, $length)->nullable();
            }
        });
    }

    public function down(): void
    {
        $columns = ['street', 'barangay', 'city', 'province', 'postal_code'];
        $existingColumns = array_values(array_filter(
            $columns,
            fn (string $column) => Schema::hasColumn('users', $column)
        ));

        if ($existingColumns === []) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($existingColumns) {
            $table->dropColumn($existingColumns);
        });
    }
};
