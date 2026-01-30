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
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('days_of_week', null)),

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
                            ->helperText('Select the days when this tour/event is available'),

                        Forms\Components\TimePicker::make('start_time')
                            ->label('Start Time')
                            ->seconds(false)
                            ->required(),

                        Forms\Components\TimePicker::make('end_time')
                            ->label('End Time')
                            ->seconds(false)
                            ->required(),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->native(false)
                            ->default(now())
                            ->helperText('When does this availability start?'),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->native(false)
                            ->after('start_date')
                            ->helperText('When does this availability end? Leave empty for ongoing.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Capacity')
                    ->schema([
                        Forms\Components\TextInput::make('capacity')
                            ->label('Maximum Participants')
                            ->numeric()
                            ->minValue(1)
                            ->default(10)
                            ->required()
                            ->helperText('Maximum number of people that can book this time slot'),

                        Forms\Components\TextInput::make('price_override')
                            ->label('Price Override (cents)')
                            ->numeric()
                            ->helperText('Leave empty to use listing base price. Enter price in cents.'),
                    ])
                    ->columns(2),
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

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Time')
                    ->formatStateUsing(
                        fn ($record) => $record->start_time && $record->end_time
                            ? $record->start_time->format('H:i') . ' - ' . $record->end_time->format('H:i')
                            : '-'
                    ),

                Tables\Columns\TextColumn::make('capacity')
                    ->label('Capacity')
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
