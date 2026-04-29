<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-time-slot price override on AvailabilityRule.
 *
 * Two structural changes:
 *
 *   1. Add a nullable `price_overrides` JSON column on availability_slots.
 *      NULL means "use the listing's pricing.person_types[]" — identical
 *      to today's behaviour. Only when set does the slot diverge from the
 *      listing.
 *
 *   2. Widen the unique key from (listing_id, date, start_time) to
 *      (listing_id, date, start_time, end_time). Without this, a vendor
 *      offering the same circuit at 1-hour AND 3-hour durations starting
 *      at the same anchor (e.g. 09:00 → 10:00 vs 09:00 → 12:00) would have
 *      the second slot silently overwrite the first when CalculateAvailabilityJob
 *      upserts. The new key is a strict superset of the old one — any data
 *      legal under the old constraint is legal under the new one, so the
 *      migration is collision-free against the existing prod dataset.
 *
 * Backward compatibility: every existing slot has price_overrides = NULL on
 * deploy, so the runtime fallback to listing pricing reproduces today's
 * behaviour bit-for-bit. No backfill required; vendors opt in by editing
 * rules.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('availability_slots', function (Blueprint $table): void {
            $table->json('price_overrides')->nullable()->after('base_price');

            $table->dropUnique(['listing_id', 'date', 'start_time']);
            $table->unique(['listing_id', 'date', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::table('availability_slots', function (Blueprint $table): void {
            $table->dropUnique(['listing_id', 'date', 'start_time', 'end_time']);
            $table->unique(['listing_id', 'date', 'start_time']);

            $table->dropColumn('price_overrides');
        });
    }
};
