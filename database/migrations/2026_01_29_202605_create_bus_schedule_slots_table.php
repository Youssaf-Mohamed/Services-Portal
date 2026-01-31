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
        Schema::create('bus_schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('bus_routes')->onDelete('cascade');
            $table->unsignedTinyInteger('day_of_week'); // 0=Sunday, 6=Saturday
            $table->string('direction', 20); // one_way, round_trip
            $table->time('time');
            $table->unsignedSmallInteger('capacity')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Prevent duplicate schedule entries
            $table->unique(['route_id', 'day_of_week', 'direction', 'time']);

            // Indexes for filtering
            $table->index('route_id');
            $table->index('active');
            $table->index('day_of_week');
            $table->index('direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_schedule_slots');
    }
};
