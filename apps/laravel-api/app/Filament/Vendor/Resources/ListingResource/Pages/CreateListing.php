<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\ListingResource\Pages;

use App\Enums\ListingStatus;
use App\Filament\Vendor\Resources\AvailabilityRuleResource;
use App\Filament\Vendor\Resources\ListingResource;
use App\Models\AvailabilityRule;
use Filament\Actions;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\Translatable;
use Illuminate\Support\Str;

class CreateListing extends CreateRecord
{
    use Translatable;

    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\Action::make('saveDraft')
                ->label('Save Draft')
                ->icon('heroicon-o-bookmark')
                ->color('gray')
                ->action(function () {
                    $this->saveDraft();
                })
                ->extraAttributes(['class' => 'filament-button-outlined']),
        ];
    }

    protected function saveDraft(): void
    {
        $data = $this->form->getState();

        // Minimal validation for draft
        if (empty($data['service_type'])) {
            Notification::make()
                ->title('Service type is required')
                ->body('Please select Tour or Event before saving.')
                ->danger()
                ->send();

            return;
        }

        // Generate slug if missing
        if (empty($data['slug']) && ! empty($data['title']['en'])) {
            $data['slug'] = Str::slug($data['title']['en']) . '-' . Str::random(6);
        } elseif (empty($data['slug'])) {
            $data['slug'] = 'draft-' . Str::random(10);
        }

        // Set defaults for required fields if empty
        $data['title'] = $data['title'] ?? ['en' => 'Untitled Draft'];
        $data['summary'] = $data['summary'] ?? ['en' => ''];
        $data['description'] = $data['description'] ?? ['en' => ''];
        $data['vendor_id'] = auth()->id();
        $data['status'] = ListingStatus::DRAFT->value;

        // Ensure location_id is null if not set (not 0 or empty string)
        if (empty($data['location_id'])) {
            $data['location_id'] = null;
        }

        // Set default pricing if empty
        if (empty($data['pricing']['base']) && empty($data['pricing']['tnd_price'])) {
            $data['pricing'] = [
                'base' => 0,
                'currency' => 'TND',
                'tnd_price' => null,
                'eur_price' => null,
            ];
        }

        // Set default group sizes if empty
        $data['min_group_size'] = $data['min_group_size'] ?? 1;
        $data['max_group_size'] = $data['max_group_size'] ?? 10;

        // Clean up the data
        $data = $this->mutateFormDataBeforeCreate($data);

        // Create the listing
        $record = $this->getModel()::create($data);

        Notification::make()
            ->title('Draft saved!')
            ->body('You can continue editing this listing later.')
            ->success()
            ->send();

        $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['vendor_id'] = auth()->id();
        $data['status'] = ListingStatus::DRAFT->value;

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

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Listing created successfully! It is now in Draft status.';
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        $quickRules = $data['_quick_availability_rules'] ?? [];
        $skipAvailability = $data['_skip_availability'] ?? false;

        if (! empty($quickRules)) {
            $createdCount = 0;

            foreach ($quickRules as $ruleData) {
                AvailabilityRule::create([
                    'listing_id' => $this->record->id,
                    'rule_type' => $ruleData['rule_type'],
                    'days_of_week' => $ruleData['days_of_week'] ?? null,
                    'start_time' => $ruleData['start_time'],
                    'end_time' => $ruleData['end_time'],
                    'capacity' => $ruleData['capacity'],
                    'is_active' => true,
                ]);
                $createdCount++;
            }

            Notification::make()
                ->success()
                ->title('Availability Rules Created')
                ->body("Created {$createdCount} availability " . ($createdCount === 1 ? 'rule' : 'rules') . ' for your listing.')
                ->send();
        } elseif ($skipAvailability) {
            Notification::make()
                ->warning()
                ->title('Remember to Add Availability')
                ->body('Your listing has been created, but you need to add availability rules before it can be published.')
                ->persistent()
                ->actions([
                    NotificationAction::make('add_availability')
                        ->label('Add Availability Now')
                        ->url(AvailabilityRuleResource::getUrl('create', ['listing_id' => $this->record->id]))
                        ->button(),
                ])
                ->send();
        }
    }
}
