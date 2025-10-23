<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_departments', function (Blueprint $table) {
            $table->boolean('works_on_saturday')->nullable()->after('department_id');
        });

        Schema::table('user_details', function (Blueprint $table) {
            $table->boolean('works_on_saturday')->nullable()->after('emp_type');
        });
    }

    public function down(): void
    {
        Schema::table('sub_departments', function (Blueprint $table) {
            $table->dropColumn('works_on_saturday');
        });

        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn('works_on_saturday');
        });
    }
};

