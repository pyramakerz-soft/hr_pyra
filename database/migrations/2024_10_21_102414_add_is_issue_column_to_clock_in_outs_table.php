<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clock_in_outs', function (Blueprint $table) {
            $table->boolean('is_issue')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clock_in_outs', function (Blueprint $table) {
            $table->dropColumn('is_issue');

        });
    }
};