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
            // Drop the old foreign key constraint
            $table->dropForeign(['location_id']);

            // Re-add the foreign key with ON DELETE SET NULL
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clock_in_outs', function (Blueprint $table) {
            // Drop the modified foreign key
            $table->dropForeign(['location_id']);

            // Re-add the original foreign key with ON DELETE CASCADE
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }
};
