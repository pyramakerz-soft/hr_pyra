<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deduction_plans', function (Blueprint $table) {
            $table->boolean('overwrite_dep')->default(false)->after('overwrite');
            $table->boolean('overwrite_subdep')->default(false)->after('overwrite_dep');
        });
    }

    public function down(): void
    {
        Schema::table('deduction_plans', function (Blueprint $table) {
            $table->dropColumn(['overwrite_dep', 'overwrite_subdep']);
        });
    }
};
