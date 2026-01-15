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
        Schema::table('over_time', function (Blueprint $table) {
            // Drop from and to columns if they exist
            if (Schema::hasColumn('over_time', 'from')) {
                $table->dropColumn('from');
            }
            if (Schema::hasColumn('over_time', 'to')) {
                $table->dropColumn('to');
            }

            // Ensure date column exists (it should already exist from the original migration)
            if (!Schema::hasColumn('over_time', 'date')) {
                $table->date('date')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('over_time', function (Blueprint $table) {
            if (!Schema::hasColumn('over_time', 'from')) {
                $table->time('from')->nullable();
            }
            if (!Schema::hasColumn('over_time', 'to')) {
                $table->time('to')->nullable();
            }
        });
    }
};
