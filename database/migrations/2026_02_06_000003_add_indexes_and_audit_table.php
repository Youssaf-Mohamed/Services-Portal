<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add missing indexes and create audit_logs table.
     */
    public function up(): void
    {
        // Add missing index on announcements
        Schema::table('announcements', function (Blueprint $table) {
            $table->index(['is_active', 'expires_at'], 'announcements_active_expires_index');
        });

        // Add payment_method_id to id_card_requests
        Schema::table('id_card_requests', function (Blueprint $table) {
            $table->foreignId('payment_method_id')
                  ->nullable()
                  ->after('transfer_screenshot_path')
                  ->constrained('payment_methods')
                  ->nullOnDelete();
        });

        // Create audit_logs table for tracking important actions
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->string('action', 100);
            $table->string('model_type', 100)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Indexes for efficient querying
            $table->index('action');
            $table->index(['model_type', 'model_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropIndex('announcements_active_expires_index');
        });

        Schema::table('id_card_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_method_id');
        });

        Schema::dropIfExists('audit_logs');
    }
};
