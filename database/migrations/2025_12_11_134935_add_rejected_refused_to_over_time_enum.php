<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update status enum to include rejected and refused
        DB::statement("ALTER TABLE over_time MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'declined', 'refused') DEFAULT 'pending'");

        // Update approval_of_direct enum to include rejected and refused
        DB::statement("ALTER TABLE over_time MODIFY COLUMN approval_of_direct ENUM('pending', 'approved', 'declined', 'rejected', 'refused') DEFAULT 'pending'");

        // Update approval_of_head enum to include rejected and refused
        DB::statement("ALTER TABLE over_time MODIFY COLUMN approval_of_head ENUM('pending', 'approved', 'declined', 'rejected', 'refused') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous enum values (be careful if 'rejected' or 'refused' data exists)
        DB::statement("ALTER TABLE over_time MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
        DB::statement("ALTER TABLE over_time MODIFY COLUMN approval_of_direct ENUM('pending', 'approved', 'declined') DEFAULT 'pending'");
        DB::statement("ALTER TABLE over_time MODIFY COLUMN approval_of_head ENUM('pending', 'approved', 'declined') DEFAULT 'pending'");
    }
};
