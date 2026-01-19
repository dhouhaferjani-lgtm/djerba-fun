<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LocationResource\Pages;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
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
                            ->maxLength(100)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('region')
                            ->label(__('filament.labels.region'))
                            ->maxLength(100)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('country')
                            ->label(__('filament.labels.country'))
                            ->required()
                            ->default('Tunisia')
                            ->maxLength(100)
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
                                ->readOnly()
                                ->dehydrated()
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('longitude')
                                ->label(__('filament.labels.longitude'))
                                ->numeric()
                                ->readOnly()
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
                        'Tunisia' => 'Tunisia',
                        'Morocco' => 'Morocco',
                        'Algeria' => 'Algeria',
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
                    ->before(function (Location $record) {
                        // Prevent deletion if location has listings
                        if ($record->listings_count > 0) {
                            throw new \Exception("Cannot delete location with {$record->listings_count} existing listing(s). Please reassign or delete them first.");
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // Prevent bulk deletion if any location has listings
                            $hasListings = $records->filter(fn ($record) => $record->listings_count > 0);

                            if ($hasListings->isNotEmpty()) {
                                $names = $hasListings->pluck('name')->join(', ');

                                throw new \Exception("Cannot delete locations with existing listings: {$names}");
                            }
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
