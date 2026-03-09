<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ListingResource\Pages;

use App\Enums\ListingStatus;
use App\Filament\Admin\Resources\ListingResource;
use App\Filament\Vendor\Resources\ListingResource as VendorListingResource;
use App\Mail\ListingPublishFailedMail;
use Filament\Actions;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EditListing extends EditRecord
{
    protected static string $resource = ListingResource::class;

    protected ?string $previousStatus = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Capture old status before save for notification logic in afterSave()
        $this->previousStatus = $this->record->status->value;

        // CRITICAL FIX: Remove empty/null values for disabled fields
        // Filament sends empty arrays for disabled fields which would overwrite existing data
        $disabledFields = ['title', 'summary', 'description', 'pricing', 'slug', 'vendor_id',
                          'min_group_size', 'max_group_size'];
        foreach ($disabledFields as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                // Remove empty arrays, null values, and empty strings for disabled fields
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    unset($data[$field]);
                }
            }
        }

        // Check if trying to publish and validate
        if (isset($data['status']) && $data['status'] === ListingStatus::PUBLISHED->value) {
            $errors = [];

            // Check title - must have at least one translation (English OR French)
            $titleEn = $this->record->getTranslation('title', 'en');
            $titleFr = $this->record->getTranslation('title', 'fr');
            $hasEnTitle = ! empty($titleEn) && ! (is_array($titleEn) && empty(array_filter($titleEn)));
            $hasFrTitle = ! empty($titleFr) && ! (is_array($titleFr) && empty(array_filter($titleFr)));
            if (! $hasEnTitle && ! $hasFrTitle) {
                $errors[] = __('filament.validation.title_translation_required');
            }

            // Check summary - must have at least one translation (English OR French)
            $summaryEn = $this->record->getTranslation('summary', 'en');
            $summaryFr = $this->record->getTranslation('summary', 'fr');
            $hasEnSummary = ! empty($summaryEn) && ! (is_array($summaryEn) && empty(array_filter($summaryEn)));
            $hasFrSummary = ! empty($summaryFr) && ! (is_array($summaryFr) && empty(array_filter($summaryFr)));
            if (! $hasEnSummary && ! $hasFrSummary) {
                $errors[] = __('filament.validation.summary_translation_required');
            }

            // Check pricing based on service type
            if ($this->record->service_type === \App\Enums\ServiceType::ACCOMMODATION) {
                // Accommodations use nightly pricing (direct columns)
                $hasNightlyPricing = ! empty($this->record->nightly_price_tnd) || ! empty($this->record->nightly_price_eur);
                if (! $hasNightlyPricing) {
                    $errors[] = 'Nightly pricing (TND or EUR) is required for accommodations';
                }
            } else {
                // Tours/Events/Nautical use person type pricing (JSON)
                $pricing = $this->record->pricing;
                $hasNewFormatPricing = ! empty($pricing['person_types']) || ! empty($pricing['personTypes']);
                $hasOldFormatPricing = ! empty($pricing['base_price']) || ! empty($pricing['tnd_price']) || ! empty($pricing['eur_price']);
                if (! $hasNewFormatPricing && ! $hasOldFormatPricing) {
                    $errors[] = 'Pricing information is required';
                }
            }

            // Check location - use form data if available, else record
            $locationId = $data['location_id'] ?? $this->record->location_id;
            if (empty($locationId)) {
                $errors[] = 'Location is required';
            }

            if (! empty($errors)) {
                Notification::make()
                    ->title('Cannot Publish Listing')
                    ->body('Missing required fields: ' . implode(', ', $errors))
                    ->danger()
                    ->persistent()
                    ->send();

                // Notify vendor (with rate limiting to prevent spam)
                $this->notifyVendorOfPublishFailure($errors);

                // Revert status to prevent publish
                $data['status'] = $this->record->getOriginal('status');
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        if ($this->previousStatus === null) {
            return;
        }

        $newStatus = $this->record->status;
        $oldStatus = ListingStatus::from($this->previousStatus);

        if ($oldStatus === $newStatus) {
            return;
        }

        // Notify vendor for ANY transition to PUBLISHED or REJECTED (not just from PENDING_REVIEW)
        if ($newStatus === ListingStatus::PUBLISHED) {
            $this->notifyVendorOfApproval();
        } elseif ($newStatus === ListingStatus::REJECTED) {
            $this->notifyVendorOfRejection();
        }
    }

    /**
     * Notify vendor when their listing cannot be published due to missing fields.
     * Uses rate limiting to prevent notification spam (max 1 per 5 minutes per listing).
     *
     * @param  array<string>  $errors  List of validation errors
     */
    protected function notifyVendorOfPublishFailure(array $errors): void
    {
        $vendor = $this->record->vendor;
        if (! $vendor) {
            return;
        }

        // Rate limit: max 1 notification per listing per 5 minutes
        $cacheKey = "listing_publish_failed_notification:{$this->record->id}";
        if (Cache::has($cacheKey)) {
            return;
        }

        // Set cache to prevent spam (5 minutes TTL)
        Cache::put($cacheKey, true, now()->addMinutes(5));

        $listingTitle = $this->record->getTranslation('title', 'en') ?: 'Untitled Listing';
        if (is_array($listingTitle)) {
            $listingTitle = $listingTitle['en'] ?? reset($listingTitle) ?: 'Untitled Listing';
        }

        // Generate correct vendor panel URL using Filament's URL generator
        $editUrl = VendorListingResource::getUrl('edit', ['record' => $this->record], panel: 'vendor');

        // Send database notification (appears in vendor panel) via direct Eloquent insert
        $vendor->notifications()->create([
            'id' => Str::uuid()->toString(),
            'type' => \Filament\Notifications\DatabaseNotification::class,
            'data' => Notification::make()
                ->title('Action Required: Listing Cannot Be Published')
                ->body("Your listing \"{$listingTitle}\" cannot be published. Missing: " . implode(', ', $errors))
                ->warning()
                ->actions([
                    NotificationAction::make('edit')
                        ->label('Edit Listing')
                        ->url($editUrl)
                        ->button(),
                ])
                ->getDatabaseMessage(),
        ]);

        // Send email notification as backup (pass edit URL for consistency)
        Mail::to($vendor->email)->queue(new ListingPublishFailedMail(
            $this->record,
            $vendor,
            $errors,
            $editUrl
        ));
    }

    protected function notifyVendorOfApproval(): void
    {
        try {
            $vendor = $this->record->vendor;
            if (! $vendor) {
                return;
            }

            $listingTitle = $this->record->getTranslation('title', 'en')
                ?: $this->record->getTranslation('title', 'fr')
                ?: 'Untitled';
            if (is_array($listingTitle)) {
                $listingTitle = reset($listingTitle) ?: 'Untitled';
            }

            $viewUrl = VendorListingResource::getUrl('view', ['record' => $this->record], panel: 'vendor');
            $vendor->notifications()->create([
                'id' => Str::uuid()->toString(),
                'type' => \Filament\Notifications\DatabaseNotification::class,
                'data' => Notification::make()
                    ->title('Listing Approved')
                    ->icon('heroicon-o-check-circle')
                    ->body("Your listing \"{$listingTitle}\" has been approved and is now published!")
                    ->success()
                    ->actions([
                        NotificationAction::make('view')
                            ->label('View Listing')
                            ->url($viewUrl)
                            ->button(),
                    ])
                    ->getDatabaseMessage(),
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to send approval notification via form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function notifyVendorOfRejection(): void
    {
        try {
            $vendor = $this->record->vendor;
            if (! $vendor) {
                return;
            }

            $listingTitle = $this->record->getTranslation('title', 'en')
                ?: $this->record->getTranslation('title', 'fr')
                ?: 'Untitled';
            if (is_array($listingTitle)) {
                $listingTitle = reset($listingTitle) ?: 'Untitled';
            }

            $editUrl = VendorListingResource::getUrl('edit', ['record' => $this->record], panel: 'vendor');
            $vendor->notifications()->create([
                'id' => Str::uuid()->toString(),
                'type' => \Filament\Notifications\DatabaseNotification::class,
                'data' => Notification::make()
                    ->title('Listing Rejected')
                    ->icon('heroicon-o-x-circle')
                    ->body("Your listing \"{$listingTitle}\" has been rejected. Please review and resubmit.")
                    ->warning()
                    ->actions([
                        NotificationAction::make('edit')
                            ->label('Edit Listing')
                            ->url($editUrl)
                            ->button(),
                    ])
                    ->getDatabaseMessage(),
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to send rejection notification via form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
