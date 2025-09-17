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
        Schema::create('user_clock_overtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clock_in_out_id')->constrained('clock_in_outs')->cascadeOnDelete();
            $table->date('overtime_date');
            $table->unsignedInteger('overtime_minutes');
            $table->enum('approval_of_direct', ['pending', 'approved', 'declined'])->default('pending');
            $table->enum('approval_of_head', ['pending', 'approved', 'declined'])->default('pending');
            $table->foreignId('direct_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('head_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'overtime_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_clock_overtimes');
    }
};
