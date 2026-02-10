<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * IMPORTANT: This migration must run BEFORE any code changes that block
     * unverified users from logging in. It grandfathers all existing users
     * by setting their email_verified_at to now().
     */
    public function up(): void
    {
        // Step 1: Grandfather all existing users — set email_verified_at for those who don't have it
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);

        // Step 2: Add dedicated verification token columns (separate from magic_token_* to avoid conflicts)
        Schema::table('users', function (Blueprint $table) {
            $table->string('verification_token_hash')->nullable()->after('magic_token_used_at');
            $table->timestamp('verification_token_expires_at')->nullable()->after('verification_token_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['verification_token_hash', 'verification_token_expires_at']);
        });

        // Note: We do NOT reset email_verified_at on rollback — that data is lost
    }
};
