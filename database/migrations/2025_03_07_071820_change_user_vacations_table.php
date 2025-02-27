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
        Schema::table('user_vacations', function (Blueprint $table) {
            $table->dropColumn(['sick_left', 'paid_left', 'deduction_left']);
            $table->time('from_date');
            $table->time('to_date');   
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending'); 

        });
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
        Schema::table('user_vacations', function (Blueprint $table) {

            // Revert back by adding the dropped columns
            $table->integer('sick_left')->nullable();
            $table->integer('paid_left')->nullable();
            $table->integer('deduction_left')->nullable();

            // Dropping the newly added date columns
            $table->dropColumn(['from_date', 'to_date','status']);
        });
    }
};
