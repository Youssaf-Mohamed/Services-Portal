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
        // Add description column to roles table
        Schema::table('roles', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
        });

        // Add module, description, and sort_order columns to permissions table
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('module', 50)->nullable()->after('name')->index();
            $table->text('description')->nullable()->after('module');
            $table->unsignedTinyInteger('sort_order')->default(0)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex(['module']);
            $table->dropColumn(['module', 'description', 'sort_order']);
        });
    }
};
