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
        Schema::table('sub_departments', function (Blueprint $table) {
            $table->renameColumn('manager_id', 'teamlead_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_departments', function (Blueprint $table) {
            $table->renameColumn('teamlead_id', 'manager_id');
        });
    }
};
