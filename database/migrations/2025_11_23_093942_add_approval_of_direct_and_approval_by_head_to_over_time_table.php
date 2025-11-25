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
            $table->unsignedInteger('overtime_minutes')->nullable();
            $table->enum('approval_of_direct', ['pending', 'approved', 'declined'])->default('pending');
            $table->enum('approval_of_head', ['pending', 'approved', 'declined'])->default('pending');
            $table->foreignId('direct_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('head_approved_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('over_time', function (Blueprint $table) {
            //
        });
    }
};
