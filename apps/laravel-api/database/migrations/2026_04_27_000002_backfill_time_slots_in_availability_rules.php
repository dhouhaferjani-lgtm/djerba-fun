<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill the new `time_slots` JSON column for every rule that already has
     * legacy `start_time` / `end_time` / `capacity` values. Each existing rule
     * becomes a single-entry time_slots array, preserving observable behaviour
     * after CalculateAvailabilityJob switches to looping `getEffectiveTimeSlots()`.
     *
     * Idempotent: rules that already have a populated time_slots JSON are skipped.
     * Reversible: down() clears time_slots so rules fall back to legacy columns.
     */
    public function up(): void
    {
        DB::table('availability_rules')
            ->whereNull('time_slots')
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->orderBy('id')
            ->chunkById(500, function ($rules) {
                foreach ($rules as $rule) {
                    DB::table('availability_rules')
                        ->where('id', $rule->id)
                        ->update([
                            'time_slots' => json_encode([[
                                'start_time' => self::normaliseTime($rule->start_time),
                                'end_time' => self::normaliseTime($rule->end_time),
                                'capacity' => (int) ($rule->capacity ?? 1),
                            ]]),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Clear backfilled values; legacy start_time/end_time/capacity remain intact.
        DB::table('availability_rules')->update(['time_slots' => null]);
    }

    /**
     * Normalise a stored time value to "HH:MM:SS" regardless of whether the
     * driver returned a TIME, DATETIME or string column.
     */
    private static function normaliseTime(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i:s');
        }

        $str = (string) $value;

        if (preg_match('/(\d{2}:\d{2}:\d{2})/', $str, $m)) {
            return $m[1];
        }

        if (preg_match('/(\d{2}:\d{2})/', $str, $m)) {
            return $m[1] . ':00';
        }

        return '00:00:00';
    }
};
