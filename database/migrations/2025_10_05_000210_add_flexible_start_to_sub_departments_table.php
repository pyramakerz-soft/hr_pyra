<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_departments', function (Blueprint $table) {
            $table->time('flexible_start_time')->nullable()->after('teamlead_id');
        });
    }

    public function down(): void
    {
        Schema::table('sub_departments', function (Blueprint $table) {
            $table->dropColumn('flexible_start_time');
        });
    }
};
