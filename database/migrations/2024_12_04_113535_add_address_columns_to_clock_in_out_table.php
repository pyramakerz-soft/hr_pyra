<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clock_in_outs', function (Blueprint $table) {
            $table->string('address_clock_in')->nullable();
            $table->string('address_clock_out')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clock_in_outs', function (Blueprint $table) {
            $table->dropColumn('address_clock_in');
            $table->dropColumn('address_clock_out');
        });
    }
};
