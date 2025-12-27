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

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Locations';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getTranslatableLocales(): array
    {
        return ['en', 'fr'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Location Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
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
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('URL-friendly identifier (auto-generated from name)')
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull()
                            ->helperText('Rich description for destination landing pages'),

                        Forms\Components\TextInput::make('image_url')
                            ->label('Image URL')
                            ->url()
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->helperText('Full URL to destination hero image (e.g., from Unsplash or uploaded to MinIO)'),
                    ])->columns(2),

                Forms\Components\Section::make('Geographic Information')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('city')
                            ->maxLength(100)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('region')
                            ->label('Region/State')
                            ->maxLength(100)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('country')
                            ->required()
                            ->default('Tunisia')
                            ->maxLength(100)
                            ->columnSpan(1),

                        Forms\Components\Select::make('timezone')
                            ->options([
                                'Africa/Tunis' => 'Africa/Tunis (UTC+1)',
                                'Europe/Paris' => 'Europe/Paris (UTC+1/+2)',
                                'Europe/London' => 'Europe/London (UTC+0/+1)',
                            ])
                            ->default('Africa/Tunis')
                            ->required()
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('Map Coordinates')
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
                                ->numeric()
                                ->readOnly()
                                ->dehydrated()
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('longitude')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated()
                                ->columnSpan(1),
                        ]),
                    ]),

                Forms\Components\Section::make('Statistics')
                    ->schema([
                        Forms\Components\TextInput::make('listings_count')
                            ->label('Number of Listings')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Auto-calculated based on published listings'),
                    ])
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->width(80)
                    ->height(60)
                    ->defaultImageUrl(url('/images/placeholder-location.jpg')),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Slug copied!')
                    ->color('gray')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('region')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('country')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('listings_count')
                    ->label('Listings')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'gray',
                        $state < 5 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('latitude')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('longitude')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('country')
                    ->options([
                        'Tunisia' => 'Tunisia',
                        'Morocco' => 'Morocco',
                        'Algeria' => 'Algeria',
                    ]),

                Tables\Filters\Filter::make('has_listings')
                    ->label('Has Listings')
                    ->query(fn ($query) => $query->where('listings_count', '>', 0)),

                Tables\Filters\Filter::make('has_coordinates')
                    ->label('Has Coordinates')
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
            ->emptyStateHeading('No locations yet')
            ->emptyStateDescription('Create your first destination/location to organize listings')
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
