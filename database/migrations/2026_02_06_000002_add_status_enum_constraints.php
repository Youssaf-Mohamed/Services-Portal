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
        DB::statement("\n            UPDATE transport_subscription_requests \n            SET status = 'pending' \n            WHERE status NOT IN ('pending', 'approved', 'rejected', 'cancelled')\n        ");

        DB::statement("\n            UPDATE transport_subscription_requests \n            SET payment_status = 'pending' \n            WHERE payment_status NOT IN ('pending', 'verified', 'flagged')\n        ");

        // Transport subscriptions
        DB::statement("\n            UPDATE transport_subscriptions \n            SET status = 'active' \n            WHERE status NOT IN ('active', 'waitlisted', 'expired', 'cancelled')\n        ");

        // ID card requests
        DB::statement("\n            UPDATE id_card_requests \n            SET status = 'pending' \n            WHERE status NOT IN ('pending', 'approved', 'rejected', 'ready_for_pickup', 'delivered', 'cancelled')\n        ");

        DB::statement("\n            UPDATE id_card_requests \n            SET payment_status = 'pending' \n            WHERE payment_status NOT IN ('pending', 'verified', 'flagged')\n        ");

        // ═══════════════════════════════════════════════════════════════
        // ENUM CONVERSION
        // ═══════════════════════════════════════════════════════════════

        // Only run enum ALTER statements on drivers that support them (MySQL, MariaDB).
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'sqlite') {
            DB::statement("\n                ALTER TABLE transport_subscription_requests \n                MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'cancelled') \n                DEFAULT 'pending' NOT NULL\n            ");

            DB::statement("\n                ALTER TABLE transport_subscription_requests \n                MODIFY COLUMN payment_status ENUM('pending', 'verified', 'flagged') \n                DEFAULT 'pending' NOT NULL\n            ");

            DB::statement("\n                ALTER TABLE transport_subscriptions \n                MODIFY COLUMN status ENUM('active', 'waitlisted', 'expired', 'cancelled') \n                DEFAULT 'active' NOT NULL\n            ");

            DB::statement("\n                ALTER TABLE id_card_requests \n                MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'ready_for_pickup', 'delivered', 'cancelled') \n                DEFAULT 'pending' NOT NULL\n            ");

            DB::statement("\n                ALTER TABLE id_card_requests \n                MODIFY COLUMN payment_status ENUM('pending', 'verified', 'flagged') \n                DEFAULT 'pending' NOT NULL\n            ");
        }
    }

    /**
     * Reverse the migrations - convert back to VARCHAR.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'sqlite') {
            DB::statement("\n                ALTER TABLE transport_subscription_requests \n                MODIFY COLUMN status VARCHAR(20) DEFAULT 'pending'\n            ");

            DB::statement("\n                ALTER TABLE transport_subscription_requests \n                MODIFY COLUMN payment_status VARCHAR(20) DEFAULT 'pending'\n            ");

            DB::statement("\n                ALTER TABLE transport_subscriptions \n                MODIFY COLUMN status VARCHAR(20) DEFAULT 'active'\n            ");

            DB::statement("\n                ALTER TABLE id_card_requests \n                MODIFY COLUMN status VARCHAR(30) DEFAULT 'pending'\n            ");

            DB::statement("\n                ALTER TABLE id_card_requests \n                MODIFY COLUMN payment_status VARCHAR(20) DEFAULT 'pending'\n            ");
        }
    }
};
