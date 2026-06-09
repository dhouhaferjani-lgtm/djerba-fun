<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LocationResource\Pages;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LocationResource extends Resource
{
    use Translatable;

    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = null;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.locations');
    }

    public static function getTranslatableLocales(): array
    {
        return ['en', 'fr'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.sections.location_information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('filament.labels.name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, Forms\Set $set) {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('slug')
                            ->label(__('filament.labels.slug'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText(__('filament.helpers.slug_url_friendly'))
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('description')
                            ->label(__('filament.labels.description'))
                            ->rows(4)
                            ->columnSpanFull()
                            ->helperText(__('filament.helpers.description_rich')),

                        Forms\Components\TextInput::make('image_url')
                            ->label(__('filament.labels.image_url'))
                            ->url()
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->helperText(__('filament.helpers.image_url_helper')),
                    ])->columns(2),

                Forms\Components\Section::make(__('filament.sections.geographic_information'))
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label(__('filament.labels.address'))
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('city')
                            ->label(__('filament.labels.city'))
                            ->required()
                            ->maxLength(100)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('region')
                            ->label(__('filament.labels.region'))
                            ->maxLength(100)
                            ->columnSpan(1),

                        Forms\Components\Select::make('country')
                            ->label(__('filament.labels.country'))
                            ->required()
                            ->default('TN')
                            ->options([
                                'TN' => 'Tunisia',
                                'FR' => 'France',
                                'MA' => 'Morocco',
                                'DZ' => 'Algeria',
                                'LY' => 'Libya',
                                'EG' => 'Egypt',
                                'IT' => 'Italy',
                                'ES' => 'Spain',
                                'DE' => 'Germany',
                                'GB' => 'United Kingdom',
                                'BE' => 'Belgium',
                                'NL' => 'Netherlands',
                            ])
                            ->searchable()
                            ->columnSpan(1),

                        Forms\Components\Select::make('timezone')
                            ->label(__('filament.labels.timezone'))
                            ->options([
                                'Africa/Tunis' => 'Africa/Tunis (UTC+1)',
                                'Europe/Paris' => 'Europe/Paris (UTC+1/+2)',
                                'Europe/London' => 'Europe/London (UTC+0/+1)',
                            ])
                            ->default('Africa/Tunis')
                            ->required()
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make(__('filament.sections.map_coordinates'))
                    ->schema([
                        \Cheesegrits\FilamentGoogleMaps\Fields\Map::make('location')
                            ->visible(fn () => filled(config('filament-google-maps.key')))
                            ->mapControls([
                                'mapTypeControl' => true,
                                'scaleControl' => true,
                                'streetViewControl' => true,
                                'rotateControl' => true,
                                'fullscreenControl' => true,
                                'searchBoxControl' => true,
                                'zoomControl' => true,
                            ])
                            ->height('400px')
                            ->defaultZoom(12)
                            ->defaultLocation([33.8869, 10.8453]) // Djerba, Tunisia
                            ->clickable()
                            ->draggable()
                            ->autocompleteReverse(true)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('latitude')
                                ->label(__('filament.labels.latitude'))
                                ->numeric()
                                ->readOnly(fn () => filled(config('filament-google-maps.key')))
                                ->dehydrated()
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('longitude')
                                ->label(__('filament.labels.longitude'))
                                ->numeric()
                                ->readOnly(fn () => filled(config('filament-google-maps.key')))
                                ->dehydrated()
                                ->columnSpan(1),
                        ]),
                    ]),

                Forms\Components\Section::make(__('filament.sections.statistics'))
                    ->schema([
                        Forms\Components\TextInput::make('listings_count')
                            ->label(__('filament.labels.number_of_listings'))
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText(__('filament.helpers.listings_count_helper')),
                    ])
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label(__('filament.labels.image'))
                    ->width(80)
                    ->height(60)
                    ->defaultImageUrl(url('/images/placeholder-location.jpg')),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament.labels.name'))
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('slug')
                    ->label(__('filament.labels.slug'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Slug copied!')
                    ->color('gray')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('city')
                    ->label(__('filament.labels.city'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('region')
                    ->label(__('filament.labels.region'))
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('country')
                    ->label(__('filament.labels.country'))
                    ->searchable()
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('listings_count')
                    ->label(__('filament.labels.listings'))
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'gray',
                        $state < 5 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('latitude')
                    ->label(__('filament.labels.latitude'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('longitude')
                    ->label(__('filament.labels.longitude'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament.labels.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('country')
                    ->label(__('filament.labels.country'))
                    ->options([
                        'TN' => 'Tunisia',
                        'FR' => 'France',
                        'MA' => 'Morocco',
                        'DZ' => 'Algeria',
                        'LY' => 'Libya',
                        'EG' => 'Egypt',
                        'IT' => 'Italy',
                        'ES' => 'Spain',
                        'DE' => 'Germany',
                        'GB' => 'United Kingdom',
                        'BE' => 'Belgium',
                        'NL' => 'Netherlands',
                    ]),

                Tables\Filters\Filter::make('has_listings')
                    ->label(__('filament.filters.has_listings'))
                    ->query(fn ($query) => $query->where('listings_count', '>', 0)),

                Tables\Filters\Filter::make('has_coordinates')
                    ->label(__('filament.filters.has_coordinates'))
                    ->query(fn ($query) => $query->whereNotNull('latitude')->whereNotNull('longitude')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription(function (Location $record): ?string {
                        // Surface the cascade impact in the standard "Are you sure?"
                        // modal: when the smart-allow path will hard-delete soft-deleted
                        // listings (and cascade through their bookings + reviews via FK
                        // cascadeOnDelete), admins must see what's about to be destroyed.
                        $trashedCount = $record->listings()->onlyTrashed()->count();

                        return $trashedCount > 0
                            ? __('filament.notifications.delete_location_cascade_warning', ['count' => $trashedCount])
                            : null;
                    })
                    ->before(function (Tables\Actions\DeleteAction $action, Location $record) {
                        // Smart-allow guard. Cascade chain on Location delete:
                        //   locations → listings (cascadeOnDelete) → cart_items (RESTRICT)
                        // cart_items.listing_id (and .hold_id via the holds cascade)
                        // is the ONLY FK that can break the cascade (23503 → 500).
                        //
                        // Policy:
                        //   - active listings exist                    → block
                        //   - LIVE cart_items reference trashed        → block
                        //     (live = Cart::scopeLive — status checking_out
                        //      OR (active AND expires_at > now()))
                        //   - else (only dead cart_items, or none)     → pre-clean dead
                        //     cart_items then allow cascade. Dead = expired/abandoned/
                        //     completed/checking_out-but-expired carts; deleting their
                        //     items just drops session state nobody depends on.
                        $activeCount = $record->listings()->count();

                        if ($activeCount > 0) {
                            Notification::make()
                                ->danger()
                                ->title(__('filament.notifications.cannot_delete_location_title'))
                                ->body(__('filament.notifications.cannot_delete_location_active_body', ['count' => $activeCount]))
                                ->send();

                            $action->cancel();

                            return;
                        }

                        $trashedListingIds = $record->listings()->onlyTrashed()->pluck('id');

                        if ($trashedListingIds->isEmpty()) {
                            return;
                        }

                        $liveCartItemCount = \App\Models\CartItem::query()
                            ->whereIn('listing_id', $trashedListingIds)
                            ->whereHas('cart', fn ($q) => $q->live())
                            ->count();

                        if ($liveCartItemCount > 0) {
                            Notification::make()
                                ->danger()
                                ->title(__('filament.notifications.cannot_delete_location_title'))
                                ->body(__('filament.notifications.cannot_delete_location_cart_items_body', ['count' => $liveCartItemCount]))
                                ->send();

                            $action->cancel();

                            return;
                        }

                        // Pre-clean any DEAD cart_items pointing at these trashed
                        // listings so the cascadeOnDelete path doesn't trip the
                        // cart_items.listing_id RESTRICT FK (or cart_items.hold_id
                        // via the booking_holds cascade).
                        \App\Models\CartItem::whereIn('listing_id', $trashedListingIds)->delete();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalDescription(function (Collection $records): ?string {
                            // Surface aggregate cascade impact across the selected records
                            // that smart-allow would actually delete (i.e. zero active +
                            // no LIVE cart_items referencing trashed listings). Dead
                            // cart_items will be pre-cleaned, so they don't disqualify.
                            $cascadeCount = $records
                                ->filter(function (Location $record) {
                                    if ($record->listings()->count() > 0) {
                                        return false;
                                    }
                                    $trashedIds = $record->listings()->onlyTrashed()->pluck('id');

                                    if ($trashedIds->isEmpty()) {
                                        return false;
                                    }

                                    return ! \App\Models\CartItem::query()
                                        ->whereIn('listing_id', $trashedIds)
                                        ->whereHas('cart', fn ($q) => $q->live())
                                        ->exists();
                                })
                                ->sum(fn (Location $record) => $record->listings()->onlyTrashed()->count());

                            return $cascadeCount > 0
                                ? __('filament.notifications.delete_locations_bulk_cascade_warning', ['count' => $cascadeCount])
                                : null;
                        })
                        ->before(function (Tables\Actions\DeleteBulkAction $action, Collection $records) {
                            // Bulk smart-allow: partition into (active-blocked,
                            // cart-blocked, eligible). LIVE cart_items block;
                            // dead ones get pre-cleaned before the cascade fires.
                            // Emit one danger notification per non-empty block
                            // group (matches existing per-reason UX).
                            $activeBlocked = collect();
                            $cartBlocked = collect();
                            $eligibleTrashedListingIds = collect();

                            foreach ($records as $record) {
                                if ($record->listings()->count() > 0) {
                                    $activeBlocked->push($record);

                                    continue;
                                }

                                $trashedIds = $record->listings()->onlyTrashed()->pluck('id');

                                if ($trashedIds->isEmpty()) {
                                    continue;
                                }

                                $hasLive = \App\Models\CartItem::query()
                                    ->whereIn('listing_id', $trashedIds)
                                    ->whereHas('cart', fn ($q) => $q->live())
                                    ->exists();

                                if ($hasLive) {
                                    $cartBlocked->push($record);
                                } else {
                                    $eligibleTrashedListingIds = $eligibleTrashedListingIds->merge($trashedIds);
                                }
                            }

                            if ($activeBlocked->isNotEmpty()) {
                                Notification::make()
                                    ->danger()
                                    ->title(__('filament.notifications.cannot_delete_location_title'))
                                    ->body(__('filament.notifications.cannot_delete_locations_bulk_active_body', [
                                        'names' => $activeBlocked
                                            ->map(fn (Location $record) => $record->getTranslation('name', app()->getLocale()))
                                            ->filter()
                                            ->join(', '),
                                    ]))
                                    ->send();
                            }

                            if ($cartBlocked->isNotEmpty()) {
                                Notification::make()
                                    ->danger()
                                    ->title(__('filament.notifications.cannot_delete_location_title'))
                                    ->body(__('filament.notifications.cannot_delete_locations_bulk_cart_items_body', [
                                        'names' => $cartBlocked
                                            ->map(fn (Location $record) => $record->getTranslation('name', app()->getLocale()))
                                            ->filter()
                                            ->join(', '),
                                    ]))
                                    ->send();
                            }

                            // Pre-clean dead cart_items pointing at eligible trashed
                            // listings so the cascadeOnDelete chain doesn't trip the
                            // cart_items.listing_id/.hold_id RESTRICT FKs.
                            if ($eligibleTrashedListingIds->isNotEmpty()) {
                                \App\Models\CartItem::whereIn('listing_id', $eligibleTrashedListingIds->unique())->delete();
                            }

                            $blocked = $activeBlocked->merge($cartBlocked);

                            if ($blocked->isEmpty()) {
                                return;
                            }

                            // Drop blocked records — eligible ones still get deleted.
                            // cancel() would halt the entire bulk action.
                            $action->records($records->reject(
                                fn (Location $record) => $blocked->contains($record),
                            ));
                        }),
                ]),
            ])
            ->emptyStateHeading(__('filament.empty_states.no_locations'))
            ->emptyStateDescription(__('filament.empty_states.create_first_location'))
            ->emptyStateIcon('heroicon-o-map-pin')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // LocationResource\RelationManagers\ListingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();

        return match (true) {
            $count === 0 => 'gray',
            $count < 5 => 'warning',
            default => 'success',
        };
    }
}
