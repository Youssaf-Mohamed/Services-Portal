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
        // Update transport_subscriptions
        Schema::table('transport_subscriptions', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('slot_id')->constrained('transport_plans')->onDelete('restrict');
            $table->json('selected_days')->nullable()->after('plan_id'); // array of day keys: ['saturday', 'sunday', ...]
            $table->renameColumn('start_date', 'starts_at');
            $table->renameColumn('end_date', 'ends_at');
            
            $table->index('plan_id');
        });

        // Update transport_subscription_requests
        Schema::table('transport_subscription_requests', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('slot_id')->constrained('transport_plans')->onDelete('restrict');
            $table->json('selected_days')->nullable()->after('plan_id');
            
            $table->index('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transport_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'selected_days']);
            $table->renameColumn('starts_at', 'start_date');
            $table->renameColumn('ends_at', 'end_date');
        });

        Schema::table('transport_subscription_requests', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'selected_days']);
        });
    }
};
