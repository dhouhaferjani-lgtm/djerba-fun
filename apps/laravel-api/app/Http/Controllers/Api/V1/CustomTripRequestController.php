<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomTripRequestRequest;
use App\Mail\CustomTripRequestConfirmationMail;
use App\Models\CustomTripRequest;
use App\Models\User;
use App\Services\EmailLogService;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CustomTripRequestController extends Controller
{
    public function __construct(
        private readonly EmailLogService $emailLogService
    ) {}

    /**
     * Store a new custom trip request.
     */
    public function store(StoreCustomTripRequestRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $customTripRequest = CustomTripRequest::create([
            'travel_start_date' => Carbon::parse($validated['travel_dates']['start']),
            'travel_end_date' => Carbon::parse($validated['travel_dates']['end']),
            'dates_flexible' => $validated['travel_dates']['flexible'] ?? false,
            'adults' => $validated['travelers']['adults'],
            'children' => $validated['travelers']['children'] ?? 0,
            'duration_days' => $validated['duration_days'],
            'interests' => $validated['interests'],
            'budget_per_person' => $validated['budget']['per_person'],
            'budget_currency' => $validated['budget']['currency'] ?? 'TND',
            'accommodation_style' => $validated['accommodation_style'],
            'travel_pace' => $validated['travel_pace'],
            'special_occasions' => $validated['special_occasions'] ?? null,
            'special_requests' => $validated['special_requests'] ?? null,
            'contact_name' => $validated['contact']['name'],
            'contact_email' => $validated['contact']['email'],
            'contact_phone' => $validated['contact']['phone'],
            'contact_whatsapp' => $validated['contact']['whatsapp'] ?? null,
            'contact_country' => $validated['contact']['country'],
            'preferred_contact_method' => $validated['contact']['preferred_method'],
            'newsletter_consent' => $validated['newsletter_consent'] ?? false,
            'locale' => $validated['locale'] ?? 'en',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Send notification to all admin users
        $this->notifyAdmins($customTripRequest);

        // Send confirmation email to customer
        $this->sendConfirmationEmail($customTripRequest);

        return response()->json([
            'data' => [
                'id' => $customTripRequest->id,
                'reference' => $customTripRequest->reference,
                'status' => $customTripRequest->status,
                'created_at' => $customTripRequest->created_at->toIso8601String(),
            ],
            'message' => 'Custom trip request submitted successfully',
        ], 201);
    }

    /**
     * Send notification to all admin users about the new custom trip request.
     */
    private function notifyAdmins(CustomTripRequest $customTripRequest): void
    {
        try {
            $admins = User::where('role', UserRole::ADMIN)->get();

            $travelerName = $customTripRequest->contact_name;
            $reference = $customTripRequest->reference;
            $travelDates = $customTripRequest->travel_start_date->format('M d') . ' - ' . $customTripRequest->travel_end_date->format('M d, Y');

            foreach ($admins as $admin) {
                Notification::make()
                    ->title('New Custom Trip Request')
                    ->icon('heroicon-o-paper-airplane')
                    ->body("New request from {$travelerName} ({$reference}) for {$travelDates}")
                    ->actions([
                        NotificationAction::make('view')
                            ->label('View Request')
                            ->url("/admin/custom-trip-requests/{$customTripRequest->id}")
                            ->button(),
                    ])
                    ->sendToDatabase($admin);
            }
        } catch (\Throwable $e) {
            // Don't let notification errors break the request submission
            Log::warning('Failed to send custom trip request notification', [
                'error' => $e->getMessage(),
                'request_id' => $customTripRequest->id,
            ]);
        }
    }

    /**
     * Send confirmation email to the customer.
     */
    private function sendConfirmationEmail(CustomTripRequest $customTripRequest): void
    {
        try {
            $this->emailLogService->queue(
                $customTripRequest->contact_email,
                new CustomTripRequestConfirmationMail($customTripRequest),
                null, // No booking associated
                [
                    'name' => $customTripRequest->contact_name,
                    'phone' => $customTripRequest->contact_phone,
                ]
            );
        } catch (\Throwable $e) {
            // Don't let email errors break the request submission
            Log::warning('Failed to send custom trip request confirmation email', [
                'error' => $e->getMessage(),
                'request_id' => $customTripRequest->id,
                'email' => $customTripRequest->contact_email,
            ]);
        }
    }
}
