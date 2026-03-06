<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\ServiceType;
use App\Enums\TagType;
use App\Filament\Admin\Resources\TagResource\Pages;
use App\Models\Tag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TagResource extends Resource
{
    use Translatable;

    protected static ?string $model = Tag::class;

    protected static ?string $navigationIcon = null;

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.content');
    }

    public static function getNavigationLabel(): string
    {
        return 'Tags';
    }

    public static function getModelLabel(): string
    {
        return 'Tag';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Tags';
    }

    public static function getTranslatableLocales(): array
    {
        return ['en', 'fr'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tag Type')
                            ->options(collect(TagType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()]))
                            ->required()
                            ->native(false)
                            ->columnSpan(1)
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                if ($state) {
                                    $tagType = TagType::from($state);
                                    $set('applicable_service_types', $tagType->applicableServiceTypes());
                                }
                            }),

                        Forms\Components\TextInput::make('name')
                            ->label('Name')
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
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->helperText('URL-friendly identifier')
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Applicable Service Types')
                    ->description('Which service types can use this tag')
                    ->schema([
                        Forms\Components\CheckboxList::make('applicable_service_types')
                            ->label('Service Types')
                            ->options(collect(ServiceType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()]))
                            ->columns(4)
                            ->gridDirection('row')
                            ->helperText('Leave empty to allow for all service types'),
                    ]),

                Forms\Components\Section::make('Display Settings')
                    ->schema([
                        Forms\Components\Select::make('icon')
                            ->label('Icon')
                            ->options([
                                'heroicon-o-map' => 'Map',
                                'heroicon-o-bolt' => 'Bolt',
                                'heroicon-o-eye' => 'Eye',
                                'heroicon-o-sun' => 'Sun',
                                'heroicon-o-fire' => 'Fire',
                                'heroicon-o-heart' => 'Heart',
                                'heroicon-o-home' => 'Home',
                                'heroicon-o-home-modern' => 'Home Modern',
                                'heroicon-o-building-library' => 'Building Library',
                                'heroicon-o-building-office-2' => 'Building Office',
                                'heroicon-o-building-storefront' => 'Storefront',
                                'heroicon-o-lifebuoy' => 'Lifebuoy',
                                'heroicon-o-rocket-launch' => 'Rocket',
                                'heroicon-o-paper-airplane' => 'Paper Airplane',
                                'heroicon-o-cloud' => 'Cloud',
                                'heroicon-o-truck' => 'Truck',
                                'heroicon-o-wifi' => 'WiFi',
                                'heroicon-o-sparkles' => 'Sparkles',
                                'heroicon-o-cake' => 'Cake',
                                'heroicon-o-beaker' => 'Beaker',
                                'heroicon-o-cube' => 'Cube',
                                'heroicon-o-globe-alt' => 'Globe',
                                'heroicon-o-musical-note' => 'Musical Note',
                                'heroicon-o-presentation-chart-bar' => 'Presentation',
                                'heroicon-o-check-badge' => 'Check Badge',
                                'heroicon-o-tag' => 'Tag',
                            ])
                            ->searchable()
                            ->columnSpan(1),

                        Forms\Components\ColorPicker::make('color')
                            ->label('Color')
                            ->helperText('Badge color for display')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('display_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first')
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive tags won\'t be shown to users')
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('Statistics')
                    ->schema([
                        Forms\Components\TextInput::make('listings_count')
                            ->label('Number of Listings')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Automatically calculated'),
                    ])
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? 'Unknown')
                    ->color(fn ($state) => match ($state?->value ?? '') {
                        'tour_type' => 'success',
                        'boat_type' => 'info',
                        'space_type' => 'warning',
                        'event_feature' => 'danger',
                        'amenity' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->weight('medium')
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Slug copied!')
                    ->color('gray')
                    ->size('sm')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('icon')
                    ->label('Icon')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? str_replace('heroicon-o-', '', $state) : '—'),

                Tables\Columns\ColorColumn::make('color')
                    ->label('Color')
                    ->copyable()
                    ->copyMessage('Color copied!'),

                Tables\Columns\TextColumn::make('applicable_service_types')
                    ->label('Services')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return 'All';
                        }

                        return collect($state)->map(fn ($v) => ucfirst($v))->join(', ');
                    })
                    ->badge()
                    ->color('gray')
                    ->wrap(),

                Tables\Columns\TextColumn::make('display_order')
                    ->label('Order')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('listings_count')
                    ->label('Listings')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'gray',
                        $state < 5 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('type')
            ->groups([
                Tables\Grouping\Group::make('type')
                    ->label('Tag Type')
                    ->getTitleFromRecordUsing(fn (Tag $record): string => $record->type?->label() ?? 'Unknown'),
            ])
            ->defaultGroup('type')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tag Type')
                    ->options(collect(TagType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()]))
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\Filter::make('has_listings')
                    ->label('Has Listings')
                    ->query(fn ($query) => $query->where('listings_count', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tag $record) {
                        if ($record->listings_count > 0) {
                            throw new \Exception("Cannot delete tag with {$record->listings_count} existing listing(s). Please remove them first.");
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            $hasListings = $records->filter(fn ($record) => $record->listings_count > 0);

                            if ($hasListings->isNotEmpty()) {
                                $names = $hasListings->pluck('name')->join(', ');

                                throw new \Exception("Cannot delete tags with existing listings: {$names}");
                            }
                        }),
                ]),
            ])
            ->emptyStateHeading('No tags yet')
            ->emptyStateDescription('Create your first tag to categorize listings')
            ->emptyStateIcon('heroicon-o-tag')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->reorderable('display_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTags::route('/'),
            'create' => Pages\CreateTag::route('/create'),
            'edit' => Pages\EditTag::route('/{record}/edit'),
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
        return 'gray';
    }
}
