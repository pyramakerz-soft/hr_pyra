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
        Schema::create('system_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->string('title');
            $table->text('message');
            $table->string('scope_type', 50)->default('all');
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->json('filters')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();

            $table->index(['scope_type', 'scope_id']);
            $table->index('type');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('system_notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status', 20)->default('sent');
            $table->timestamp('read_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['notification_id', 'user_id']);
            $table->foreign('notification_id')->references('id')->on('system_notifications')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_notification_recipients');
        Schema::dropIfExists('system_notifications');
    }
};
