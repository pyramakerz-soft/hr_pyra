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
        Schema::table('over_time_in_outs', function (Blueprint $table) {
            Schema::dropIfExists('over_time_in_outs');

        });
    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {     Schema::table('over_time_in_outs', function (Blueprint $table) {
           
        $table->id();
        $table->timestamp('clock_in')->nullable();
        $table->timestamp('clock_out')->nullable();
        $table->time('duration')->nullable();
        $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

        $table->timestamps();
      });
    }
};
