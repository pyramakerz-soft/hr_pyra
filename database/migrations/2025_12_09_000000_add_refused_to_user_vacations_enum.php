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
        DB::statement("ALTER TABLE user_vacations MODIFY COLUMN approval_of_direct ENUM('pending', 'approved', 'declined', 'rejected', 'refused') DEFAULT 'pending'");
        DB::statement("ALTER TABLE user_vacations MODIFY COLUMN approval_of_head ENUM('pending', 'approved', 'declined', 'rejected', 'refused') DEFAULT 'pending'");
        DB::statement("ALTER TABLE user_vacations MODIFY COLUMN status ENUM('pending', 'approved', 'declined', 'rejected', 'refused') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous enum values (be careful if 'refused' data exists)
        DB::statement("ALTER TABLE user_vacations MODIFY COLUMN approval_of_direct ENUM('pending', 'approved', 'declined', 'rejected') DEFAULT 'pending'");
        DB::statement("ALTER TABLE user_vacations MODIFY COLUMN approval_of_head ENUM('pending', 'approved', 'declined', 'rejected') DEFAULT 'pending'");
        DB::statement("ALTER TABLE user_vacations MODIFY COLUMN status ENUM('pending', 'approved', 'declined', 'rejected') DEFAULT 'pending'");
    }
};
