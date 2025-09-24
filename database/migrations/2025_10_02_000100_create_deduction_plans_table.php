<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deduction_plans', function (Blueprint $table) {
            $table->id();
            $table->morphs('planable');
            $table->boolean('overwrite')->default(false);
            $table->unsignedInteger('grace_minutes')->nullable();
            $table->json('rules')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deduction_plans');
    }
};
