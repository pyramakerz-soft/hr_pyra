<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('custom_vacations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_full_day')->default(true);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('custom_vacation_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_vacation_id')->constrained('custom_vacations')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['custom_vacation_id', 'department_id'], 'custom_vacation_department_unique');
        });

        Schema::create('custom_vacation_sub_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_vacation_id')->constrained('custom_vacations')->cascadeOnDelete();
            $table->foreignId('sub_department_id')->constrained('sub_departments')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['custom_vacation_id', 'sub_department_id'], 'custom_vacation_sub_department_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_vacation_sub_department');
        Schema::dropIfExists('custom_vacation_department');
        Schema::dropIfExists('custom_vacations');
    }
};

