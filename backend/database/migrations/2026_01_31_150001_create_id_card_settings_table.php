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
        Schema::create('id_card_settings', function (Blueprint $table) {
            $table->id();
            $table->string('payment_account_number', 50);
            $table->string('payment_account_name', 100);
            $table->text('payment_instructions')->nullable();
            $table->boolean('service_enabled')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('id_card_settings');
    }
};
