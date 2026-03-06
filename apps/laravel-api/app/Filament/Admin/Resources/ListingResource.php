<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Enums\TagType;
use App\Filament\Admin\Resources\ListingResource\Pages;
use App\Filament\Vendor\Resources\ListingResource as VendorListingResource;
use App\Models\Listing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;

    protected static ?string $navigationIcon = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.listings');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.sections.listing_information'))
                    ->schema([
                        Forms\Components\TextInput::make('title.en')
                            ->label(__('filament.labels.title_english'))
                            ->disabled(),

                        Forms\Components\TextInput::make('title.fr')
                            ->label(__('filament.labels.title_french'))
                            ->disabled(),

                        Forms\Components\Select::make('service_type')
                            ->options(ServiceType::class),

                        Forms\Components\Select::make('status')
                            ->options([
                                ListingStatus::DRAFT->value => ListingStatus::DRAFT->label(),
                                ListingStatus::PENDING_REVIEW->value => ListingStatus::PENDING_REVIEW->label(),
                                ListingStatus::PUBLISHED->value => ListingStatus::PUBLISHED->label(),
                                ListingStatus::ARCHIVED->value => ListingStatus::ARCHIVED->label(),
                                ListingStatus::REJECTED->value => ListingStatus::REJECTED->label(),
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('slug')
                            ->disabled(),

                        Forms\Components\Select::make('vendor_id')
                            ->relationship('vendor', 'display_name')
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('filament.sections.location'))
                    ->schema([
                        Forms\Components\Select::make('location_id')
                            ->relationship(
                                name: 'location',
                                modifyQueryUsing: fn (Builder $query) => $query->orderBy('slug'),
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', app()->getLocale()))
                            ->searchable(['slug'])
                            ->preload(),
                    ]),

                Forms\Components\Section::make(__('filament.sections.description'))
                    ->schema([
                        Forms\Components\Textarea::make('summary.en')
                            ->label(__('filament.labels.summary_english'))
                            ->disabled()
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('description.en')
                            ->label(__('filament.labels.description_english'))
                            ->disabled()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make(__('filament.sections.pricing_capacity'))
                    ->schema([
                        Forms\Components\TextInput::make('pricing.base')
                            ->label(__('filament.labels.base_price'))
                            ->disabled(),

                        Forms\Components\TextInput::make('pricing.currency')
                            ->label(__('filament.labels.currency'))
                            ->disabled(),

                        Forms\Components\TextInput::make('min_group_size')
                            ->disabled(),

                        Forms\Components\TextInput::make('max_group_size')
                            ->disabled(),
                    ])
                    ->columns(4),

                Forms\Components\Section::make(__('filament.sections.moderation'))
                    ->schema([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label(__('filament.labels.rejection_reason'))
                            ->helperText(__('filament.helpers.rejection_reason_helper'))
                            ->rows(3)
                            ->visible(fn ($record) => $record?->status === ListingStatus::REJECTED)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make(__('filament.sections.homepage_display'))
                    ->schema([
                        Forms\Components\Toggle::make('is_featured')
                            ->label(__('filament.labels.feature_on_homepage'))
                            ->visible(fn ($record) => $record?->status === ListingStatus::PUBLISHED)
                            ->disabled(function (?Listing $record) {
                                // If this listing is already featured, allow toggling off
                                if ($record?->is_featured) {
                                    return false;
                                }
                                // Check if 3 listings are already featured
                                return Listing::where('is_featured', true)->count() >= 3;
                            })
                            ->helperText(function (?Listing $record) {
                                if ($record?->is_featured) {
                                    return __('filament.helpers.show_on_homepage');
                                }
                                $featuredCount = Listing::where('is_featured', true)->count();
                                if ($featuredCount >= 3) {
                                    return __('filament.helpers.featured_limit_reached');
                                }
                                return __('filament.helpers.show_on_homepage');
                            })
                            ->rules([
                                function (?Listing $record) {
                                    return function (string $attribute, $value, \Closure $fail) use ($record) {
                                        if ($value && !$record?->is_featured) {
                                            $featuredCount = Listing::where('is_featured', true)->count();
                                            if ($featuredCount >= 3) {
                                                $fail(__('filament.helpers.featured_limit_reached'));
                                            }
                                        }
                                    };
                                },
                            ]),
                    ])
                    ->visible(fn ($record) => $record?->status === ListingStatus::PUBLISHED),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('filament.labels.title'))
                    ->formatStateUsing(function ($record) {
                        $currentLocale = app()->getLocale();
                        $alternateLocale = $currentLocale === 'en' ? 'fr' : 'en';

                        // Try current locale first
                        $title = $record->getTranslation('title', $currentLocale);
                        $usedLocale = $currentLocale;

                        // Handle malformed nested arrays from earlier bug
                        if (is_array($title)) {
                            $title = $title[$currentLocale] ?? $title['en'] ?? reset($title) ?: null;

                            while (is_array($title)) {
                                $title = reset($title) ?: null;
                            }
                        }

                        // If empty, try alternate locale
                        if (empty($title)) {
                            $title = $record->getTranslation('title', $alternateLocale);
                            $usedLocale = $alternateLocale;

                            if (is_array($title)) {
                                $title = $title[$alternateLocale] ?? $title['en'] ?? reset($title) ?: null;

                                while (is_array($title)) {
                                    $title = reset($title) ?: null;
                                }
                            }
                        }

                        if (empty($title)) {
                            return 'Untitled';
                        }

                        // Add language indicator if using alternate locale
                        if ($usedLocale !== $currentLocale) {
                            $langLabel = strtoupper($usedLocale);

                            return "[{$langLabel}] {$title}";
                        }

                        return $title;
                    })
                    ->limit(35)
                    ->description(function ($record) {
                        // Check which languages have content
                        $titleEn = $record->getTranslation('title', 'en');
                        $titleFr = $record->getTranslation('title', 'fr');

                        // Handle malformed arrays
                        if (is_array($titleEn)) {
                            $titleEn = $titleEn['en'] ?? reset($titleEn) ?: null;
                        }

                        if (is_array($titleFr)) {
                            $titleFr = $titleFr['fr'] ?? reset($titleFr) ?: null;
                        }

                        $hasEn = ! empty($titleEn);
                        $hasFr = ! empty($titleFr);

                        if ($hasEn && $hasFr) {
                            return null; // Bilingual - no indicator needed
                        }

                        if ($hasEn) {
                            return __('filament.labels.english_only');
                        }

                        if ($hasFr) {
                            return __('filament.labels.french_only');
                        }

                        return null;
                    })
                    ->tooltip(function ($record) {
                        $currentLocale = app()->getLocale();
                        $alternateLocale = $currentLocale === 'en' ? 'fr' : 'en';

                        $title = $record->getTranslation('title', $currentLocale);

                        if (is_array($title)) {
                            $title = $title[$currentLocale] ?? $title['en'] ?? reset($title) ?: null;

                            while (is_array($title)) {
                                $title = reset($title) ?: null;
                            }
                        }

                        if (empty($title)) {
                            $title = $record->getTranslation('title', $alternateLocale);

                            if (is_array($title)) {
                                $title = $title[$alternateLocale] ?? $title['en'] ?? reset($title) ?: null;

                                while (is_array($title)) {
                                    $title = reset($title) ?: null;
                                }
                            }
                        }

                        return $title ?: 'Untitled';
                    })
                    ->searchable(false),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('vendor.display_name')
                    ->label(__('filament.labels.vendor'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('service_type')
                    ->label(__('filament.labels.type'))
                    ->badge()
                    ->color(fn (ServiceType $state): string => match ($state) {
                        ServiceType::TOUR => 'info',
                        ServiceType::NAUTICAL => 'primary',
                        ServiceType::ACCOMMODATION => 'warning',
                        ServiceType::EVENT => 'success',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (ListingStatus $state): string => $state->color()),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label(__('filament.labels.featured'))
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('tags.name')
                    ->label('Tags')
                    ->badge()
                    ->limitList(3)
                    ->formatStateUsing(function ($state, $record) {
                        if (empty($state)) {
                            return null;
                        }
                        // Get the translated name
                        $locale = app()->getLocale();
                        if (is_array($state)) {
                            return $state[$locale] ?? $state['en'] ?? reset($state) ?: $state;
                        }
                        return $state;
                    })
                    ->color(fn ($record) => 'gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('location.name')
                    ->label(__('filament.sections.location'))
                    ->formatStateUsing(function ($record) {
                        $name = $record->location?->getTranslation('name', app()->getLocale());

                        if (is_array($name)) {
                            $name = $name[app()->getLocale()] ?? $name['en'] ?? reset($name) ?: '-';

                            while (is_array($name)) {
                                $name = reset($name) ?: '-';
                            }
                        }

                        return $name ?: '-';
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('pricing.base')
                    ->label(__('filament.labels.price'))
                    ->formatStateUsing(
                        fn ($state, $record) => $state ? number_format((float) $state / 100, 2) . ' ' . ($record->pricing['currency'] ?? 'TND') : '-'
                    )
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('bookings_count')
                    ->label(__('filament.resources.bookings'))
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('rating')
                    ->label(__('filament.labels.rating'))
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 1) : '-')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.labels.created'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label(__('filament.labels.published'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        ListingStatus::DRAFT->value => ListingStatus::DRAFT->label(),
                        ListingStatus::PENDING_REVIEW->value => ListingStatus::PENDING_REVIEW->label(),
                        ListingStatus::PUBLISHED->value => ListingStatus::PUBLISHED->label(),
                        ListingStatus::ARCHIVED->value => ListingStatus::ARCHIVED->label(),
                        ListingStatus::REJECTED->value => ListingStatus::REJECTED->label(),
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('service_type')
                    ->options([
                        ServiceType::TOUR->value => __('filament.options.tours'),
                        ServiceType::NAUTICAL->value => __('filament.options.nautical'),
                        ServiceType::ACCOMMODATION->value => __('filament.options.accommodations'),
                        ServiceType::EVENT->value => __('filament.options.events'),
                    ]),

                Tables\Filters\Filter::make('pending_review')
                    ->label(__('filament.filters.pending_review'))
                    ->query(fn (Builder $query): Builder => $query->where('status', ListingStatus::PENDING_REVIEW))
                    ->toggle(),

                Tables\Filters\Filter::make('featured')
                    ->label(__('filament.filters.featured'))
                    ->query(fn (Builder $query): Builder => $query->where('is_featured', true))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('content_language')
                    ->label(__('filament.filters.content_language'))
                    ->options([
                        'en_only' => __('filament.filters.english_only'),
                        'fr_only' => __('filament.filters.french_only'),
                        'bilingual' => __('filament.filters.bilingual'),
                        'missing_en' => __('filament.filters.missing_english'),
                        'missing_fr' => __('filament.filters.missing_french'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        // PostgreSQL JSON syntax: title->>'en' extracts as text
                        return match ($data['value']) {
                            'en_only' => $query->whereRaw("(title->>'en') IS NOT NULL AND (title->>'en') != ''")
                                ->where(function ($q) {
                                    $q->whereRaw("(title->>'fr') IS NULL")
                                        ->orWhereRaw("(title->>'fr') = ''");
                                }),
                            'fr_only' => $query->whereRaw("(title->>'fr') IS NOT NULL AND (title->>'fr') != ''")
                                ->where(function ($q) {
                                    $q->whereRaw("(title->>'en') IS NULL")
                                        ->orWhereRaw("(title->>'en') = ''");
                                }),
                            'bilingual' => $query->whereRaw("(title->>'en') IS NOT NULL AND (title->>'en') != ''")
                                ->whereRaw("(title->>'fr') IS NOT NULL AND (title->>'fr') != ''"),
                            'missing_en' => $query->where(function ($q) {
                                $q->whereRaw("(title->>'en') IS NULL")
                                    ->orWhereRaw("(title->>'en') = ''");
                            }),
                            'missing_fr' => $query->where(function ($q) {
                                $q->whereRaw("(title->>'fr') IS NULL")
                                    ->orWhereRaw("(title->>'fr') = ''");
                            }),
                            default => $query,
                        };
                    }),

                Tables\Filters\SelectFilter::make('tag_type')
                    ->label('Tag Type')
                    ->options(collect(TagType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()]))
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('tags', fn ($q) => $q->where('type', $data['value']));
                    }),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label(__('filament.actions.approve_publish'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('filament.modals.approve_listing'))
                        ->modalDescription(__('filament.modals.approve_listing_description'))
                        ->action(function (Listing $record) {
                            // Validate required fields before publishing
                            $errors = [];

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

                            // Check pricing - accept new or old format
                            $pricing = $record->pricing;
                            $hasNewFormatPricing = ! empty($pricing['person_types']) || ! empty($pricing['personTypes']);
                            $hasOldFormatPricing = ! empty($pricing['base_price']) || ! empty($pricing['tnd_price']) || ! empty($pricing['eur_price']);

                            if (! $hasNewFormatPricing && ! $hasOldFormatPricing) {
                                $errors[] = __('filament.validation.pricing_required');
                            }

                            // Check location
                            if (empty($record->location_id)) {
                                $errors[] = __('filament.validation.location_required');
                            }

                            if (! empty($errors)) {
                                Notification::make()
                                    ->title(__('filament.notifications.cannot_publish'))
                                    ->body(implode(', ', $errors))
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                return;
                            }

                            $record->update([
                                'status' => ListingStatus::PUBLISHED,
                                'published_at' => now(),
                            ]);

                            // Send database notification to vendor
                            try {
                                $vendor = $record->vendor;
                                if ($vendor) {
                                    $listingTitle = $record->getTranslation('title', 'en') ?: $record->getTranslation('title', 'fr') ?: 'Untitled';
                                    if (is_array($listingTitle)) {
                                        $listingTitle = reset($listingTitle) ?: 'Untitled';
                                    }
                                    $viewUrl = VendorListingResource::getUrl('view', ['record' => $record], panel: 'vendor');
                                    $vendor->notifications()->create([
                                        'id' => Str::uuid()->toString(),
                                        'type' => \Filament\Notifications\DatabaseNotification::class,
                                        'data' => Notification::make()
                                            ->title('Your Listing Was Approved')
                                            ->icon('heroicon-o-check-badge')
                                            ->body("Your listing \"{$listingTitle}\" has been approved and is now visible to travelers.")
                                            ->success()
                                            ->actions([
                                                NotificationAction::make('view')
                                                    ->label('View Listing')
                                                    ->url($viewUrl)
                                                    ->button(),
                                            ])
                                            ->getDatabaseMessage(),
                                    ]);
                                }
                            } catch (\Throwable $e) {
                                \Log::error('Failed to send listing approved notification to vendor', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                            }

                            Notification::make()
                                ->title(__('filament.notifications.listing_approved'))
                                ->body(__('filament.notifications.listing_approved_body'))
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Listing $record) => $record->status === ListingStatus::PENDING_REVIEW),

                    Tables\Actions\Action::make('reject')
                        ->label(__('filament.actions.reject'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label(__('filament.labels.rejection_reason'))
                                ->required()
                                ->rows(3)
                                ->helperText(__('filament.helpers.rejection_shared_helper')),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading(__('filament.modals.reject_listing'))
                        ->modalDescription(__('filament.modals.reject_listing_description'))
                        ->action(function (Listing $record, array $data) {
                            $record->update([
                                'status' => ListingStatus::REJECTED,
                            ]);

                            // Send database notification to vendor with rejection reason
                            try {
                                $vendor = $record->vendor;
                                if ($vendor) {
                                    $listingTitle = $record->getTranslation('title', 'en') ?: $record->getTranslation('title', 'fr') ?: 'Untitled';
                                    if (is_array($listingTitle)) {
                                        $listingTitle = reset($listingTitle) ?: 'Untitled';
                                    }
                                    $reason = $data['reason'] ?? 'No reason provided';
                                    $editUrl = VendorListingResource::getUrl('edit', ['record' => $record], panel: 'vendor');
                                    $vendor->notifications()->create([
                                        'id' => Str::uuid()->toString(),
                                        'type' => \Filament\Notifications\DatabaseNotification::class,
                                        'data' => Notification::make()
                                            ->title('Your Listing Was Rejected')
                                            ->icon('heroicon-o-x-mark')
                                            ->body("Your listing \"{$listingTitle}\" was rejected. Reason: {$reason}")
                                            ->warning()
                                            ->actions([
                                                NotificationAction::make('edit')
                                                    ->label('Edit Listing')
                                                    ->url($editUrl)
                                                    ->button(),
                                            ])
                                            ->getDatabaseMessage(),
                                    ]);
                                }
                            } catch (\Throwable $e) {
                                \Log::error('Failed to send listing rejected notification to vendor', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                            }

                            Notification::make()
                                ->title(__('filament.notifications.listing_rejected'))
                                ->body(__('filament.notifications.listing_rejected_body'))
                                ->warning()
                                ->send();
                        })
                        ->visible(fn (Listing $record) => $record->status === ListingStatus::PENDING_REVIEW),

                    Tables\Actions\Action::make('archive')
                        ->label(__('filament.actions.archive'))
                        ->icon('heroicon-o-archive-box')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (Listing $record) {
                            $record->archive();

                            Notification::make()
                                ->title(__('filament.notifications.listing_archived'))
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Listing $record) => $record->status === ListingStatus::PUBLISHED),

                    Tables\Actions\Action::make('republish')
                        ->label(__('filament.actions.republish'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Listing $record) {
                            $record->publish();

                            Notification::make()
                                ->title(__('filament.notifications.listing_republished'))
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Listing $record) => $record->status === ListingStatus::ARCHIVED),

                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),

                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label(__('filament.actions.approve_selected'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $approved = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if ($record->status !== ListingStatus::PENDING_REVIEW) {
                                    continue;
                                }

                                // Validate required fields
                                $title = $record->getTranslation('title', 'en');
                                $summary = $record->getTranslation('summary', 'en');
                                $hasTitle = ! empty($title) && ! (is_array($title) && empty(array_filter($title)));
                                $hasSummary = ! empty($summary) && ! (is_array($summary) && empty(array_filter($summary)));
                                $pricing = $record->pricing;
                                $hasPricing = ! empty($pricing['person_types']) || ! empty($pricing['personTypes']) ||
                                              ! empty($pricing['base_price']) || ! empty($pricing['tnd_price']) || ! empty($pricing['eur_price']);
                                $hasLocation = ! empty($record->location_id);

                                if ($hasTitle && $hasSummary && $hasPricing && $hasLocation) {
                                    $record->publish();
                                    $approved++;
                                } else {
                                    $skipped++;
                                }
                            }

                            $message = __('filament.notifications.listings_approved', ['approved' => $approved]);

                            if ($skipped > 0) {
                                $message = __('filament.notifications.listings_skipped', ['approved' => $approved, 'skipped' => $skipped]);
                            }

                            Notification::make()
                                ->title($message)
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_archive')
                        ->label(__('filament.actions.archive_selected'))
                        ->icon('heroicon-o-archive-box')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;

                            foreach ($records as $record) {
                                if ($record->status === ListingStatus::PUBLISHED) {
                                    $record->archive();
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title(__('filament.notifications.listings_archived', ['count' => $count]))
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListListings::route('/'),
            'create' => Pages\CreateListing::route('/create'),
            'view' => Pages\ViewListing::route('/{record}'),
            'edit' => Pages\EditListing::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', ListingStatus::PENDING_REVIEW)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('filament.tooltips.pending_review');
    }
}
