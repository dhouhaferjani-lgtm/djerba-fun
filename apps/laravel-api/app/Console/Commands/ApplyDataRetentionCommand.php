<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\BookingHold;
use App\Models\Consent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplyDataRetentionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gdpr:apply-retention
                            {--dry-run : Preview what would be deleted without making changes}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply GDPR data retention policies to clean up old data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');

        $this->info('GDPR Data Retention Policy Application');
        $this->info('======================================');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be modified');
        }

        $stats = [
            'abandoned_holds' => 0,
            'expired_sessions' => 0,
            'old_consents' => 0,
            'anonymized_bookings' => 0,
        ];

        // 1. Delete abandoned booking holds (>30 days old)
        $this->info("\n1. Abandoned Booking Holds (>30 days)");
        $abandonedHolds = BookingHold::where('created_at', '<', now()->subDays(30))->count();
        $stats['abandoned_holds'] = $abandonedHolds;
        $this->line("   Found: {$abandonedHolds} holds");

        if ($abandonedHolds > 0 && !$isDryRun) {
            if ($isForced || $this->confirm('Delete these holds?', true)) {
                BookingHold::where('created_at', '<', now()->subDays(30))->delete();
                $this->info("   Deleted: {$abandonedHolds} holds");
                Log::info("GDPR retention: Deleted {$abandonedHolds} abandoned booking holds");
            }
        }

        // 2. Delete expired sessions (>90 days old)
        $this->info("\n2. Expired Sessions (>90 days)");
        $expiredSessions = DB::table('sessions')
            ->where('last_activity', '<', now()->subDays(90)->timestamp)
            ->count();
        $stats['expired_sessions'] = $expiredSessions;
        $this->line("   Found: {$expiredSessions} sessions");

        if ($expiredSessions > 0 && !$isDryRun) {
            if ($isForced || $this->confirm('Delete these sessions?', true)) {
                $deleted = DB::table('sessions')
                    ->where('last_activity', '<', now()->subDays(90)->timestamp)
                    ->delete();
                $this->info("   Deleted: {$deleted} sessions");
                Log::info("GDPR retention: Deleted {$deleted} expired sessions");
            }
        }

        // 3. Clean up very old revoked consents (>3 years)
        $this->info("\n3. Old Revoked Consents (>3 years)");
        $oldConsents = Consent::whereNotNull('revoked_at')
            ->where('revoked_at', '<', now()->subYears(3))
            ->count();
        $stats['old_consents'] = $oldConsents;
        $this->line("   Found: {$oldConsents} old revoked consents");

        if ($oldConsents > 0 && !$isDryRun) {
            if ($isForced || $this->confirm('Delete these consents?', true)) {
                $deleted = Consent::whereNotNull('revoked_at')
                    ->where('revoked_at', '<', now()->subYears(3))
                    ->delete();
                $this->info("   Deleted: {$deleted} consents");
                Log::info("GDPR retention: Deleted {$deleted} old revoked consents");
            }
        }

        // 4. Anonymize old cancelled bookings (>2 years)
        $this->info("\n4. Old Cancelled Bookings (>2 years) - Anonymization");
        $oldCancelledBookings = Booking::where('status', 'cancelled')
            ->where('created_at', '<', now()->subYears(2))
            ->whereNotNull('billing_contact')
            ->count();
        $stats['anonymized_bookings'] = $oldCancelledBookings;
        $this->line("   Found: {$oldCancelledBookings} bookings to anonymize");

        if ($oldCancelledBookings > 0 && !$isDryRun) {
            if ($isForced || $this->confirm('Anonymize these bookings?', true)) {
                $bookings = Booking::where('status', 'cancelled')
                    ->where('created_at', '<', now()->subYears(2))
                    ->whereNotNull('billing_contact')
                    ->get();

                foreach ($bookings as $booking) {
                    $booking->update([
                        'billing_contact' => [
                            'first_name' => 'REDACTED',
                            'last_name' => 'REDACTED',
                            'email' => 'redacted@example.com',
                            'phone' => null,
                        ],
                    ]);

                    // Also anonymize participants
                    $booking->participants()->update([
                        'first_name' => 'REDACTED',
                        'last_name' => 'REDACTED',
                        'email' => null,
                        'phone' => null,
                    ]);
                }

                $this->info("   Anonymized: {$oldCancelledBookings} bookings");
                Log::info("GDPR retention: Anonymized {$oldCancelledBookings} old cancelled bookings");
            }
        }

        // Summary
        $this->info("\n======================================");
        $this->info('Summary:');
        $this->table(
            ['Category', 'Count', 'Action'],
            [
                ['Abandoned Holds', $stats['abandoned_holds'], 'Deleted'],
                ['Expired Sessions', $stats['expired_sessions'], 'Deleted'],
                ['Old Revoked Consents', $stats['old_consents'], 'Deleted'],
                ['Old Cancelled Bookings', $stats['anonymized_bookings'], 'Anonymized'],
            ]
        );

        if ($isDryRun) {
            $this->warn("\nThis was a DRY RUN. Run without --dry-run to apply changes.");
        } else {
            $this->info("\nData retention policies applied successfully.");
        }

        return Command::SUCCESS;
    }
}
