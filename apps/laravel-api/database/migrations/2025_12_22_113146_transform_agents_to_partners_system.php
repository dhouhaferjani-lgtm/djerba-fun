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
        // Drop foreign key in agent_audit_logs BEFORE renaming tables
        Schema::table('agent_audit_logs', function (Blueprint $table) {
            $table->dropForeign(['agent_id']);
        });

        // Rename agents table to partners
        Schema::rename('agents', 'partners');

        // Rename agent_audit_logs table to partner_audit_logs
        Schema::rename('agent_audit_logs', 'partner_audit_logs');

        // Rename column and re-create foreign key
        Schema::table('partner_audit_logs', function (Blueprint $table) {
            $table->renameColumn('agent_id', 'partner_id');
        });

        Schema::table('partner_audit_logs', function (Blueprint $table) {
            $table->foreign('partner_id')->references('id')->on('partners')->onDelete('cascade');
        });

        // Add new columns to partners table
        Schema::table('partners', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('company_name')->after('name');
            $table->string('company_type')->nullable()->after('company_name');
            $table->text('description')->nullable()->after('company_type');
            $table->string('website_url')->nullable()->after('description');
            $table->string('contact_email')->nullable()->after('website_url');
            $table->string('contact_phone')->nullable()->after('contact_email');
            $table->json('ip_whitelist')->nullable()->after('metadata');
            $table->string('webhook_url')->nullable()->after('ip_whitelist');
            $table->string('webhook_secret')->nullable()->after('webhook_url');
            $table->string('kyc_status')->default('pending')->after('is_active');
            $table->string('partner_tier')->default('standard')->after('kyc_status');
            $table->boolean('sandbox_mode')->default(true)->after('partner_tier');
            $table->timestamp('api_key_expires_at')->nullable()->after('last_used_at');
            $table->timestamp('approved_at')->nullable()->after('api_key_expires_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();

            $table->index('user_id');
            $table->index('kyc_status');
            $table->index('partner_tier');
        });

        // Add partner_metadata to bookings table (this column doesn't exist yet)
        Schema::table('bookings', function (Blueprint $table) {
            $table->json('partner_metadata')->nullable()->after('extras');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove partner_metadata from bookings
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('partner_metadata');
        });

        // Remove new columns from partners
        Schema::table('partners', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['kyc_status']);
            $table->dropIndex(['partner_tier']);
            $table->dropColumn([
                'user_id',
                'company_name',
                'company_type',
                'description',
                'website_url',
                'contact_email',
                'contact_phone',
                'ip_whitelist',
                'webhook_url',
                'webhook_secret',
                'kyc_status',
                'partner_tier',
                'sandbox_mode',
                'api_key_expires_at',
                'approved_at',
                'approved_by',
            ]);
        });

        // Drop foreign key from partner_audit_logs
        Schema::table('partner_audit_logs', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
        });

        // Rename column back
        Schema::table('partner_audit_logs', function (Blueprint $table) {
            $table->renameColumn('partner_id', 'agent_id');
        });

        // Rename tables back
        Schema::rename('partner_audit_logs', 'agent_audit_logs');
        Schema::rename('partners', 'agents');

        // Re-create foreign key
        Schema::table('agent_audit_logs', function (Blueprint $table) {
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
        });
    }
};
