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
        Schema::create('bus_route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('bus_routes')->onDelete('cascade');
            $table->foreignId('stop_id')->constrained('bus_stops')->onDelete('restrict');
            $table->unsignedSmallInteger('sort_order');
            $table->timestamps();

            // Unique constraints
            $table->unique(['route_id', 'sort_order']);
            $table->unique(['route_id', 'stop_id']);

            // Index for ordering
            $table->index(['route_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_route_stops');
    }
};
