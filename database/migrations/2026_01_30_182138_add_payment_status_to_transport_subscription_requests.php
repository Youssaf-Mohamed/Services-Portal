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
        Schema::table('transport_subscription_requests', function (Blueprint $table) {
            $table->enum('payment_status', ['pending_verification', 'verified', 'flagged'])
                  ->default('pending_verification')
                  ->after('status');
            $table->text('payment_flag_reason')->nullable()->after('payment_status');
            $table->timestamp('payment_verified_at')->nullable()->after('payment_flag_reason');
            $table->foreignId('payment_verified_by')->nullable()->after('payment_verified_at')
                  ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transport_subscription_requests', function (Blueprint $table) {
            $table->dropForeign(['payment_verified_by']);
            $table->dropColumn([
                'payment_status',
                'payment_flag_reason',
                'payment_verified_at',
                'payment_verified_by'
            ]);
        });
    }
};
