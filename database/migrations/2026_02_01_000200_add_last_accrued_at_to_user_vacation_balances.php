<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_vacation_balances', function (Blueprint ) {
            ->timestamp('last_accrued_at')->nullable()->after('used_days');
        });
    }

    public function down(): void
    {
        Schema::table('user_vacation_balances', function (Blueprint ) {
            ->dropColumn('last_accrued_at');
        });
    }
};

