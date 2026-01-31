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
        Schema::create('transport_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('days_per_week')->default(5);
            $table->unsignedTinyInteger('weeks_in_month')->default(4);
            $table->unsignedTinyInteger('weeks_in_term')->default(12);
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_settings');
    }
};
