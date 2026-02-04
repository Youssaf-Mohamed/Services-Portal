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
        Schema::table('id_card_requests', function (Blueprint $table) {
            $table->string('paid_from_number', 20)->nullable()->after('transaction_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('id_card_requests', function (Blueprint $table) {
            $table->dropColumn('paid_from_number');
        });
    }
};
