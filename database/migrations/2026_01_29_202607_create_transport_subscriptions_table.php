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
        Schema::create('transport_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('transport_subscription_requests')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('route_id')->constrained('bus_routes')->onDelete('restrict');
            $table->foreignId('slot_id')->constrained('bus_schedule_slots')->onDelete('restrict');
            $table->string('plan_type', 20); // monthly, term
            $table->string('status', 20); // active, waitlisted, expired, cancelled
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->decimal('amount_paid_expected', 10, 2);
            $table->timestamps();

            // Unique constraint - one subscription per request
            $table->unique('request_id');

            // Indexes for analytics and filtering
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_subscriptions');
    }
};
