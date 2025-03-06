<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('user_vacations', function (Blueprint $table) {
            $table->dateTime('from_date')->change();
            $table->dateTime('to_date')->change();
        });
    }

    public function down()
    {
        Schema::table('user_vacations', function (Blueprint $table) {
            $table->time('from_date')->change();
            $table->time('to_date')->change();
        });
    }
};
