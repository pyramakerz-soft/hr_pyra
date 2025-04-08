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
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key first before removing the column
            $table->dropForeign(['parent_manager_id']);
            $table->dropColumn('parent_manager_id');

            // Add sub_department_id
            $table->foreignId('sub_department_id')->nullable()->constrained('sub_departments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop sub_department_id before adding parent_manager_id back
            $table->dropForeign(['sub_department_id']);
            $table->dropColumn('sub_department_id');

            // Re-add parent_manager_id column
            $table->unsignedBigInteger('parent_manager_id')->nullable();

            // Re-add foreign key constraint for parent_manager_id
            $table->foreign('parent_manager_id')->references('id')->on('users')->onDelete('set null');
        });
    }
};
