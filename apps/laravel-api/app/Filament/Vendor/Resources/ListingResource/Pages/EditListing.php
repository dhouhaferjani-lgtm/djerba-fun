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
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditListing extends EditRecord
{
    use Translatable;

    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
                    if (! $record->location_id) {
                        $errors[] = 'Location is required';
                    }

                    // Check title - must have at least one translation (English OR French)
                    $titleEn = $record->getTranslation('title', 'en');
                    $titleFr = $record->getTranslation('title', 'fr');
                    $hasEnglishTitle = ! empty($titleEn) && ! (is_array($titleEn) && empty(array_filter($titleEn)));
                    $hasFrenchTitle = ! empty($titleFr) && ! (is_array($titleFr) && empty(array_filter($titleFr)));

                    if (! $hasEnglishTitle && ! $hasFrenchTitle) {
                        $errors[] = __('filament.validation.title_translation_required');
                    }

                    // Check summary - must have at least one translation (English OR French)
                    $summaryEn = $record->getTranslation('summary', 'en');
                    $summaryFr = $record->getTranslation('summary', 'fr');
                    $hasEnglishSummary = ! empty($summaryEn) && ! (is_array($summaryEn) && empty(array_filter($summaryEn)));
                    $hasFrenchSummary = ! empty($summaryFr) && ! (is_array($summaryFr) && empty(array_filter($summaryFr)));

                    if (! $hasEnglishSummary && ! $hasFrenchSummary) {
                        $errors[] = __('filament.validation.summary_translation_required');
                    }

                    // Check description - must have at least one translation (English OR French)
                    $descriptionEn = $record->getTranslation('description', 'en');
                    $descriptionFr = $record->getTranslation('description', 'fr');
                    $hasEnglishDescription = ! empty($descriptionEn) && ! (is_array($descriptionEn) && empty(array_filter($descriptionEn)));
                    $hasFrenchDescription = ! empty($descriptionFr) && ! (is_array($descriptionFr) && empty(array_filter($descriptionFr)));

                    if (! $hasEnglishDescription && ! $hasFrenchDescription) {
                        $errors[] = __('filament.validation.description_translation_required');
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
                            if (! empty($personType['tnd_price']) && ! empty($personType['eur_price'])) {
                                $hasValidPricing = true;
                                break;
                            }
                        }

                        if (! $hasValidPricing) {
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

                    if (! empty($errors)) {
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

            Actions\Action::make('manage_availability')
                ->label('Manage Availability')
                ->icon('heroicon-o-calendar-days')
                ->color('info')
                ->url(fn () => \App\Filament\Vendor\Resources\AvailabilityRuleResource::getUrl('index') . '?tableFilters[listing_id][value]=' . $this->record->id)
                ->visible(fn () => $this->record->exists),

            Actions\Action::make('add_availability_rule')
                ->label('Add Availability Rule')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->url(fn () => \App\Filament\Vendor\Resources\AvailabilityRuleResource::getUrl('create') . '?listing_id=' . $this->record->id)
                ->visible(fn () => $this->record->exists),

            Actions\Action::make('auto_translate')
                ->label(__('filament.actions.auto_translate'))
                ->icon('heroicon-o-language')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading(__('filament.modals.auto_translate'))
                ->modalDescription(__('filament.modals.auto_translate_description'))
                ->form([
                    \Filament\Forms\Components\Select::make('target_language')
                        ->label(__('filament.labels.target_language'))
                        ->options([
                            'en' => 'English (from French)',
                            'fr' => 'French (from English)',
                        ])
                        ->required()
                        ->default('en'),
                ])
                ->action(function (array $data) {
                    $translationService = app(\App\Services\TranslationService::class);

                    if (! $translationService->isEnabled()) {
                        Notification::make()
                            ->title(__('filament.notifications.translation_not_configured'))
                            ->body(__('filament.notifications.translation_not_configured_body'))
                            ->warning()
                            ->send();

                        return;
                    }

                    $result = $translationService->translateListing(
                        $this->record,
                        $data['target_language']
                    );

                    if (! empty($result['translated_fields'])) {
                        $this->record->save();

                        Notification::make()
                            ->title(__('filament.notifications.translation_complete'))
                            ->body(__('filament.notifications.translation_complete_body', [
                                'fields' => implode(', ', $result['translated_fields']),
                                'language' => $data['target_language'] === 'en' ? 'English' : 'French',
                            ]))
                            ->success()
                            ->send();

                        // Refresh the form data
                        $this->fillForm();
                    } else {
                        Notification::make()
                            ->title(__('filament.notifications.no_translation_needed'))
                            ->body(__('filament.notifications.no_translation_needed_body'))
                            ->info()
                            ->send();
                    }
                })
                ->visible(fn () => $this->record->exists),

            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Fix any double-nested translations that might exist in the database
        // This cleans up malformed data before it goes into the form
        foreach (['title', 'summary', 'description'] as $field) {
            if (! isset($data[$field]) || ! is_array($data[$field])) {
                continue;
            }

            $value = $data[$field];
            $locales = ['en', 'fr'];
            $hasLocaleKeys = ! empty(array_intersect(array_keys($value), $locales));

            if ($hasLocaleKeys) {
                // Check each locale value for nested arrays and flatten them
                foreach ($locales as $locale) {
                    if (isset($value[$locale])) {
                        $localeValue = $value[$locale];

                        // Unwrap nested arrays
                        while (is_array($localeValue)) {
                            $extracted = $localeValue[$locale] ?? $localeValue['en'] ?? reset($localeValue);

                            if ($extracted === false || $extracted === $localeValue) {
                                break;
                            }
                            $localeValue = $extracted;
                        }
                        $data[$field][$locale] = is_string($localeValue) ? $localeValue : '';
                    }
                }
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Get the active locale for proper data handling
        $activeLocale = $this->getActiveFormsLocale() ?? 'en';

        // Fix double-nesting issue: Filament's Translatable concern wraps translatable
        // fields in locale keys. If the data is ALREADY wrapped (from LocaleSwitcher state),
        // we need to unwrap it to prevent double-nesting like {"en":{"en":"value"}}.
        foreach (['title', 'summary', 'description'] as $field) {
            if (! isset($data[$field])) {
                continue;
            }

            $value = $data[$field];

            // If empty string or empty array, continue
            if ($value === '' || $value === []) {
                continue;
            }

            // If it's a plain string, leave it alone
            if (is_string($value)) {
                continue;
            }

            // If it's an array, check for double-nesting
            if (is_array($value)) {
                $locales = ['en', 'fr'];
                $hasLocaleKeys = ! empty(array_intersect(array_keys($value), $locales));

                if ($hasLocaleKeys) {
                    // Check each locale value for double-nesting
                    foreach ($locales as $locale) {
                        if (isset($value[$locale])) {
                            $localeValue = $value[$locale];

                            // Unwrap if the locale value is ALSO an array
                            while (is_array($localeValue)) {
                                $extracted = $localeValue[$locale] ?? $localeValue['en'] ?? reset($localeValue);

                                if ($extracted === false || $extracted === $localeValue) {
                                    break;
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

        // Process gallery_images: convert TemporaryUploadedFile to permanent storage paths
        $data['gallery_images'] = $this->processGalleryImages($data['gallery_images'] ?? []);

        return $data;
    }

    /**
     * Process gallery images - convert TemporaryUploadedFile objects to permanent URLs.
     *
     * @param  array  $images  Array that may contain TemporaryUploadedFile objects, strings, or nulls
     * @return array Array of full URL strings
     */
    protected function processGalleryImages(array $images): array
    {
        $processed = [];
        $disk = config('filesystems.default') === 'minio' ? 'minio' : 'public';

        foreach ($images as $index => $image) {
            if ($image === null) {
                continue;
            }

            // If it's a TemporaryUploadedFile, store it permanently and get full URL
            if ($image instanceof TemporaryUploadedFile) {
                $path = $image->store('listing-galleries', $disk);

                if ($path) {
                    // Store full URL for consistent frontend access
                    $processed[$index] = Storage::disk($disk)->url($path);
                }
            }
            // If it's already a full URL (http/https), keep it
            elseif (is_string($image) && ! empty($image)) {
                if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
                    $processed[$index] = $image;
                } else {
                    // Convert relative path to full URL
                    $processed[$index] = Storage::disk($disk)->url($image);
                }
            }
        }

        // Re-index array to remove gaps
        return array_values($processed);
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

    /**
     * Get temporary preview URL for an uploaded image.
     * Called from the bento-slot-mapper blade component via $wire.getTemporaryUploadUrl().
     */
    public function getTemporaryUploadUrl(int $index): ?string
    {
        $image = $this->data['gallery_images'][$index] ?? null;

        if ($image instanceof TemporaryUploadedFile) {
            return $image->temporaryUrl();
        }

        // If it's already a string URL, return it
        if (is_string($image) && ! empty($image)) {
            return $image;
        }

        return null;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
