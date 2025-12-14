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
        Schema::table('users', function (Blueprint $table) {
            // Remove old 'name' column
            $table->dropColumn('name');

            // Add new columns
            $table->string('role')->default('traveler')->index();
            $table->string('status')->default('active')->index();
            $table->string('display_name');
            $table->string('avatar_url')->nullable();
            $table->uuid('uuid')->after('id')->unique();

            // Add indexes
            $table->index('email_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name');
            $table->dropColumn(['role', 'status', 'display_name', 'avatar_url', 'uuid']);
        });
    }
};
