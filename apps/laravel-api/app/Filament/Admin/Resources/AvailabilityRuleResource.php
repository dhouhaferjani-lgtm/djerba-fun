<?php

namespace App\Filament\Admin\Resources;

use App\Enums\AvailabilityRuleType;
use App\Filament\Admin\Resources\AvailabilityRuleResource\Pages;
use App\Models\AvailabilityRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AvailabilityRuleResource extends Resource
{
    protected static ?string $model = AvailabilityRule::class;

    protected static ?string $navigationIcon = null;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.availability_rules');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.availability_rule.basic_information'))
                    ->schema([
                        Forms\Components\Select::make('listing_id')
                            ->label(__('filament.availability_rule.listing'))
                            ->relationship(
                                name: 'listing',
                                modifyQueryUsing: fn (Builder $query) => $query->orderBy('slug'),
                            )
                            ->getOptionLabelFromRecordUsing(function ($record): string {
                        $title = $record->getTranslation('title', app()->getLocale());

                        return is_string($title) && ! empty($title) ? $title : $record->slug;
                    })
                            ->searchable(['slug'])
                            ->required()
                            ->preload(),

                        Forms\Components\Select::make('rule_type')
                            ->label(__('filament.availability_rule.rule_type'))
                            ->options([
                                AvailabilityRuleType::WEEKLY->value => AvailabilityRuleType::WEEKLY->label(),
                                AvailabilityRuleType::DAILY->value => AvailabilityRuleType::DAILY->label(),
                                AvailabilityRuleType::SPECIFIC_DATES->value => AvailabilityRuleType::SPECIFIC_DATES->label(),
                                AvailabilityRuleType::BLOCKED_DATES->value => AvailabilityRuleType::BLOCKED_DATES->label(),
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                // Set sensible defaults for days_of_week based on rule type
                                if (in_array($state, [AvailabilityRuleType::WEEKLY->value, AvailabilityRuleType::DAILY->value])) {
                                    $set('days_of_week', [0, 1, 2, 3, 4, 5, 6]); // All days by default
                                } else {
                                    $set('days_of_week', null);
                                }
                            }),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('filament.availability_rule.active'))
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make(__('filament.availability_rule.schedule'))
                    ->schema([
                        Forms\Components\CheckboxList::make('days_of_week')
                            ->label(__('filament.availability_rule.days_of_week'))
                            ->options([
                                0 => __('filament.availability_rule.sunday'),
                                1 => __('filament.availability_rule.monday'),
                                2 => __('filament.availability_rule.tuesday'),
                                3 => __('filament.availability_rule.wednesday'),
                                4 => __('filament.availability_rule.thursday'),
                                5 => __('filament.availability_rule.friday'),
                                6 => __('filament.availability_rule.saturday'),
                            ])
                            ->columns(7)
                            ->visible(fn (Forms\Get $get): bool => in_array($get('rule_type'), [
                                AvailabilityRuleType::WEEKLY->value,
                                AvailabilityRuleType::DAILY->value,
                            ]))
                            ->required(fn (Forms\Get $get): bool => in_array($get('rule_type'), [
                                AvailabilityRuleType::WEEKLY->value,
                                AvailabilityRuleType::DAILY->value,
                            ]))
                            ->default([0, 1, 2, 3, 4, 5, 6])
                            ->helperText(__('filament.availability_rule.days_of_week_helper') ?? 'Select the days when this availability applies.'),

                        Forms\Components\TimePicker::make('start_time')
                            ->label(__('filament.availability_rule.start_time'))
                            ->seconds(false),

                        Forms\Components\TimePicker::make('end_time')
                            ->label(__('filament.availability_rule.end_time'))
                            ->seconds(false),

                        Forms\Components\DatePicker::make('start_date')
                            ->label(__('filament.availability_rule.start_date'))
                            ->native(false),

                        Forms\Components\DatePicker::make('end_date')
                            ->label(__('filament.availability_rule.end_date'))
                            ->native(false)
                            ->after('start_date'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('filament.availability_rule.capacity_pricing'))
                    ->schema([
                        Forms\Components\TextInput::make('capacity')
                            ->label(__('filament.availability_rule.capacity'))
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),

                        Forms\Components\TextInput::make('price_override')
                            ->label(__('filament.availability_rule.price_override'))
                            ->numeric()
                            ->prefix('$')
                            ->step('0.01')
                            ->helperText(__('filament.availability_rule.price_override_helper')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('listing_title_display')
                    ->label(__('filament.availability_rule.listing'))
                    ->getStateUsing(function ($record): string {
                        $title = $record->listing?->getTranslation('title', app()->getLocale());
                        if (is_string($title) && ! empty($title)) {
                            return $title;
                        }

                        return $record->listing?->slug ?? '-';
                    })
                    ->limit(30)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('listing', function ($q) use ($search) {
                            $q->where('slug', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('rule_type')
                    ->label(__('filament.availability_rule.type'))
                    ->formatStateUsing(fn (AvailabilityRuleType $state): string => $state->label())
                    ->badge()
                    ->color(fn (AvailabilityRuleType $state): string => match ($state) {
                        AvailabilityRuleType::WEEKLY => 'info',
                        AvailabilityRuleType::DAILY => 'success',
                        AvailabilityRuleType::SPECIFIC_DATES => 'warning',
                        AvailabilityRuleType::BLOCKED_DATES => 'danger',
                    }),

                Tables\Columns\TextColumn::make('start_time')
                    ->label(__('filament.availability_rule.time'))
                    ->formatStateUsing(
                        fn ($record) => $record->start_time && $record->end_time
                            ? $record->start_time->format('H:i') . ' - ' . $record->end_time->format('H:i')
                            : '-'
                    ),

                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('filament.availability_rule.date_range'))
                    ->formatStateUsing(
                        fn ($record) => $record->start_date && $record->end_date
                            ? $record->start_date->format('M d, Y') . ' - ' . $record->end_date->format('M d, Y')
                            : ($record->start_date ? __('filament.availability_rule.from') . ' ' . $record->start_date->format('M d, Y') : __('filament.availability_rule.ongoing'))
                    )
                    ->wrap(),

                Tables\Columns\TextColumn::make('capacity')
                    ->label(__('filament.availability_rule.capacity'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('filament.availability_rule.active'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.availability_rule.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('listing_id')
                    ->label(__('filament.availability_rule.listing'))
                    ->options(function () {
                        return \App\Models\Listing::query()
                            ->orderBy('slug')
                            ->get()
                            ->mapWithKeys(function ($listing) {
                                $title = $listing->getTranslation('title', app()->getLocale());
                                $displayTitle = is_string($title) && ! empty($title) ? $title : $listing->slug;

                                return [$listing->id => $displayTitle];
                            });
                    })
                    ->searchable(),

                Tables\Filters\SelectFilter::make('rule_type')
                    ->label(__('filament.availability_rule.rule_type'))
                    ->options([
                        AvailabilityRuleType::WEEKLY->value => AvailabilityRuleType::WEEKLY->label(),
                        AvailabilityRuleType::DAILY->value => AvailabilityRuleType::DAILY->label(),
                        AvailabilityRuleType::SPECIFIC_DATES->value => AvailabilityRuleType::SPECIFIC_DATES->label(),
                        AvailabilityRuleType::BLOCKED_DATES->value => AvailabilityRuleType::BLOCKED_DATES->label(),
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('filament.availability_rule.active'))
                    ->placeholder(__('filament.availability_rule.all_rules'))
                    ->trueLabel(__('filament.availability_rule.active_only'))
                    ->falseLabel(__('filament.availability_rule.inactive_only')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading(__('filament.availability_rule.delete_heading'))
                    ->modalDescription(__('filament.availability_rule.delete_description')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
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
            'index' => Pages\ListAvailabilityRules::route('/'),
            'create' => Pages\CreateAvailabilityRule::route('/create'),
            'edit' => Pages\EditAvailabilityRule::route('/{record}/edit'),
        ];
    }
}
