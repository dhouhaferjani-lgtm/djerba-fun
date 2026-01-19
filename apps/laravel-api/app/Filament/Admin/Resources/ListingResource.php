<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Filament\Admin\Resources\ListingResource\Pages;
use App\Models\Listing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                            ->options(ServiceType::class)
                            ->disabled(),

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
                            ->disabled()
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('filament.labels.title'))
                    ->formatStateUsing(function ($record) {
                        $title = $record->getTranslation('title', app()->getLocale());

                        // Handle malformed nested arrays from earlier bug
                        if (is_array($title)) {
                            $title = $title[app()->getLocale()] ?? $title['en'] ?? reset($title) ?: 'Untitled';

                            while (is_array($title)) {
                                $title = reset($title) ?: 'Untitled';
                            }
                        }

                        return $title ?: 'Untitled';
                    })
                    ->limit(30)
                    ->tooltip(function ($record) {
                        $title = $record->getTranslation('title', app()->getLocale());

                        if (is_array($title)) {
                            $title = $title[app()->getLocale()] ?? $title['en'] ?? reset($title) ?: 'Untitled';

                            while (is_array($title)) {
                                $title = reset($title) ?: 'Untitled';
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
                        ServiceType::EVENT => 'success',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (ListingStatus $state): string => $state->color()),

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
                        ServiceType::EVENT->value => __('filament.options.events'),
                    ]),

                Tables\Filters\Filter::make('pending_review')
                    ->label(__('filament.filters.pending_review'))
                    ->query(fn (Builder $query): Builder => $query->where('status', ListingStatus::PENDING_REVIEW))
                    ->toggle(),

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

                            // Check title
                            $title = $record->getTranslation('title', 'en');

                            if (empty($title) || (is_array($title) && empty(array_filter($title)))) {
                                $errors[] = __('filament.validation.english_title_required');
                            }

                            // Check summary
                            $summary = $record->getTranslation('summary', 'en');

                            if (empty($summary) || (is_array($summary) && empty(array_filter($summary)))) {
                                $errors[] = __('filament.validation.english_summary_required');
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

                            // TODO: Send notification to vendor with rejection reason

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
