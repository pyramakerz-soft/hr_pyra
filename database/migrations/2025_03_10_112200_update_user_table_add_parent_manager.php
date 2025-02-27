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
            $table->unsignedBigInteger('parent_manager_id')->nullable(); // Manager Hierarchy

            // Foreign Key for Manager Hierarchy (Self-referencing)
            $table->foreign('parent_manager_id')->references('id')->on('users')->onDelete('set null');
        
        });
    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {     Schema::table('over_time_in_outs', function (Blueprint $table) {
        $table->dropColumn(['parent_manager_id', ]);

      });
    }
};
