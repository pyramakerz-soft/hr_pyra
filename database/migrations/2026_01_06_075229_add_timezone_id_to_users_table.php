<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimezoneIdToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add the 'timezone_id' column to the 'users' table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('timezone_id')->nullable()->constrained('timezones'); // Nullable without default value
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop the 'timezone_id' column from the 'users' table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('timezone_id');
        });
    }
}
