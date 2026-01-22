<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ActivityTypeResource\Pages;
use App\Models\ActivityType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ActivityTypeResource extends Resource
{
    use Translatable;

    protected static ?string $model = ActivityType::class;

    protected static ?string $navigationIcon = null;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.activity_types');
    }

    public static function getModelLabel(): string
    {
        return __('filament.resources.activity_type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.resources.activity_types');
    }

    public static function getTranslatableLocales(): array
    {
        return ['en', 'fr'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.sections.basic_information'))
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
                            ->maxLength(100)
                            ->helperText(__('filament.helpers.slug_url_friendly'))
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('description')
                            ->label(__('filament.labels.description'))
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText(__('filament.helpers.activity_type_description')),
                    ])->columns(2),

                Forms\Components\Section::make(__('filament.sections.display_settings'))
                    ->schema([
                        Forms\Components\Select::make('icon')
                            ->label(__('filament.labels.icon'))
                            ->options([
                                'heroicon-o-building-library' => 'Building Library (Cultural)',
                                'heroicon-o-sparkles' => 'Sparkles (Adventure)',
                                'heroicon-o-lifebuoy' => 'Lifebuoy (Water)',
                                'heroicon-o-building-office' => 'Building Office (Corporate)',
                                'heroicon-o-map' => 'Map (Trekking)',
                                'heroicon-o-sun' => 'Sun (Beach)',
                                'heroicon-o-camera' => 'Camera (Photography)',
                                'heroicon-o-musical-note' => 'Musical Note (Music)',
                                'heroicon-o-fire' => 'Fire (Camping)',
                                'heroicon-o-globe-alt' => 'Globe (Travel)',
                                'heroicon-o-heart' => 'Heart (Wellness)',
                                'heroicon-o-truck' => 'Truck (Safari)',
                            ])
                            ->searchable()
                            ->columnSpan(1),

                        Forms\Components\ColorPicker::make('color')
                            ->label(__('filament.labels.color'))
                            ->helperText(__('filament.helpers.badge_color'))
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('display_order')
                            ->label(__('filament.labels.display_order'))
                            ->numeric()
                            ->default(0)
                            ->helperText(__('filament.helpers.display_order'))
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('filament.labels.is_active'))
                            ->default(true)
                            ->helperText(__('filament.helpers.activity_type_active'))
                            ->columnSpan(1),
                    ])->columns(2),

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
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament.labels.name'))
                    ->searchable()
                    ->weight('medium')
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label(__('filament.labels.slug'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Slug copied!')
                    ->color('gray')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('icon')
                    ->label(__('filament.labels.icon'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? str_replace('heroicon-o-', '', $state) : '—'),

                Tables\Columns\ColorColumn::make('color')
                    ->label(__('filament.labels.color'))
                    ->copyable()
                    ->copyMessage('Color copied!'),

                Tables\Columns\TextColumn::make('display_order')
                    ->label(__('filament.labels.order'))
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('listings_count')
                    ->label(__('filament.labels.listings'))
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'gray',
                        $state < 5 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('filament.labels.active'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('display_order', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('filament.labels.active'))
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\Filter::make('has_listings')
                    ->label(__('filament.filters.has_listings'))
                    ->query(fn ($query) => $query->where('listings_count', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (ActivityType $record) {
                        // Prevent deletion if activity type has listings
                        if ($record->listings_count > 0) {
                            throw new \Exception("Cannot delete activity type with {$record->listings_count} existing listing(s). Please reassign or remove them first.");
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // Prevent bulk deletion if any activity type has listings
                            $hasListings = $records->filter(fn ($record) => $record->listings_count > 0);

                            if ($hasListings->isNotEmpty()) {
                                $names = $hasListings->pluck('name')->join(', ');

                                throw new \Exception("Cannot delete activity types with existing listings: {$names}");
                            }
                        }),
                ]),
            ])
            ->emptyStateHeading(__('filament.empty_states.no_activity_types'))
            ->emptyStateDescription(__('filament.empty_states.create_first_activity_type'))
            ->emptyStateIcon('heroicon-o-tag')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->reorderable('display_order');
    }

    public static function getRelations(): array
    {
        return [
            // Could add ListingsRelationManager here if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityTypes::route('/'),
            'create' => Pages\CreateActivityType::route('/create'),
            'edit' => Pages\EditActivityType::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return (string) static::getModel()::count();
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        try {
            $count = static::getModel()::count();

            return match (true) {
                $count === 0 => 'gray',
                $count < 5 => 'warning',
                default => 'success',
            };
        } catch (\Exception $e) {
            return 'gray';
        }
    }
}
