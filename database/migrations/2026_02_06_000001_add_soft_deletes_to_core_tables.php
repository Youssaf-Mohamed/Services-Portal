<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add soft deletes to core tables for safe data management.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('transport_subscription_requests', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('transport_subscriptions', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('id_card_requests', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('bus_routes', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('transport_subscription_requests', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('transport_subscriptions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('id_card_requests', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('bus_routes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
