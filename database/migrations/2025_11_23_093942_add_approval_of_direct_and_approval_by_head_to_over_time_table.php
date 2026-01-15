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
            if (!Schema::hasColumn('over_time', 'overtime_minutes')) {
                $table->unsignedInteger('overtime_minutes')->nullable();
            }
            if (!Schema::hasColumn('over_time', 'approval_of_direct')) {
                $table->enum('approval_of_direct', ['pending', 'approved', 'declined'])->default('pending');
            }
            if (!Schema::hasColumn('over_time', 'approval_of_head')) {
                $table->enum('approval_of_head', ['pending', 'approved', 'declined'])->default('pending');
            }
            if (!Schema::hasColumn('over_time', 'direct_approved_by')) {
                $table->foreignId('direct_approved_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('over_time', 'head_approved_by')) {
                $table->foreignId('head_approved_by')->nullable()->constrained('users')->nullOnDelete();
            }
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
