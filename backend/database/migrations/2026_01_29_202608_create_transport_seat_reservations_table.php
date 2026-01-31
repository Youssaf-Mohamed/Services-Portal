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
        Schema::create('transport_seat_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained('bus_schedule_slots')->onDelete('cascade');
            $table->foreignId('subscription_id')->constrained('transport_subscriptions')->onDelete('cascade');
            $table->dateTime('reserved_at');
            $table->dateTime('released_at')->nullable();
            $table->timestamps();

            // One reservation per subscription
            $table->unique('subscription_id');

            // Indexes for capacity management
            $table->index('slot_id');
            $table->index('released_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_seat_reservations');
    }
};
