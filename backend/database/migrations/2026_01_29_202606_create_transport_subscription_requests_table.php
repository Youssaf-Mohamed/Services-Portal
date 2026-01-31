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
        Schema::create('transport_subscription_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('route_id')->constrained('bus_routes')->onDelete('restrict');
            $table->foreignId('slot_id')->constrained('bus_schedule_slots')->onDelete('restrict');
            $table->string('plan_type', 20); // monthly, term
            $table->string('direction', 20); // one_way, round_trip
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->foreignId('payment_method_id')->constrained('payment_methods')->onDelete('restrict');
            $table->string('paid_from_number', 30);
            $table->dateTime('paid_at');
            $table->string('proof_path', 255); // Private storage path
            $table->json('pricing_snapshot');
            $table->decimal('amount_expected', 10, 2);
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            // Indexes for filtering and analytics
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('route_id');
            $table->index('slot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_subscription_requests');
    }
};
