<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->boolean('works_on_saturday')->default(false)->after('is_location_time');
            $table->string('work_schedule_type')->default('flexible')->after('works_on_saturday');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['works_on_saturday', 'work_schedule_type']);
        });
    }
};
