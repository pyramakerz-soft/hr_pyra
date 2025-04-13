<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUserForeignKeysOnDeleteSetNull extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop existing foreign keys
            $table->dropForeign(['department_id']);
            $table->dropForeign(['sub_department_id']);

            // Re-add foreign keys with onDelete('set null')
            $table->foreign('department_id')
                  ->references('id')->on('departments')
                  ->onDelete('set null');

            $table->foreign('sub_department_id')
                  ->references('id')->on('sub_departments')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Rollback by dropping the new foreign keys
            $table->dropForeign(['department_id']);
            $table->dropForeign(['sub_department_id']);

            // Re-add old ones (you can set it back to restrict or cascade if needed)
            $table->foreign('department_id')
                  ->references('id')->on('departments');

            $table->foreign('sub_department_id')
                  ->references('id')->on('sub_departments');
        });
    }
}
