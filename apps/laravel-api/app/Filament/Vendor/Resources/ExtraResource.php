<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources;

use App\Enums\ExtraCategory;
use App\Enums\ExtraPricingType;
use App\Filament\Vendor\Resources\ExtraResource\Pages;
use App\Models\Extra;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExtraResource extends Resource
{
    use Translatable;

    protected static ?string $model = Extra::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationLabel = 'Extras & Add-ons';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('vendor_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name.en')
                            ->label('Name (English)')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('name.fr')
                            ->label('Name (French)')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('short_description.en')
                            ->label('Short Description (English)')
                            ->rows(2)
                            ->maxLength(500),

                        Forms\Components\Textarea::make('short_description.fr')
                            ->label('Short Description (French)')
                            ->rows(2)
                            ->maxLength(500),

                        Forms\Components\RichEditor::make('description.en')
                            ->label('Full Description (English)')
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('description.fr')
                            ->label('Full Description (French)')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Category & Pricing')
                    ->schema([
                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->options(ExtraCategory::class)
                            ->required()
                            ->default(ExtraCategory::OTHER),

                        Forms\Components\Select::make('pricing_type')
                            ->label('Pricing Type')
                            ->options(ExtraPricingType::class)
                            ->required()
                            ->default(ExtraPricingType::PER_BOOKING)
                            ->helperText(function ($state) {
                                if (!$state) {
                                    return 'Select a pricing type';
                                }
                                $enum = $state instanceof ExtraPricingType ? $state : ExtraPricingType::tryFrom($state);
                                return $enum?->description() ?? 'Select a pricing type';
                            })
                            ->live(),

                        Forms\Components\TextInput::make('base_price_tnd')
                            ->label('Price (TND)')
                            ->numeric()
                            ->prefix('TND')
                            ->required()
                            ->minValue(0)
                            ->step(0.01),

                        Forms\Components\TextInput::make('base_price_eur')
                            ->label('Price (EUR)')
                            ->numeric()
                            ->prefix('EUR')
                            ->required()
                            ->minValue(0)
                            ->step(0.01),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Person Type Pricing')
                    ->schema([
                        Forms\Components\Repeater::make('person_type_prices')
                            ->label('Prices by Person Type')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Person Type')
                                    ->options([
                                        'adult' => 'Adult',
                                        'child' => 'Child',
                                        'infant' => 'Infant',
                                        'senior' => 'Senior',
                                    ])
                                    ->required(),

                                Forms\Components\TextInput::make('tnd')
                                    ->label('TND Price')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0),

                                Forms\Components\TextInput::make('eur')
                                    ->label('EUR Price')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add Person Type Price')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('pricing_type') === ExtraPricingType::PER_PERSON_TYPE->value)
                    ->collapsible(),

                Forms\Components\Section::make('Quantity Settings')
                    ->schema([
                        Forms\Components\TextInput::make('min_quantity')
                            ->label('Minimum Quantity')
                            ->numeric()
                            ->default(1)
                            ->minValue(0),

                        Forms\Components\TextInput::make('max_quantity')
                            ->label('Maximum Quantity')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave empty for no limit'),

                        Forms\Components\TextInput::make('default_quantity')
                            ->label('Default Quantity')
                            ->numeric()
                            ->default(1)
                            ->minValue(0),

                        Forms\Components\Toggle::make('allow_quantity_change')
                            ->label('Allow customers to change quantity')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Inventory Management')
                    ->schema([
                        Forms\Components\Toggle::make('track_inventory')
                            ->label('Track Inventory')
                            ->helperText('Enable to limit available quantities')
                            ->default(false)
                            ->live(),

                        Forms\Components\TextInput::make('inventory_count')
                            ->label('Current Stock')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->visible(fn (Forms\Get $get) => $get('track_inventory')),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Display Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_required')
                            ->label('Required Extra')
                            ->helperText('Customers must include this extra')
                            ->default(false),

                        Forms\Components\Toggle::make('auto_add')
                            ->label('Auto-add to Cart')
                            ->helperText('Automatically add to cart when booking')
                            ->default(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Only active extras are shown to customers')
                            ->default(true),

                        Forms\Components\TextInput::make('display_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Images')
                    ->schema([
                        Forms\Components\TextInput::make('image_url')
                            ->label('Image URL')
                            ->url()
                            ->maxLength(500),

                        Forms\Components\TextInput::make('thumbnail_url')
                            ->label('Thumbnail URL')
                            ->url()
                            ->maxLength(500),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->formatStateUsing(fn ($record) => $record->getTranslation('name', app()->getLocale()))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereRaw("name->>'en' ILIKE ?", ["%{$search}%"])
                            ->orWhereRaw("name->>'fr' ILIKE ?", ["%{$search}%"]);
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw("name->>'en' {$direction}");
                    }),

                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label())
                    ->color(fn ($state) => $state?->color()),

                Tables\Columns\TextColumn::make('pricing_type')
                    ->label('Pricing')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label())
                    ->toggleable(),

                Tables\Columns\TextColumn::make('base_price_tnd')
                    ->label('Price (TND)')
                    ->money('TND')
                    ->sortable(),

                Tables\Columns\TextColumn::make('base_price_eur')
                    ->label('Price (EUR)')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_required')
                    ->label('Required')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('track_inventory')
                    ->label('Inventory')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('inventory_count')
                    ->label('Stock')
                    ->visible(fn ($record) => $record?->track_inventory)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('listings_count')
                    ->label('Listings')
                    ->counts('listings')
                    ->sortable(),

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
            ->defaultSort('display_order')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(ExtraCategory::class)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('pricing_type')
                    ->options(ExtraPricingType::class),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\TernaryFilter::make('is_required')
                    ->label('Required Status')
                    ->placeholder('All')
                    ->trueLabel('Required only')
                    ->falseLabel('Optional only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (Extra $record) {
                        $newExtra = $record->replicate();
                        $newExtra->name = array_map(fn ($name) => $name . ' (Copy)', $record->name);
                        $newExtra->is_active = false;
                        $newExtra->save();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Extra')
                    ->modalDescription('Are you sure you want to delete this extra? This action cannot be undone.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->emptyStateHeading('No extras yet')
            ->emptyStateDescription('Create extras like equipment rentals, meals, or insurance that customers can add to their bookings.')
            ->emptyStateIcon('heroicon-o-puzzle-piece')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create your first extra'),
            ]);
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
            'index' => Pages\ListExtras::route('/'),
            'create' => Pages\CreateExtra::route('/create'),
            'edit' => Pages\EditExtra::route('/{record}/edit'),
        ];
    }
}
