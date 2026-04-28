<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources;

use App\Enums\AvailabilityRuleType;
use App\Filament\Vendor\Resources\AvailabilityRuleResource\Pages;
use App\Models\AvailabilityRule;
use App\Models\Listing;
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
        return __('filament.nav.my_listings');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.availability_rules');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('listing_id')
                            ->label('Listing')
                            ->options(function () {
                                return Listing::where('vendor_id', auth()->id())
                                    ->orderBy('slug')
                                    ->get()
                                    ->mapWithKeys(function ($listing) {
                                        $title = $listing->getTranslation('title', app()->getLocale());

                                        return [
                                            $listing->id => is_string($title) && ! empty($title) ? $title : $listing->slug,
                                        ];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->preload()
                            ->default(fn () => request()->query('listing_id')),

                        Forms\Components\Select::make('rule_type')
                            ->label('Rule Type')
                            ->options([
                                AvailabilityRuleType::WEEKLY->value => AvailabilityRuleType::WEEKLY->label(),
                                AvailabilityRuleType::DAILY->value => AvailabilityRuleType::DAILY->label(),
                                AvailabilityRuleType::SPECIFIC_DATES->value => AvailabilityRuleType::SPECIFIC_DATES->label(),
                                AvailabilityRuleType::BLOCKED_DATES->value => AvailabilityRuleType::BLOCKED_DATES->label(),
                            ])
                            ->required()
                            ->default(AvailabilityRuleType::WEEKLY->value)
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
                            ->label('Active')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\CheckboxList::make('days_of_week')
                            ->label('Days of Week')
                            ->options([
                                0 => 'Sunday',
                                1 => 'Monday',
                                2 => 'Tuesday',
                                3 => 'Wednesday',
                                4 => 'Thursday',
                                5 => 'Friday',
                                6 => 'Saturday',
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
                            ->helperText('Select the days when this tour/event is available. At least one day must be selected.'),

                        // Multiple time slots per day. CalculateAvailabilityJob materialises
                        // one AvailabilitySlot per entry per applicable date.
                        // Hidden for BLOCKED_DATES (whole-day blocking — single time window).
                        Forms\Components\Repeater::make('time_slots')
                            ->label('Time Slots')
                            ->helperText('Add one row per time slot you offer on each applicable day. Each slot has its own capacity.')
                            ->schema([
                                // Masked TextInput, not TimePicker — see the Admin resource twin for
                                // the long-form rationale (Safari's <input type="time"> kept
                                // producing "Invalid value" tooltips across every variant).
                                Forms\Components\TextInput::make('start_time')
                                    ->label('Start Time')
                                    ->mask('99:99')
                                    ->placeholder('HH:MM')
                                    ->required()
                                    ->rule('regex:/^([01]\d|2[0-3]):[0-5]\d$/')
                                    ->afterStateHydrated(function (Forms\Components\TextInput $component, $state): void {
                                        if (is_string($state) && strlen($state) > 5) {
                                            $component->state(substr($state, 0, 5));
                                        }
                                    }),
                                Forms\Components\TextInput::make('end_time')
                                    ->label('End Time')
                                    ->mask('99:99')
                                    ->placeholder('HH:MM')
                                    ->required()
                                    ->rule('regex:/^([01]\d|2[0-3]):[0-5]\d$/')
                                    ->after(fn (Forms\Get $get) => $get('start_time'))
                                    ->afterStateHydrated(function (Forms\Components\TextInput $component, $state): void {
                                        if (is_string($state) && strlen($state) > 5) {
                                            $component->state(substr($state, 0, 5));
                                        }
                                    }),
                                Forms\Components\TextInput::make('capacity')
                                    ->label('Capacity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(10)
                                    ->required(),
                            ])
                            ->columns(3)
                            ->columnSpanFull()
                            ->defaultItems(1)
                            ->minItems(1)
                            ->maxItems(10)
                            ->addActionLabel('Add another time slot')
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
                            ->label('Start Date')
                            ->native(false)
                            ->default(now())
                            ->visible(
                                fn (Forms\Get $get): bool => $get('rule_type') === AvailabilityRuleType::BLOCKED_DATES->value
                                || (in_array($get('rule_type'), [
                                    AvailabilityRuleType::WEEKLY->value,
                                    AvailabilityRuleType::DAILY->value,
                                ]) && $get('enable_date_range'))
                            ),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
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
                            ->helperText('Add individual dates when this tour/event is available.'),
                    ])
                    ->columns(2),

                // Capacity is per-time-slot (lives in the time_slots Repeater above).
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('listing_title_display')
                    ->label('Listing')
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
                    ->label('Type')
                    ->formatStateUsing(fn (AvailabilityRuleType $state): string => $state->label())
                    ->badge()
                    ->color(fn (AvailabilityRuleType $state): string => match ($state) {
                        AvailabilityRuleType::WEEKLY => 'info',
                        AvailabilityRuleType::DAILY => 'success',
                        AvailabilityRuleType::SPECIFIC_DATES => 'warning',
                        AvailabilityRuleType::BLOCKED_DATES => 'danger',
                    }),

                Tables\Columns\TextColumn::make('days_of_week_display')
                    ->label('Days')
                    ->getStateUsing(function ($record): string {
                        $daysOfWeek = $record->days_of_week;

                        if (empty($daysOfWeek)) {
                            return '-';
                        }
                        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                        $days = is_array($daysOfWeek) ? $daysOfWeek : [];

                        return collect($days)->map(fn ($d) => $dayNames[$d] ?? '?')->join(', ');
                    }),

                Tables\Columns\TextColumn::make('time_slots_summary')
                    ->label('Time Slots')
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

                Tables\Columns\TextColumn::make('total_capacity_display')
                    ->label('Capacity')
                    ->getStateUsing(function ($record): int {
                        $entries = $record->getEffectiveTimeSlots();

                        if (empty($entries)) {
                            return (int) ($record->capacity ?? 0);
                        }

                        return (int) collect($entries)->sum(fn (array $e) => (int) ($e['capacity'] ?? 0));
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
            ->filters([
                Tables\Filters\SelectFilter::make('listing_id')
                    ->label('Listing')
                    ->options(function () {
                        return Listing::where('vendor_id', auth()->id())
                            ->orderBy('slug')
                            ->get()
                            ->mapWithKeys(fn ($listing) => [
                                $listing->id => $listing->getTranslation('title', app()->getLocale()) ?: $listing->slug,
                            ]);
                    })
                    ->searchable(),

                Tables\Filters\SelectFilter::make('rule_type')
                    ->label('Rule Type')
                    ->options([
                        AvailabilityRuleType::WEEKLY->value => AvailabilityRuleType::WEEKLY->label(),
                        AvailabilityRuleType::DAILY->value => AvailabilityRuleType::DAILY->label(),
                        AvailabilityRuleType::SPECIFIC_DATES->value => AvailabilityRuleType::SPECIFIC_DATES->label(),
                        AvailabilityRuleType::BLOCKED_DATES->value => AvailabilityRuleType::BLOCKED_DATES->label(),
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All rules')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        // Only show rules for listings owned by the current vendor
        return parent::getEloquentQuery()
            ->whereHas('listing', function (Builder $query) {
                $query->where('vendor_id', auth()->id());
            });
    }

    public static function getRelations(): array
    {
        return [];
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
