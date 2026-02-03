<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\ListingResource\RelationManagers;

use App\Enums\ExtraCategory;
use App\Models\Extra;
use App\Models\ExtraTemplate;
use App\Models\ListingExtra;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
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
                    ->formatStateUsing(function ($record) {
                        $name = $record->getTranslation('name', app()->getLocale());

                        if (is_array($name)) {
                            $name = $name[app()->getLocale()] ?? $name['en'] ?? reset($name) ?: 'Unnamed';
                        }

                        return $name ?: 'Unnamed';
                    })
                    ->description(fn ($record) => $record->category?->label() ?? 'Other')
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
            ->defaultSort('listing_extras.display_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->queries(
                        true: fn (Builder $query) => $query->wherePivot('is_active', true),
                        false: fn (Builder $query) => $query->wherePivot('is_active', false),
                    ),
            ])
            ->headerActions([
                // Action to add existing vendor extras to this listing
                Tables\Actions\Action::make('attach')
                    ->label('Add Existing Extra')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Add Extra from Your Library')
                    ->visible(fn () => Extra::where('vendor_id', auth()->id())->where('is_active', true)->exists())
                    ->form([
                        Forms\Components\Select::make('extra_id')
                            ->label('Extra')
                            ->options(function () {
                                return Extra::query()
                                    ->where('vendor_id', auth()->id())
                                    ->where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(function (Extra $extra) {
                                        // Safely get translatable name
                                        $name = $extra->getTranslation('name', app()->getLocale());

                                        if (is_array($name)) {
                                            $name = $name[app()->getLocale()] ?? $name['en'] ?? reset($name) ?: 'Unnamed';
                                        }
                                        $name = $name ?: 'Unnamed';

                                        return [
                                            $extra->id => $name . ' - ' . ($extra->category?->label() ?? 'Other'),
                                        ];
                                    });
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
                        $listingId = $livewire->getOwnerRecord()->id;
                        $extraId = $data['extra_id'];

                        // Check if this extra is already attached to the listing
                        $exists = ListingExtra::where('listing_id', $listingId)
                            ->where('extra_id', $extraId)
                            ->exists();

                        if ($exists) {
                            Notification::make()
                                ->title('Extra already attached')
                                ->body('This extra is already added to the listing.')
                                ->warning()
                                ->send();

                            return;
                        }

                        try {
                            // Create pivot record directly with UUID
                            ListingExtra::create([
                                'id' => (string) Str::uuid(),
                                'listing_id' => $listingId,
                                'extra_id' => $extraId,
                                'override_price_tnd' => $data['override_price_tnd'] ?? null,
                                'override_price_eur' => $data['override_price_eur'] ?? null,
                                'display_order' => $data['display_order'] ?? 0,
                                'is_featured' => $data['is_featured'] ?? false,
                                'is_active' => $data['is_active'] ?? true,
                            ]);

                            Notification::make()
                                ->title('Extra added')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Log::error('Failed to attach extra to listing', [
                                'listing_id' => $listingId,
                                'extra_id' => $extraId,
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('Failed to add extra')
                                ->body('An error occurred. Please try again.')
                                ->danger()
                                ->send();
                        }
                    }),

                // Action to create a new extra from a template and attach it
                Tables\Actions\Action::make('createFromTemplate')
                    ->label('Create from Template')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('success')
                    ->modalHeading('Create Extra from Template')
                    ->modalDescription('Select a template to create a new extra and add it to this listing.')
                    ->form([
                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->options(ExtraCategory::class)
                            ->placeholder('All categories')
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('template_id', null)),

                        Forms\Components\Select::make('template_id')
                            ->label('Template')
                            ->options(function (Get $get) {
                                $query = ExtraTemplate::active()->ordered();

                                if ($category = $get('category')) {
                                    $query->where('category', $category);
                                }

                                return $query->get()->mapWithKeys(function (ExtraTemplate $template) {
                                    // Safely get translatable name
                                    $name = $template->getTranslation('name', app()->getLocale());

                                    if (is_array($name)) {
                                        $name = $name[app()->getLocale()] ?? $name['en'] ?? reset($name) ?: 'Unnamed';
                                    }
                                    $name = $name ?: 'Unnamed';

                                    return [
                                        $template->id => $name .
                                            ' - ' . ($template->category?->label() ?? 'Other') .
                                            ' (' . number_format($template->suggested_price_tnd ?? 0, 2) . ' TND)',
                                    ];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('The template will be cloned to your extras library and attached to this listing.'),

                        Forms\Components\Section::make('Customize Pricing (Optional)')
                            ->description('Override the template\'s suggested prices for this listing.')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('override_price_tnd')
                                            ->label('Price (TND)')
                                            ->numeric()
                                            ->prefix('TND')
                                            ->step(0.01)
                                            ->helperText('Leave empty to use template price'),

                                        Forms\Components\TextInput::make('override_price_eur')
                                            ->label('Price (EUR)')
                                            ->numeric()
                                            ->prefix('EUR')
                                            ->step(0.01)
                                            ->helperText('Leave empty to use template price'),
                                    ]),
                            ])
                            ->collapsible()
                            ->collapsed(),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        $template = ExtraTemplate::find($data['template_id']);

                        if (! $template) {
                            Notification::make()
                                ->title('Template not found')
                                ->danger()
                                ->send();

                            return;
                        }

                        try {
                            // Clone template to vendor's extras
                            $extra = $template->cloneForVendor(auth()->id());

                            // If custom prices provided, update the extra
                            if (! empty($data['override_price_tnd'])) {
                                $extra->base_price_tnd = $data['override_price_tnd'];
                            }

                            if (! empty($data['override_price_eur'])) {
                                $extra->base_price_eur = $data['override_price_eur'];
                            }

                            // Activate the extra so it's ready to use
                            $extra->is_active = true;
                            $extra->save();

                            // Attach to listing
                            ListingExtra::create([
                                'id' => (string) Str::uuid(),
                                'listing_id' => $livewire->getOwnerRecord()->id,
                                'extra_id' => $extra->id,
                                'display_order' => 0,
                                'is_featured' => false,
                                'is_active' => true,
                            ]);

                            // Safely get name for notification
                            $extraName = $extra->getTranslation('name', 'en');

                            if (is_array($extraName)) {
                                $extraName = $extraName['en'] ?? reset($extraName) ?: 'Extra';
                            }
                            $extraName = $extraName ?: 'Extra';

                            Notification::make()
                                ->title('Extra created and added')
                                ->body("'{$extraName}' has been created and added to this listing.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Log::error('Failed to create extra from template', [
                                'template_id' => $data['template_id'],
                                'listing_id' => $livewire->getOwnerRecord()->id,
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('Failed to create extra')
                                ->body('An error occurred. Please try again.')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit Override')
                    ->modalHeading(function ($record) {
                        $name = $record->getTranslation('name', app()->getLocale());

                        if (is_array($name)) {
                            $name = $name[app()->getLocale()] ?? $name['en'] ?? reset($name) ?: 'Extra';
                        }

                        return 'Edit: ' . ($name ?: 'Extra');
                    }),

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
            ->emptyStateDescription('Add extras like equipment, meals, or insurance to enhance your listing.')
            ->emptyStateIcon('heroicon-o-puzzle-piece')
            ->emptyStateActions([
                // Primary action: Create from Template (most common use case)
                Tables\Actions\Action::make('createFromTemplateEmpty')
                    ->label('Create from Template')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('success')
                    ->modalHeading('Create Extra from Template')
                    ->modalDescription('Select a template to create a new extra and add it to this listing.')
                    ->form([
                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->options(ExtraCategory::class)
                            ->placeholder('All categories')
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('template_id', null)),

                        Forms\Components\Select::make('template_id')
                            ->label('Template')
                            ->options(function (Get $get) {
                                $query = ExtraTemplate::active()->ordered();

                                if ($category = $get('category')) {
                                    $query->where('category', $category);
                                }

                                return $query->get()->mapWithKeys(function (ExtraTemplate $template) {
                                    // Safely get translatable name
                                    $name = $template->getTranslation('name', app()->getLocale());

                                    if (is_array($name)) {
                                        $name = $name[app()->getLocale()] ?? $name['en'] ?? reset($name) ?: 'Unnamed';
                                    }
                                    $name = $name ?: 'Unnamed';

                                    return [
                                        $template->id => $name .
                                            ' - ' . ($template->category?->label() ?? 'Other') .
                                            ' (' . number_format($template->suggested_price_tnd ?? 0, 2) . ' TND)',
                                    ];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Choose from common extras like breakfast, transport, equipment, etc.'),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        $template = ExtraTemplate::find($data['template_id']);

                        if (! $template) {
                            Notification::make()
                                ->title('Template not found')
                                ->danger()
                                ->send();

                            return;
                        }

                        try {
                            // Clone template to vendor's extras
                            $extra = $template->cloneForVendor(auth()->id());
                            $extra->is_active = true;
                            $extra->save();

                            // Attach to listing
                            ListingExtra::create([
                                'id' => (string) Str::uuid(),
                                'listing_id' => $livewire->getOwnerRecord()->id,
                                'extra_id' => $extra->id,
                                'display_order' => 0,
                                'is_featured' => false,
                                'is_active' => true,
                            ]);

                            // Safely get name for notification
                            $extraName = $extra->getTranslation('name', 'en');

                            if (is_array($extraName)) {
                                $extraName = $extraName['en'] ?? reset($extraName) ?: 'Extra';
                            }
                            $extraName = $extraName ?: 'Extra';

                            Notification::make()
                                ->title('Extra created and added')
                                ->body("'{$extraName}' has been added to this listing.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Log::error('Failed to create extra from template (empty state)', [
                                'template_id' => $data['template_id'],
                                'listing_id' => $livewire->getOwnerRecord()->id,
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('Failed to create extra')
                                ->body('An error occurred. Please try again.')
                                ->danger()
                                ->send();
                        }
                    }),

                // Secondary action: Add existing extra (only visible if vendor has extras)
                Tables\Actions\Action::make('attachFirst')
                    ->label('Add Existing Extra')
                    ->icon('heroicon-o-plus')
                    ->color('gray')
                    ->visible(fn () => Extra::where('vendor_id', auth()->id())->where('is_active', true)->exists())
                    ->modalHeading('Add Extra from Your Library')
                    ->form([
                        Forms\Components\Select::make('extra_id')
                            ->label('Extra')
                            ->options(function () {
                                return Extra::query()
                                    ->where('vendor_id', auth()->id())
                                    ->where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(function (Extra $extra) {
                                        // Safely get translatable name
                                        $name = $extra->getTranslation('name', app()->getLocale());

                                        if (is_array($name)) {
                                            $name = $name[app()->getLocale()] ?? $name['en'] ?? reset($name) ?: 'Unnamed';
                                        }
                                        $name = $name ?: 'Unnamed';

                                        return [
                                            $extra->id => $name . ' - ' . ($extra->category?->label() ?? 'Other'),
                                        ];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        $listingId = $livewire->getOwnerRecord()->id;
                        $extraId = $data['extra_id'];

                        // Check if this extra is already attached to the listing
                        $exists = ListingExtra::where('listing_id', $listingId)
                            ->where('extra_id', $extraId)
                            ->exists();

                        if ($exists) {
                            Notification::make()
                                ->title('Extra already attached')
                                ->body('This extra is already added to the listing.')
                                ->warning()
                                ->send();

                            return;
                        }

                        try {
                            ListingExtra::create([
                                'id' => (string) Str::uuid(),
                                'listing_id' => $listingId,
                                'extra_id' => $extraId,
                                'display_order' => 0,
                                'is_featured' => false,
                                'is_active' => $data['is_active'] ?? true,
                            ]);

                            Notification::make()
                                ->title('Extra added')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Log::error('Failed to attach extra to listing (empty state)', [
                                'listing_id' => $listingId,
                                'extra_id' => $extraId,
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('Failed to add extra')
                                ->body('An error occurred. Please try again.')
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}
