<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Show-duration toggle on availability rules.
 *
 * Vendor opt-in flag that the customer-facing slot picker reads to decide
 * whether to render each slot's duration ("1h", "1h 30min", "3h") next to
 * the time range. Default false so every existing rule renders exactly as
 * today after the migration — zero behaviour change for in-flight listings.
 *
 * Lives on the rule (not on availability_slots) because it's a per-rule
 * vendor preference, not a per-slot snapshot. The Resource resolves it via
 * the slot's availabilityRule relation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('availability_rules', function (Blueprint $table): void {
            $table->boolean('show_duration')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('availability_rules', function (Blueprint $table): void {
            $table->dropColumn('show_duration');
        });
    }
};
