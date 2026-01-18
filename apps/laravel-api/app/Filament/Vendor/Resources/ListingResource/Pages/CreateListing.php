<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\ListingResource\Pages;

use App\Enums\ListingStatus;
use App\Filament\Vendor\Resources\AvailabilityRuleResource;
use App\Filament\Vendor\Resources\ListingResource;
use App\Models\AvailabilityRule;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
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

        // Ensure location_id is null if not set (not 0 or empty string)
        if (empty($data['location_id'])) {
            $data['location_id'] = null;
        }

        // Use the same mutation logic as normal create
        $data = $this->mutateFormDataBeforeCreate($data);

        // Set default title if still empty after mutation
        if (empty($data['title']) || $data['title'] === []) {
            // Get active locale for the default title
            $activeLocale = $this->getActiveFormsLocale() ?? 'en';
            $data['title'] = [$activeLocale => 'Untitled Draft'];
        }

        // Ensure title is properly formatted (not double-nested)
        if (is_array($data['title'])) {
            $cleaned = [];
            foreach (['en', 'fr'] as $locale) {
                if (isset($data['title'][$locale])) {
                    $val = $data['title'][$locale];
                    while (is_array($val)) {
                        $val = $val[$locale] ?? $val['en'] ?? reset($val) ?: '';
                    }
                    if (is_string($val) && $val !== '') {
                        $cleaned[$locale] = $val;
                    }
                }
            }
            $data['title'] = $cleaned ?: [$activeLocale => 'Untitled Draft'];
        }

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

        // Get the active locale for proper data handling
        $activeLocale = $this->getActiveFormsLocale() ?? 'en';

        // Fix double-nesting issue: Filament's Translatable concern wraps translatable
        // fields in locale keys. If the data is ALREADY wrapped (from LocaleSwitcher state),
        // we need to unwrap it to prevent double-nesting like {"en":{"en":"value"}}.
        foreach (['title', 'summary', 'description'] as $field) {
            if (! isset($data[$field])) {
                $data[$field] = [];

                continue;
            }

            $value = $data[$field];

            // If empty string or empty array, set to empty array and continue
            if ($value === '' || $value === []) {
                $data[$field] = [];

                continue;
            }

            // If it's a plain string, Filament will wrap it correctly - leave it alone
            if (is_string($value)) {
                continue;
            }

            // If it's an array, we need to check for double-nesting
            if (is_array($value)) {
                // Check if this looks like locale-wrapped data: ['en' => ..., 'fr' => ...]
                $locales = ['en', 'fr'];
                $hasLocaleKeys = ! empty(array_intersect(array_keys($value), $locales));

                if ($hasLocaleKeys) {
                    // It's already locale-wrapped. Check each locale value for double-nesting.
                    foreach ($locales as $locale) {
                        if (isset($value[$locale])) {
                            $localeValue = $value[$locale];
                            // Unwrap if the locale value is ALSO an array with locale keys
                            // This handles {"en": {"en": "actual value"}}
                            while (is_array($localeValue)) {
                                // Get the first value from the nested array
                                $extracted = $localeValue[$locale] ?? $localeValue['en'] ?? reset($localeValue);
                                if ($extracted === false || $extracted === $localeValue) {
                                    break; // Can't extract further
                                }
                                $localeValue = $extracted;
                            }
                            $value[$locale] = is_string($localeValue) ? $localeValue : '';
                        }
                    }
                    // Filter out empty locale values
                    $data[$field] = array_filter($value, fn ($v) => is_string($v) && $v !== '');
                } else {
                    // Not locale-wrapped, might be malformed - try to extract any string
                    $extracted = $this->extractStringFromNested($value);
                    $data[$field] = $extracted !== '' ? [$activeLocale => $extracted] : [];
                }
            }
        }

        // Set defaults for NOT NULL JSON columns that might be empty
        $data['highlights'] = $data['highlights'] ?? [];
        $data['included'] = $data['included'] ?? [];
        $data['not_included'] = $data['not_included'] ?? [];
        $data['requirements'] = $data['requirements'] ?? [];
        $data['meeting_point'] = $data['meeting_point'] ?? [];
        $data['cancellation_policy'] = $data['cancellation_policy'] ?? [];
        $data['pricing'] = $data['pricing'] ?? [];

        // Set defaults for required numeric fields
        $data['min_group_size'] = $data['min_group_size'] ?? 1;
        $data['max_group_size'] = $data['max_group_size'] ?? 10;
        $data['min_advance_booking_hours'] = $data['min_advance_booking_hours'] ?? 0;

        // Generate slug if missing
        if (empty($data['slug'])) {
            $titleForSlug = is_array($data['title'])
                ? ($data['title']['en'] ?? $data['title']['fr'] ?? reset($data['title']) ?: null)
                : ($data['title'] ?: null);
            if ($titleForSlug) {
                $data['slug'] = Str::slug($titleForSlug) . '-' . Str::random(6);
            } else {
                $data['slug'] = 'draft-' . Str::random(10);
            }
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

    /**
     * Override "Create & Create Another" to redirect to create page (resets wizard to step 1).
     */
    protected function getCreateAnotherFormAction(): Action
    {
        return Action::make('createAnother')
            ->label(__('filament-panels::resources/pages/create-record.form.actions.create_another.label'))
            ->action(function () {
                $this->create(another: true);
            })
            ->keyBindings(['mod+shift+s'])
            ->color('gray');
    }

    /**
     * When creating another record, redirect to the create page to reset the wizard.
     */
    protected function getRedirectUrlForAnotherRecord(): string
    {
        return $this->getResource()::getUrl('create');
    }

    /**
     * Extract a string value from potentially deeply nested arrays.
     */
    protected function extractStringFromNested(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            // Try locale keys first
            foreach (['en', 'fr'] as $locale) {
                if (isset($value[$locale])) {
                    $extracted = $this->extractStringFromNested($value[$locale]);
                    if ($extracted !== '') {
                        return $extracted;
                    }
                }
            }
            // Try first value
            $first = reset($value);
            if ($first !== false) {
                return $this->extractStringFromNested($first);
            }
        }

        return '';
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
        } else {
            // AUTO-CREATE DEFAULT AVAILABILITY: When no rules provided and not skipping,
            // create a sensible default rule so the listing has availability immediately.
            // This ensures new listings always have at least basic availability.
            AvailabilityRule::create([
                'listing_id' => $this->record->id,
                'rule_type' => 'daily',
                'days_of_week' => null, // daily applies to all days
                'start_time' => now()->setTime(9, 0, 0),
                'end_time' => now()->setTime(17, 0, 0),
                'capacity' => $this->record->max_group_size ?? 10,
                'is_active' => true,
            ]);

            Notification::make()
                ->info()
                ->title('Default Availability Created')
                ->body('A default daily schedule (9 AM - 5 PM) has been created. You can customize this in the Availability section.')
                ->persistent()
                ->actions([
                    NotificationAction::make('customize_availability')
                        ->label('Customize Availability')
                        ->url(AvailabilityRuleResource::getUrl('index') . '?tableFilters[listing_id][value]=' . $this->record->id)
                        ->button(),
                ])
                ->send();
        }
    }
}
