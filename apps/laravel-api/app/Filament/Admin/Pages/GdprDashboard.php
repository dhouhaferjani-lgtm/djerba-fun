<?php

namespace App\Filament\Admin\Pages;

use App\Models\Consent;
use App\Models\DataDeletionRequest;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class GdprDashboard extends Page
{
    protected static ?string $navigationIcon = null;

    protected static string $view = 'filament.admin.pages.gdpr-dashboard';

    protected static ?string $navigationGroup = 'Compliance';

    protected static ?string $navigationLabel = 'GDPR Dashboard';

    protected static ?string $title = 'GDPR Compliance Dashboard';

    protected static ?int $navigationSort = 100;

    public function getStats(): array
    {
        $pendingDeletions = DataDeletionRequest::where('status', DataDeletionRequest::STATUS_PENDING)->count();
        $processingDeletions = DataDeletionRequest::where('status', DataDeletionRequest::STATUS_PROCESSING)->count();
        $completedDeletions = DataDeletionRequest::where('status', DataDeletionRequest::STATUS_COMPLETED)->count();
        $totalDeletions = DataDeletionRequest::count();

        $totalConsents = Consent::count();
        $activeConsents = Consent::where('granted', true)->whereNull('revoked_at')->count();
        $marketingConsents = Consent::where('consent_type', Consent::TYPE_MARKETING)
            ->where('granted', true)
            ->whereNull('revoked_at')
            ->count();

        return [
            [
                'label' => 'Pending Deletion Requests',
                'value' => $pendingDeletions,
                'color' => $pendingDeletions > 0 ? 'warning' : 'success',
                'icon' => 'heroicon-o-clock',
            ],
            [
                'label' => 'Processing',
                'value' => $processingDeletions,
                'color' => 'info',
                'icon' => 'heroicon-o-cog-6-tooth',
            ],
            [
                'label' => 'Completed Deletions',
                'value' => $completedDeletions,
                'color' => 'success',
                'icon' => 'heroicon-o-check-circle',
            ],
            [
                'label' => 'Total Deletion Requests',
                'value' => $totalDeletions,
                'color' => 'gray',
                'icon' => 'heroicon-o-document-text',
            ],
            [
                'label' => 'Total Consents',
                'value' => $totalConsents,
                'color' => 'primary',
                'icon' => 'heroicon-o-hand-thumb-up',
            ],
            [
                'label' => 'Active Consents',
                'value' => $activeConsents,
                'color' => 'success',
                'icon' => 'heroicon-o-check',
            ],
            [
                'label' => 'Marketing Opt-ins',
                'value' => $marketingConsents,
                'color' => 'info',
                'icon' => 'heroicon-o-megaphone',
            ],
        ];
    }

    public function getConsentBreakdown(): array
    {
        return Consent::select('consent_type', DB::raw('count(*) as total'))
            ->where('granted', true)
            ->whereNull('revoked_at')
            ->groupBy('consent_type')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->consent_type => $item->total])
            ->toArray();
    }

    public function getRecentDeletionRequests(): array
    {
        return DataDeletionRequest::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($request) => [
                'id' => $request->id,
                'email' => $request->email,
                'status' => $request->status,
                'reason' => $request->reason,
                'requested_at' => $request->requested_at->format('M d, Y H:i'),
                'status_color' => match ($request->status) {
                    DataDeletionRequest::STATUS_PENDING => 'warning',
                    DataDeletionRequest::STATUS_PROCESSING => 'info',
                    DataDeletionRequest::STATUS_COMPLETED => 'success',
                    DataDeletionRequest::STATUS_REJECTED => 'danger',
                    default => 'gray',
                },
            ])
            ->toArray();
    }

    public function getDataRetentionStatus(): array
    {
        // Calculate data that should be cleaned up based on retention policies
        $abandonedHolds = DB::table('booking_holds')
            ->where('created_at', '<', now()->subDays(30))
            ->count();

        $oldCancelledBookings = DB::table('bookings')
            ->where('status', 'cancelled')
            ->where('created_at', '<', now()->subYears(2))
            ->count();

        $oldSessions = DB::table('sessions')
            ->where('last_activity', '<', now()->subDays(90)->timestamp)
            ->count();

        return [
            [
                'label' => 'Abandoned Holds (>30 days)',
                'count' => $abandonedHolds,
                'action' => 'Should be deleted',
            ],
            [
                'label' => 'Old Cancelled Bookings (>2 years)',
                'count' => $oldCancelledBookings,
                'action' => 'Should be anonymized',
            ],
            [
                'label' => 'Expired Sessions (>90 days)',
                'count' => $oldSessions,
                'action' => 'Should be deleted',
            ],
        ];
    }
}
