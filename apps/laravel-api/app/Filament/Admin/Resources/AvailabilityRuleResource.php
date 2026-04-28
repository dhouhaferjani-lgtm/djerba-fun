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
                                if (in_array($state, [AvailabilityRuleType::WEEKLY->value, AvailabilityRuleType::DAILY->value])) {
                                    $set('days_of_week', [0, 1, 2, 3, 4, 5, 6]);
                                    $set('enable_date_range', false);
                                    $set('start_date', now()->format('Y-m-d'));
                                    $set('end_date', null);
                                } else {
                                    $set('days_of_week', null);
                                    $set('enable_date_range', false);
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

                        // One slot per row → CalculateAvailabilityJob materialises
                        // one AvailabilitySlot per entry per applicable date.
                        // Hidden for BLOCKED_DATES (whole-day blocking — single time window).
                        Forms\Components\Repeater::make('time_slots')
                            ->label(__('filament.availability_rule.time_slots'))
                            ->schema([
                                Forms\Components\TimePicker::make('start_time')
                                    ->label(__('filament.availability_rule.start_time'))
                                    ->seconds(false)
                                    ->required(),
                                Forms\Components\TimePicker::make('end_time')
                                    ->label(__('filament.availability_rule.end_time'))
                                    ->seconds(false)
                                    ->required()
                                    ->after('start_time'),
                                Forms\Components\TextInput::make('capacity')
                                    ->label(__('filament.availability_rule.capacity'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),
                            ])
                            ->columns(3)
                            ->columnSpanFull()
                            ->defaultItems(1)
                            ->minItems(1)
                            ->maxItems(10)
                            ->addActionLabel(__('filament.availability_rule.add_time_slot'))
                            ->reorderable(false)
                            ->visible(fn (Forms\Get $get): bool => in_array($get('rule_type'), [
                                AvailabilityRuleType::WEEKLY->value,
                                AvailabilityRuleType::DAILY->value,
                                AvailabilityRuleType::SPECIFIC_DATES->value,
                            ]))
                            // Hydrate from legacy start_time/end_time/capacity columns when
                            // time_slots JSON is null (covers rules created before the
                            // multi-slot rollout, until the data backfill migration runs).
                            ->afterStateHydrated(function (Forms\Components\Repeater $component, $state, $record) {
                                if (is_array($state) && count($state) > 0) {
                                    return;
                                }

                                if (! $record) {
                                    return;
                                }
                                // Match the Repeater's TimePicker config (->seconds(false), expects H:i).
                                // Hydrating with H:i:s causes Flatpickr to report "Invalid value".
                                $startTime = $record->start_time?->format('H:i');
                                $endTime = $record->end_time?->format('H:i');

                                if ($startTime && $endTime) {
                                    $component->state([[
                                        'start_time' => $startTime,
                                        'end_time' => $endTime,
                                        'capacity' => (int) ($record->capacity ?? 1),
                                    ]]);
                                }
                            }),

                        Forms\Components\Toggle::make('enable_date_range')
                            ->label('Limit to Date Range')
                            ->helperText('Turn on to set a start and end date. When off, the rule applies indefinitely.')
                            ->default(false)
                            ->dehydrated(false)
                            ->live()
                            ->afterStateHydrated(function (Forms\Components\Toggle $component, $record) {
                                if ($record && $record->end_date !== null) {
                                    $component->state(true);
                                }
                            })
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if (! $state) {
                                    $set('start_date', now()->format('Y-m-d'));
                                    $set('end_date', null);
                                }
                            })
                            ->visible(fn (Forms\Get $get): bool => in_array($get('rule_type'), [
                                AvailabilityRuleType::WEEKLY->value,
                                AvailabilityRuleType::DAILY->value,
                            ])),

                        Forms\Components\DatePicker::make('start_date')
                            ->label(__('filament.availability_rule.start_date'))
                            ->native(false)
                            ->visible(
                                fn (Forms\Get $get): bool => $get('rule_type') === AvailabilityRuleType::BLOCKED_DATES->value
                                || (in_array($get('rule_type'), [
                                    AvailabilityRuleType::WEEKLY->value,
                                    AvailabilityRuleType::DAILY->value,
                                ]) && $get('enable_date_range'))
                            ),

                        Forms\Components\DatePicker::make('end_date')
                            ->label(__('filament.availability_rule.end_date'))
                            ->native(false)
                            ->after('start_date')
                            ->visible(
                                fn (Forms\Get $get): bool => $get('rule_type') === AvailabilityRuleType::BLOCKED_DATES->value
                                || (in_array($get('rule_type'), [
                                    AvailabilityRuleType::WEEKLY->value,
                                    AvailabilityRuleType::DAILY->value,
                                ]) && $get('enable_date_range'))
                            ),

                        Forms\Components\Repeater::make('specific_dates')
                            ->label('Specific Dates')
                            ->schema([
                                Forms\Components\DatePicker::make('date')
                                    ->label('Date')
                                    ->required()
                                    ->native(false),
                            ])
                            ->visible(fn (Forms\Get $get) => $get('rule_type') === AvailabilityRuleType::SPECIFIC_DATES->value)
                            ->addActionLabel('Add Date')
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->columns(1)
                            ->afterStateHydrated(function (Forms\Components\Repeater $component, $state) {
                                if (is_array($state) && ! empty($state)) {
                                    $first = reset($state);

                                    if (is_string($first)) {
                                        $transformed = [];

                                        foreach ($state as $dateStr) {
                                            $transformed[] = ['date' => $dateStr];
                                        }
                                        $component->state($transformed);
                                    }
                                }
                            })
                            ->mutateDehydratedStateUsing(function ($state) {
                                if (! is_array($state)) {
                                    return [];
                                }

                                return collect($state)
                                    ->pluck('date')
                                    ->filter()
                                    ->values()
                                    ->toArray();
                            })
                            ->helperText('Add individual dates when this availability applies.'),
                    ])
                    ->columns(2),

                // Capacity moved into the time_slots Repeater (per-entry).
                // For BLOCKED_DATES rules, capacity is forced to 0 by the job — no UI needed.
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

                Tables\Columns\TextColumn::make('time_slots_summary')
                    ->label(__('filament.availability_rule.time'))
                    ->getStateUsing(function ($record): string {
                        $entries = $record->getEffectiveTimeSlots();

                        if (empty($entries)) {
                            return '-';
                        }

                        return collect($entries)
                            ->map(fn (array $entry) => substr((string) $entry['start_time'], 0, 5) . '–' . substr((string) $entry['end_time'], 0, 5))
                            ->implode(', ');
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('filament.availability_rule.date_range'))
                    ->getStateUsing(function ($record): string {
                        if ($record->rule_type === AvailabilityRuleType::SPECIFIC_DATES && ! empty($record->specific_dates)) {
                            $count = count($record->specific_dates);

                            return $count . ' specific date' . ($count !== 1 ? 's' : '');
                        }

                        if ($record->start_date && $record->end_date) {
                            return $record->start_date->format('M d, Y') . ' - ' . $record->end_date->format('M d, Y');
                        }

                        if ($record->start_date) {
                            return __('filament.availability_rule.from') . ' ' . $record->start_date->format('M d, Y');
                        }

                        return __('filament.availability_rule.ongoing');
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('total_capacity_display')
                    ->label(__('filament.availability_rule.capacity'))
                    ->getStateUsing(function ($record): int {
                        $entries = $record->getEffectiveTimeSlots();

                        if (empty($entries)) {
                            return (int) ($record->capacity ?? 0);
                        }

                        return (int) collect($entries)->sum(fn (array $e) => (int) ($e['capacity'] ?? 0));
                    }),

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
