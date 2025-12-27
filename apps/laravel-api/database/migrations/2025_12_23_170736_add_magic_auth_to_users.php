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
            // Allow passwordless accounts
            $table->string('password')->nullable()->change();

            // Magic link fields for authentication
            $table->string('magic_token_hash', 64)->nullable()->index()->after('password');
            $table->timestamp('magic_token_expires_at')->nullable()->after('magic_token_hash');
            $table->timestamp('magic_token_used_at')->nullable()->after('magic_token_expires_at');

            // User preferences
            $table->boolean('prefers_passwordless')->default(false)->after('magic_token_used_at');
            $table->timestamp('last_magic_login_at')->nullable()->after('prefers_passwordless');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'magic_token_hash',
                'magic_token_expires_at',
                'magic_token_used_at',
                'prefers_passwordless',
                'last_magic_login_at',
            ]);

            // Revert password to non-nullable (but skip if there are null passwords)
            // $table->string('password')->nullable(false)->change();
        });
    }
};
