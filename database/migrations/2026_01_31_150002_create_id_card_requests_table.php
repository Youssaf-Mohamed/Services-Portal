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
        Schema::create('id_card_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('type_id')->constrained('id_card_types')->onDelete('restrict');
            
            // Status workflow: pending, approved, rejected, ready_for_pickup, delivered
            $table->string('status', 30)->default('pending');
            
            // Fee snapshot (frozen at submission time)
            $table->decimal('amount_snapshot', 10, 2);
            
            // Payment info
            $table->string('transaction_number', 50);
            $table->dateTime('transfer_time');
            $table->string('transfer_screenshot_path', 255);
            
            // Payment verification
            $table->string('payment_status', 20)->default('pending');
            $table->text('payment_flag_reason')->nullable();
            $table->foreignId('payment_verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('payment_verified_at')->nullable();
            
            // Service-specific fields
            $table->string('new_photo_path', 255)->nullable();
            $table->text('issue_description')->nullable();
            
            // Admin workflow
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('reviewed_at')->nullable();
            $table->dateTime('ready_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->foreignId('delivered_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes for filtering
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('type_id');
            $table->index('created_at');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('id_card_requests');
    }
};
