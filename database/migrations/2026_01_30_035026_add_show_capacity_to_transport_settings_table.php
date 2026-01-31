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
        Schema::table('transport_settings', function (Blueprint $table) {
            $table->boolean('show_capacity')->default(true)->after('weeks_in_term');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transport_settings', function (Blueprint $table) {
            $table->dropColumn('show_capacity');
        });
    }
};
