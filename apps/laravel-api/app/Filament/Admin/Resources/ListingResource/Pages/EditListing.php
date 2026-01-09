<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ListingResource\Pages;

use App\Enums\ListingStatus;
use App\Filament\Admin\Resources\ListingResource;
use App\Filament\Vendor\Resources\ListingResource as VendorListingResource;
use App\Mail\ListingPublishFailedMail;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class EditListing extends EditRecord
{
    protected static string $resource = ListingResource::class;

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
        // CRITICAL FIX: Remove empty arrays for disabled fields
        // Filament sends empty arrays for disabled fields which would overwrite existing data
        $disabledFields = ['title', 'summary', 'description', 'pricing', 'slug', 'service_type', 'vendor_id',
                          'min_group_size', 'max_group_size'];
        foreach ($disabledFields as $field) {
            if (isset($data[$field]) && (is_array($data[$field]) && empty($data[$field]))) {
                unset($data[$field]);
            }
        }

        // Check if trying to publish and validate
        if (isset($data['status']) && $data['status'] === ListingStatus::PUBLISHED->value) {
            $errors = [];

            // Check title from existing record (since title field is disabled)
            $title = $this->record->getTranslation('title', 'en');
            if (empty($title) || (is_array($title) && empty(array_filter($title)))) {
                $errors[] = 'English title is required';
            }

            // Check summary from existing record
            $summary = $this->record->getTranslation('summary', 'en');
            if (empty($summary) || (is_array($summary) && empty(array_filter($summary)))) {
                $errors[] = 'English summary is required';
            }

            // Check pricing from existing record
            $pricing = $this->record->pricing;
            $hasNewFormatPricing = ! empty($pricing['person_types']) || ! empty($pricing['personTypes']);
            $hasOldFormatPricing = ! empty($pricing['base_price']) || ! empty($pricing['tnd_price']) || ! empty($pricing['eur_price']);
            if (! $hasNewFormatPricing && ! $hasOldFormatPricing) {
                $errors[] = 'Pricing information is required';
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

        // Send database notification (appears in vendor panel)
        Notification::make()
            ->title('Action Required: Listing Cannot Be Published')
            ->body("Your listing \"{$listingTitle}\" cannot be published. Missing: " . implode(', ', $errors))
            ->warning()
            ->actions([
                \Filament\Notifications\Actions\Action::make('edit')
                    ->label('Edit Listing')
                    ->url($editUrl)
                    ->button(),
            ])
            ->sendToDatabase($vendor);

        // Send email notification as backup (pass edit URL for consistency)
        Mail::to($vendor->email)->queue(new ListingPublishFailedMail(
            $this->record,
            $vendor,
            $errors,
            $editUrl
        ));
    }
}
