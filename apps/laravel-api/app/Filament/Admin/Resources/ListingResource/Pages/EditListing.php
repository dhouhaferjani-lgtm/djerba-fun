<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ListingResource\Pages;

use App\Enums\ListingStatus;
use App\Filament\Admin\Resources\ListingResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

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
}
