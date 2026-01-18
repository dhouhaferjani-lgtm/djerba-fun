<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\ListingResource\RelationManagers;

use App\Models\Extra;
use App\Models\ListingExtra;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ExtrasRelationManager extends RelationManager
{
    protected static string $relationship = 'extras';

    protected static ?string $title = 'Extras & Add-ons';

    protected static ?string $icon = 'heroicon-o-puzzle-piece';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pricing Overrides')
                    ->description('Override the default pricing for this specific listing. Leave empty to use the extra\'s default price.')
                    ->schema([
                        Forms\Components\TextInput::make('override_price_tnd')
                            ->label('Price (TND)')
                            ->numeric()
                            ->prefix('TND')
                            ->step(0.01)
                            ->helperText('Leave empty to use default price'),

                        Forms\Components\TextInput::make('override_price_eur')
                            ->label('Price (EUR)')
                            ->numeric()
                            ->prefix('EUR')
                            ->step(0.01)
                            ->helperText('Leave empty to use default price'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Quantity Overrides')
                    ->schema([
                        Forms\Components\TextInput::make('override_min_quantity')
                            ->label('Minimum Quantity')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Leave empty to use default'),

                        Forms\Components\TextInput::make('override_max_quantity')
                            ->label('Maximum Quantity')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave empty to use default'),

                        Forms\Components\Toggle::make('override_is_required')
                            ->label('Required for this listing')
                            ->helperText('Override whether this extra is required'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Display Settings')
                    ->schema([
                        Forms\Components\TextInput::make('display_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured')
                            ->helperText('Show prominently in the booking flow'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Only active extras are shown to customers'),
                    ])
                    ->columns(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Extra')
                    ->formatStateUsing(fn ($record) => $record->getTranslation('name', app()->getLocale()))
                    ->description(fn ($record) => $record->category?->label())
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereRaw("name->>'en' ILIKE ?", ["%{$search}%"]);
                    }),

                Tables\Columns\TextColumn::make('pricing_type')
                    ->label('Pricing')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label()),

                Tables\Columns\TextColumn::make('base_price_tnd')
                    ->label('Default (TND)')
                    ->money('TND')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('pivot.override_price_tnd')
                    ->label('Override (TND)')
                    ->money('TND')
                    ->placeholder('-')
                    ->color('success'),

                Tables\Columns\TextColumn::make('pivot.display_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\IconColumn::make('pivot.is_featured')
                    ->label('Featured')
                    ->boolean(),

                Tables\Columns\IconColumn::make('pivot.is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('pivot.display_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->queries(
                        true: fn (Builder $query) => $query->wherePivot('is_active', true),
                        false: fn (Builder $query) => $query->wherePivot('is_active', false),
                    ),
            ])
            ->headerActions([
                // Custom action that creates pivot record with UUID directly
                // instead of using attach() which bypasses the model's HasUuids trait
                Tables\Actions\Action::make('attach')
                    ->label('Add Extra')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Add Extra')
                    ->form([
                        Forms\Components\Select::make('extra_id')
                            ->label('Extra')
                            ->options(function () {
                                return Extra::query()
                                    ->where('vendor_id', auth()->id())
                                    ->where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(fn (Extra $extra) => [
                                        $extra->id => $extra->getTranslation('name', app()->getLocale()) . ' - ' . $extra->category?->label(),
                                    ]);
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Section::make('Listing-Specific Settings')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('override_price_tnd')
                                            ->label('Override Price (TND)')
                                            ->numeric()
                                            ->prefix('TND')
                                            ->step(0.01)
                                            ->helperText('Leave empty to use default'),

                                        Forms\Components\TextInput::make('override_price_eur')
                                            ->label('Override Price (EUR)')
                                            ->numeric()
                                            ->prefix('EUR')
                                            ->step(0.01)
                                            ->helperText('Leave empty to use default'),
                                    ]),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('display_order')
                                            ->label('Display Order')
                                            ->numeric()
                                            ->default(0),

                                        Forms\Components\Toggle::make('is_featured')
                                            ->label('Featured')
                                            ->default(false),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Active')
                                            ->default(true),
                                    ]),
                            ]),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        // Create pivot record directly with UUID
                        ListingExtra::create([
                            'id' => (string) Str::uuid(),
                            'listing_id' => $livewire->getOwnerRecord()->id,
                            'extra_id' => $data['extra_id'],
                            'override_price_tnd' => $data['override_price_tnd'] ?? null,
                            'override_price_eur' => $data['override_price_eur'] ?? null,
                            'display_order' => $data['display_order'] ?? 0,
                            'is_featured' => $data['is_featured'] ?? false,
                            'is_active' => $data['is_active'] ?? true,
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit Override')
                    ->modalHeading(fn ($record) => 'Edit: ' . $record->getTranslation('name', app()->getLocale())),

                Tables\Actions\DetachAction::make()
                    ->label('Remove'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Remove Selected'),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records, RelationManager $livewire) {
                            foreach ($records as $record) {
                                $livewire->getOwnerRecord()->extras()->updateExistingPivot($record->id, ['is_active' => true]);
                            }
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records, RelationManager $livewire) {
                            foreach ($records as $record) {
                                $livewire->getOwnerRecord()->extras()->updateExistingPivot($record->id, ['is_active' => false]);
                            }
                        }),
                ]),
            ])
            ->emptyStateHeading('No extras attached')
            ->emptyStateDescription('Add extras like equipment, meals, or insurance to this listing.')
            ->emptyStateIcon('heroicon-o-puzzle-piece')
            ->emptyStateActions([
                // Custom action that creates pivot record with UUID directly
                // instead of using attach() which bypasses the model's HasUuids trait
                Tables\Actions\Action::make('attachFirst')
                    ->label('Add your first extra')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Add Extra')
                    ->form([
                        Forms\Components\Select::make('extra_id')
                            ->label('Extra')
                            ->options(function () {
                                return Extra::query()
                                    ->where('vendor_id', auth()->id())
                                    ->where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(fn (Extra $extra) => [
                                        $extra->id => $extra->getTranslation('name', app()->getLocale()) . ' - ' . $extra->category?->label(),
                                    ]);
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        // Create pivot record directly with UUID
                        ListingExtra::create([
                            'id' => (string) Str::uuid(),
                            'listing_id' => $livewire->getOwnerRecord()->id,
                            'extra_id' => $data['extra_id'],
                            'display_order' => 0,
                            'is_featured' => false,
                            'is_active' => $data['is_active'] ?? true,
                        ]);
                    }),
            ]);
    }
}
