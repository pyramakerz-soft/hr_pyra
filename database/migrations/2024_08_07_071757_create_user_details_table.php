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
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->decimal('salary', 8, 2)->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->decimal('overtime_hourly_rate', 8, 2)->nullable();

            $table->decimal('working_hours_day', 8, 2)->nullable();
            $table->decimal('overtime_hours', 8, 2)->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('emp_type')->nullable();
            $table->date('hiring_date')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};