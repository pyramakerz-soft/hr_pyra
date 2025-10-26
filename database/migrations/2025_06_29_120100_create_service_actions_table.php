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
        Schema::create('service_actions', function (Blueprint $table) {
            $table->id();
            $table->string('action_type', 100);
            $table->string('scope_type', 50);
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->string('status', 30)->default('completed');
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamps();

            $table->index(['scope_type', 'scope_id']);
            $table->index('action_type');
            $table->index('status');
            $table->foreign('performed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_actions');
    }
};

