<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add ENUM constraints to status fields for data integrity.
     * Includes data cleanup before conversion to prevent failures.
     */
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // DATA CLEANUP - Must run BEFORE ENUM conversion
        // ═══════════════════════════════════════════════════════════════

        // Transport subscription requests
        DB::statement("
            UPDATE transport_subscription_requests 
            SET status = 'pending' 
            WHERE status NOT IN ('pending', 'approved', 'rejected', 'cancelled')
        ");

        DB::statement("
            UPDATE transport_subscription_requests 
            SET payment_status = 'pending' 
            WHERE payment_status NOT IN ('pending', 'verified', 'flagged')
        ");

        // Transport subscriptions
        DB::statement("
            UPDATE transport_subscriptions 
            SET status = 'active' 
            WHERE status NOT IN ('active', 'waitlisted', 'expired', 'cancelled')
        ");

        // ID card requests
        DB::statement("
            UPDATE id_card_requests 
            SET status = 'pending' 
            WHERE status NOT IN ('pending', 'approved', 'rejected', 'ready_for_pickup', 'delivered', 'cancelled')
        ");

        DB::statement("
            UPDATE id_card_requests 
            SET payment_status = 'pending' 
            WHERE payment_status NOT IN ('pending', 'verified', 'flagged')
        ");

        // ═══════════════════════════════════════════════════════════════
        // ENUM CONVERSION
        // ═══════════════════════════════════════════════════════════════

        DB::statement("
            ALTER TABLE transport_subscription_requests 
            MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'cancelled') 
            DEFAULT 'pending' NOT NULL
        ");

        DB::statement("
            ALTER TABLE transport_subscription_requests 
            MODIFY COLUMN payment_status ENUM('pending', 'verified', 'flagged') 
            DEFAULT 'pending' NOT NULL
        ");

        DB::statement("
            ALTER TABLE transport_subscriptions 
            MODIFY COLUMN status ENUM('active', 'waitlisted', 'expired', 'cancelled') 
            DEFAULT 'active' NOT NULL
        ");

        DB::statement("
            ALTER TABLE id_card_requests 
            MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'ready_for_pickup', 'delivered', 'cancelled') 
            DEFAULT 'pending' NOT NULL
        ");

        DB::statement("
            ALTER TABLE id_card_requests 
            MODIFY COLUMN payment_status ENUM('pending', 'verified', 'flagged') 
            DEFAULT 'pending' NOT NULL
        ");
    }

    /**
     * Reverse the migrations - convert back to VARCHAR.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE transport_subscription_requests 
            MODIFY COLUMN status VARCHAR(20) DEFAULT 'pending'
        ");

        DB::statement("
            ALTER TABLE transport_subscription_requests 
            MODIFY COLUMN payment_status VARCHAR(20) DEFAULT 'pending'
        ");

        DB::statement("
            ALTER TABLE transport_subscriptions 
            MODIFY COLUMN status VARCHAR(20) DEFAULT 'active'
        ");

        DB::statement("
            ALTER TABLE id_card_requests 
            MODIFY COLUMN status VARCHAR(30) DEFAULT 'pending'
        ");

        DB::statement("
            ALTER TABLE id_card_requests 
            MODIFY COLUMN payment_status VARCHAR(20) DEFAULT 'pending'
        ");
    }
};
