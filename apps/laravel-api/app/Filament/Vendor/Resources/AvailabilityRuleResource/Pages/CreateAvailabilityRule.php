<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\AvailabilityRuleResource\Pages;

use App\Filament\Vendor\Resources\AvailabilityRuleResource;
use App\Models\Listing;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAvailabilityRule extends CreateRecord
{
    protected static string $resource = AvailabilityRuleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Verify the listing belongs to the current vendor
        $listing = Listing::find($data['listing_id']);

        if (! $listing || $listing->vendor_id !== auth()->id()) {
            Notification::make()
                ->title('Error')
                ->body('You can only create availability rules for your own listings.')
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Availability Rule Created')
            ->body('Your availability rule has been created. Slots will be generated automatically.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null; // Using custom notification in afterCreate
    }
}
