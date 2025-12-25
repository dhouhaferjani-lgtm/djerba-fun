<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\ListingResource\Pages;

use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Filament\Vendor\Resources\ListingResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\EditRecord\Concerns\Translatable;

class EditListing extends EditRecord
{
    use Translatable;

    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\Action::make('submit_for_review')
                ->label('Submit for Review')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Submit for Review')
                ->modalDescription('Your listing will be reviewed by our team. Please ensure all required fields are filled in.')
                ->visible(fn () => $this->record->status === ListingStatus::DRAFT)
                ->action(function () {
                    $record = $this->record;
                    $errors = [];

                    // Basic info
                    if (!$record->location_id) {
                        $errors[] = 'Location is required';
                    }
                    if (empty($record->getTranslation('title', 'en'))) {
                        $errors[] = 'English title is required';
                    }
                    if (empty($record->getTranslation('summary', 'en'))) {
                        $errors[] = 'English summary is required';
                    }
                    if (empty($record->getTranslation('description', 'en'))) {
                        $errors[] = 'English description is required';
                    }

                    // Service-specific validation
                    if ($record->service_type === ServiceType::TOUR) {
                        if (empty($record->duration['value'])) {
                            $errors[] = 'Duration is required for tours';
                        }
                    } elseif ($record->service_type === ServiceType::EVENT) {
                        if (empty($record->event_type)) {
                            $errors[] = 'Event type is required';
                        }
                        if (empty($record->start_date)) {
                            $errors[] = 'Start date is required for events';
                        }
                        if (empty($record->venue['name'])) {
                            $errors[] = 'Venue name is required for events';
                        }
                    }

                    // Meeting point
                    if (empty($record->meeting_point['address'])) {
                        $errors[] = 'Meeting point address is required';
                    }

                    // Pricing - Person Types
                    if (empty($record->pricing['person_types'])) {
                        $errors[] = 'At least one person type is required';
                    } else {
                        // Validate each person type has prices
                        $hasValidPricing = false;
                        foreach ($record->pricing['person_types'] as $personType) {
                            if (!empty($personType['tnd_price']) && !empty($personType['eur_price'])) {
                                $hasValidPricing = true;
                                break;
                            }
                        }
                        if (!$hasValidPricing) {
                            $errors[] = 'At least one person type must have both TND and EUR prices';
                        }
                    }

                    // Group size
                    if (empty($record->max_group_size)) {
                        $errors[] = 'Maximum group size is required';
                    }

                    // Cancellation policy
                    if (empty($record->cancellation_policy['type'])) {
                        $errors[] = 'Cancellation policy is required';
                    }

                    if (!empty($errors)) {
                        Notification::make()
                            ->title('Cannot Submit for Review')
                            ->body('Please fix the following issues:' . "\n• " . implode("\n• ", $errors))
                            ->danger()
                            ->persistent()
                            ->send();
                        return;
                    }

                    $record->update(['status' => ListingStatus::PENDING_REVIEW]);

                    Notification::make()
                        ->title('Submitted for Review')
                        ->body('Your listing has been submitted and will be reviewed by our team.')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ensure nested JSON data is properly structured for the form
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure translations are properly formatted
        if (isset($data['title']) && is_array($data['title'])) {
            $data['title'] = array_filter($data['title']);
        }

        if (isset($data['summary']) && is_array($data['summary'])) {
            $data['summary'] = array_filter($data['summary']);
        }

        if (isset($data['description']) && is_array($data['description'])) {
            $data['description'] = array_filter($data['description']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
