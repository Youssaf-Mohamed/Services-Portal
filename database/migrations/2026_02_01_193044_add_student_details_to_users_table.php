<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('academic_id')->nullable()->after('avatar_url');
            $table->string('national_id')->nullable()->after('academic_id');
            $table->string('program_name')->nullable()->after('national_id');
            $table->string('level_name')->nullable()->after('program_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['academic_id', 'national_id', 'program_name', 'level_name']);
        });
    }
};
