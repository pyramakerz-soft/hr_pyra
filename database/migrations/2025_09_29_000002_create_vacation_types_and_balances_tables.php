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
        Schema::create('vacation_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('default_days')->default(0);
            $table->timestamps();
        });

        Schema::create('user_vacation_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vacation_type_id')->constrained('vacation_types')->cascadeOnDelete();
            $table->year('year');
            $table->unsignedDecimal('allocated_days', 5, 2)->default(0);
            $table->unsignedDecimal('used_days', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'vacation_type_id', 'year']);
        });

        Schema::table('user_vacations', function (Blueprint $table) {
            $table->foreignId('vacation_type_id')->nullable()->after('user_id')->constrained('vacation_types')->nullOnDelete();
            $table->unsignedDecimal('days_count', 5, 2)->default(0)->after('to_date');
            $table->enum('approval_of_direct', ['pending', 'approved', 'declined'])->default('pending')->after('status');
            $table->enum('approval_of_head', ['pending', 'approved', 'declined'])->default('pending')->after('approval_of_direct');
            $table->foreignId('direct_approved_by')->nullable()->after('approval_of_head')->constrained('users')->nullOnDelete();
            $table->foreignId('head_approved_by')->nullable()->after('direct_approved_by')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_vacations', function (Blueprint $table) {
            $table->dropForeign(['vacation_type_id']);
            $table->dropForeign(['direct_approved_by']);
            $table->dropForeign(['head_approved_by']);
            $table->dropColumn(['vacation_type_id', 'days_count', 'approval_of_direct', 'approval_of_head', 'direct_approved_by', 'head_approved_by']);
        });

        Schema::dropIfExists('user_vacation_balances');
        Schema::dropIfExists('vacation_types');
    }
};
