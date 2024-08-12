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
            $table->decimal('salary', 8, 2);
            $table->decimal('hourly_rate', 8, 2);
            $table->decimal('working_hours_day', 8, 2);
            $table->decimal('overtime_hours', 8, 2);
            $table->decimal('start_time', 8, 2);
            $table->decimal('end_time', 8, 2);
            $table->string('emp_type');
            $table->date('hiring_date');
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
