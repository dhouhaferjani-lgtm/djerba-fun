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
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('listing_id')
                            ->label('Listing')
                            ->relationship(
                                name: 'listing',
                                modifyQueryUsing: fn (Builder $query) => $query->orderBy('slug'),
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('title', app()->getLocale()))
                            ->searchable(['slug'])
                            ->required()
                            ->preload(),

                        Forms\Components\Select::make('rule_type')
                            ->label('Rule Type')
                            ->options([
                                AvailabilityRuleType::WEEKLY->value => AvailabilityRuleType::WEEKLY->label(),
                                AvailabilityRuleType::DAILY->value => AvailabilityRuleType::DAILY->label(),
                                AvailabilityRuleType::SPECIFIC_DATES->value => AvailabilityRuleType::SPECIFIC_DATES->label(),
                                AvailabilityRuleType::BLOCKED_DATES->value => AvailabilityRuleType::BLOCKED_DATES->label(),
                            ])
                            ->required()
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
                            ->visible(fn (Forms\Get $get): bool => $get('rule_type') === AvailabilityRuleType::WEEKLY->value),

                        Forms\Components\TimePicker::make('start_time')
                            ->label('Start Time')
                            ->seconds(false),

                        Forms\Components\TimePicker::make('end_time')
                            ->label('End Time')
                            ->seconds(false),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->native(false),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->native(false)
                            ->after('start_date'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Capacity & Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('capacity')
                            ->label('Capacity')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),

                        Forms\Components\TextInput::make('price_override')
                            ->label('Price Override')
                            ->numeric()
                            ->prefix('$')
                            ->step('0.01')
                            ->helperText('Leave empty to use listing base price'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('listing.title')
                    ->label('Listing')
                    ->formatStateUsing(fn ($record) => $record->listing?->getTranslation('title', app()->getLocale()))
                    ->limit(30),

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

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Time')
                    ->formatStateUsing(
                        fn ($record) => $record->start_time && $record->end_time
                            ? $record->start_time->format('H:i') . ' - ' . $record->end_time->format('H:i')
                            : '-'
                    ),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Date Range')
                    ->formatStateUsing(
                        fn ($record) => $record->start_date && $record->end_date
                            ? $record->start_date->format('M d, Y') . ' - ' . $record->end_date->format('M d, Y')
                            : ($record->start_date ? 'From ' . $record->start_date->format('M d, Y') : 'Ongoing')
                    )
                    ->wrap(),

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
                    ->options(
                        fn () => \App\Models\Listing::query()
                            ->orderBy('slug')
                            ->get()
                            ->mapWithKeys(fn ($listing) => [$listing->id => $listing->getTranslation('title', app()->getLocale())])
                    )
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
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Availability Rule')
                    ->modalDescription('Are you sure you want to delete this availability rule? This action cannot be undone.'),
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
