<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ListingResource\Pages;

use App\Enums\DifficultyLevel;
use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Filament\Admin\Resources\ListingResource;
use App\Filament\Concerns\SafeTranslation;
use App\Filament\Vendor\Resources\ListingResource as VendorListingResource;
use App\Mail\ListingPublishFailedMail;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ViewListing extends ViewRecord
{
    use SafeTranslation;

    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('approve')
                ->label('Approve & Publish')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve Listing')
                ->modalDescription('This will publish the listing and make it visible to travelers.')
                ->action(function () {
                    // Validate required fields before publishing
                    $errors = $this->validateForPublish();

                    if (! empty($errors)) {
                        Notification::make()
                            ->title('Cannot Publish Listing')
                            ->body('Missing required fields: ' . implode(', ', $errors))
                            ->danger()
                            ->persistent()
                            ->send();

                        // Notify vendor of publish failure
                        $this->notifyVendorOfPublishFailure($errors);

                        return;
                    }

                    $this->record->update([
                        'status' => ListingStatus::PUBLISHED,
                        'published_at' => now(),
                    ]);

                    // Send vendor bell notification
                    try {
                        $vendor = $this->record->vendor;
                        if ($vendor) {
                            $listingTitle = $this->record->getTranslation('title', 'en')
                                ?: $this->record->getTranslation('title', 'fr')
                                ?: 'Untitled';
                            if (is_array($listingTitle)) {
                                $listingTitle = reset($listingTitle) ?: 'Untitled';
                            }

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
                                            ->url("/vendor/listings/{$this->record->id}/edit")
                                            ->button(),
                                    ])
                                    ->getDatabaseMessage(),
                            ]);

                            \Log::info('NOTIF_DEBUG: ViewListing approval notification created', [
                                'listing_id' => $this->record->id,
                                'vendor_id' => $vendor->id,
                            ]);
                        }
                    } catch (\Throwable $e) {
                        \Log::error('Failed to send approval notification from ViewListing', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }

                    Notification::make()
                        ->title('Listing Approved')
                        ->body('The listing has been published.')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status === ListingStatus::PENDING_REVIEW),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => ListingStatus::REJECTED,
                    ]);

                    // Send vendor bell notification with rejection reason
                    try {
                        $vendor = $this->record->vendor;
                        if ($vendor) {
                            $listingTitle = $this->record->getTranslation('title', 'en')
                                ?: $this->record->getTranslation('title', 'fr')
                                ?: 'Untitled';
                            if (is_array($listingTitle)) {
                                $listingTitle = reset($listingTitle) ?: 'Untitled';
                            }
                            $reason = $data['reason'] ?? 'No reason provided';

                            $vendor->notifications()->create([
                                'id' => Str::uuid()->toString(),
                                'type' => \Filament\Notifications\DatabaseNotification::class,
                                'data' => Notification::make()
                                    ->title('Listing Rejected')
                                    ->icon('heroicon-o-x-circle')
                                    ->body("Your listing \"{$listingTitle}\" was rejected. Reason: {$reason}")
                                    ->warning()
                                    ->actions([
                                        NotificationAction::make('edit')
                                            ->label('Edit Listing')
                                            ->url("/vendor/listings/{$this->record->id}/edit")
                                            ->button(),
                                    ])
                                    ->getDatabaseMessage(),
                            ]);

                            \Log::info('NOTIF_DEBUG: ViewListing rejection notification created', [
                                'listing_id' => $this->record->id,
                                'vendor_id' => $vendor->id,
                            ]);
                        }
                    } catch (\Throwable $e) {
                        \Log::error('Failed to send rejection notification from ViewListing', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }

                    Notification::make()
                        ->title('Listing Rejected')
                        ->warning()
                        ->send();
                })
                ->visible(fn () => $this->record->status === ListingStatus::PENDING_REVIEW),

            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Listing Overview')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('Title')
                            ->formatStateUsing(fn ($record) => $this->safeTranslation($record->getTranslation('title', app()->getLocale()), 'Untitled')),

                        Infolists\Components\TextEntry::make('service_type')
                            ->label('Type')
                            ->badge()
                            ->color(fn (ServiceType $state): string => match ($state) {
                                ServiceType::TOUR => 'info',
                                ServiceType::EVENT => 'success',
                                ServiceType::SEJOUR => 'warning',
                            }),

                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (ListingStatus $state): string => $state->color()),

                        Infolists\Components\TextEntry::make('slug')
                            ->copyable(),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Vendor Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('vendor.display_name')
                            ->label('Vendor Name'),

                        Infolists\Components\TextEntry::make('vendor.email')
                            ->label('Email')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('location.name')
                            ->label('Location')
                            ->formatStateUsing(fn ($record) => $this->safeTranslation($record->location?->getTranslation('name', app()->getLocale()))),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Content')
                    ->schema([
                        Infolists\Components\TextEntry::make('summary')
                            ->label('Summary')
                            ->formatStateUsing(fn ($record) => $this->safeTranslation($record->getTranslation('summary', app()->getLocale())))
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->html()
                            ->formatStateUsing(fn ($record) => $this->safeTranslation($record->getTranslation('description', app()->getLocale())))
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Pricing & Capacity')
                    ->schema([
                        Infolists\Components\TextEntry::make('pricing.base')
                            ->label('Base Price')
                            ->formatStateUsing(
                                fn ($state, $record) => number_format((float) $state / 100, 2) . ' ' . ($record->pricing['currency'] ?? 'TND')
                            ),

                        Infolists\Components\TextEntry::make('min_group_size')
                            ->label('Min Group'),

                        Infolists\Components\TextEntry::make('max_group_size')
                            ->label('Max Group'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Tour Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('duration.value')
                            ->label('Duration')
                            ->formatStateUsing(
                                fn ($state, $record) => ($state ?? '-') . ' ' . ($record->duration['unit'] ?? 'hours')
                            ),

                        Infolists\Components\TextEntry::make('difficulty')
                            ->label('Difficulty')
                            ->formatStateUsing(fn (?DifficultyLevel $state): string => $state?->label() ?? 'Not set')
                            ->badge(),

                        Infolists\Components\TextEntry::make('distance.value')
                            ->label('Distance')
                            ->formatStateUsing(
                                fn ($state, $record) => $state ? ($state . ' ' . ($record->distance['unit'] ?? 'km')) : 'Not set'
                            ),
                    ])
                    ->columns(3)
                    ->visible(fn ($record): bool => in_array($record->service_type, [ServiceType::TOUR, ServiceType::SEJOUR])),

                Infolists\Components\Section::make('Event Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('event_type')
                            ->label('Event Type'),

                        Infolists\Components\TextEntry::make('start_date')
                            ->label('Start Date')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('end_date')
                            ->label('End Date')
                            ->dateTime(),
                    ])
                    ->columns(3)
                    ->visible(fn ($record): bool => $record->service_type === ServiceType::EVENT),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('bookings_count')
                            ->label('Total Bookings'),

                        Infolists\Components\TextEntry::make('reviews_count')
                            ->label('Reviews'),

                        Infolists\Components\TextEntry::make('rating')
                            ->label('Rating')
                            ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 1) . '/5' : 'No ratings'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('published_at')
                            ->label('Published')
                            ->dateTime()
                            ->placeholder('Not published'),
                    ])
                    ->columns(5),
            ]);
    }

    /**
     * Validate listing has all required fields for publishing.
     *
     * @return array<string> List of validation errors
     */
    protected function validateForPublish(): array
    {
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

        // Check pricing
        $pricing = $this->record->pricing;
        $hasNewFormatPricing = ! empty($pricing['person_types']) || ! empty($pricing['personTypes']);
        $hasOldFormatPricing = ! empty($pricing['base_price']) || ! empty($pricing['tnd_price']) || ! empty($pricing['eur_price']);
        if (! $hasNewFormatPricing && ! $hasOldFormatPricing) {
            $errors[] = 'Pricing information is required';
        }

        // Check location
        if (empty($this->record->location_id)) {
            $errors[] = 'Location is required';
        }

        return $errors;
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
}
