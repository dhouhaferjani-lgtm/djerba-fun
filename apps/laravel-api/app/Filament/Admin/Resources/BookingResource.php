<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\BookingStatus;
use App\Filament\Admin\Resources\BookingResource\Pages;
use App\Filament\Concerns\SafeTranslation;
use App\Models\Booking;
use App\Services\BookingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingResource extends Resource
{
    use SafeTranslation;

    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.operations');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.bookings');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.sections.booking_information'))
                    ->schema([
                        Forms\Components\TextInput::make('booking_number')
                            ->label(__('filament.labels.booking_number'))
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\Select::make('status')
                            ->label(__('filament.labels.status'))
                            ->options(BookingStatus::class)
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'display_name')
                            ->label(__('filament.labels.traveler'))
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('listing_id')
                            ->relationship(
                                name: 'listing',
                                modifyQueryUsing: fn (Builder $query) => $query->orderBy('slug'),
                            )
                            ->label(__('filament.resources.listings'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => self::extractTranslation($record->getTranslation('title', app()->getLocale()), 'Untitled'))
                            ->required()
                            ->searchable(['slug'])
                            ->preload(),

                        Forms\Components\Select::make('availability_slot_id')
                            ->relationship('availabilitySlot', 'start_time')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('filament.sections.pricing'))
                    ->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->label(__('filament.labels.quantity'))
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(1),

                        Forms\Components\TextInput::make('total_amount')
                            ->label(__('filament.labels.total_amount'))
                            ->numeric()
                            ->required()
                            ->prefix(fn ($record) => match ($record?->currency ?? 'TND') {
                                'TND' => 'د.ت',
                                'EUR' => '€',
                                'USD' => '$',
                                default => '',
                            })
                            ->step(0.01),

                        Forms\Components\TextInput::make('currency')
                            ->label(__('filament.labels.currency'))
                            ->default('TND')
                            ->maxLength(3),
                    ])
                    ->columns(3),

                Forms\Components\Section::make(__('filament.sections.traveler_information'))
                    ->description(__('filament.helpers.sensitive_info_warning'))
                    ->schema([
                        Forms\Components\KeyValue::make('traveler_info')
                            ->label(__('filament.labels.traveler_details'))
                            ->keyLabel(__('filament.labels.field'))
                            ->valueLabel(__('filament.labels.value'))
                            ->columnSpanFull()
                            ->disabled()
                            ->helperText(__('filament.helpers.traveler_info_warning')),

                        Forms\Components\KeyValue::make('extras')
                            ->label(__('filament.labels.extras_addons'))
                            ->keyLabel(__('filament.labels.name'))
                            ->valueLabel(__('filament.labels.value'))
                            ->columnSpanFull()
                            ->disabled(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make(__('filament.sections.cancellation'))
                    ->schema([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label(__('filament.labels.cancellation_reason'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('cancelled_at')
                            ->label(__('filament.labels.cancelled_at'))
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('confirmed_at')
                            ->label(__('filament.labels.confirmed_at'))
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record?->isCancelled() || $record?->isConfirmed()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking_number')
                    ->label(__('filament.labels.booking_hash'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('listing.title')
                    ->label(__('filament.resources.listings'))
                    ->formatStateUsing(fn ($record) => self::extractTranslation($record->listing?->getTranslation('title', app()->getLocale()), 'Untitled'))
                    ->limit(30),

                Tables\Columns\TextColumn::make('user.display_name')
                    ->label(__('filament.labels.traveler'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('filament.labels.amount'))
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('filament.labels.qty'))
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament.labels.status'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.labels.booked_on'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('confirmed_at')
                    ->label(__('filament.labels.confirmed'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('traveler_details_status')
                    ->label(__('filament.labels.participant_names'))
                    ->colors([
                        'secondary' => 'not_required',
                        'warning' => fn ($state) => in_array($state, ['pending', 'partial']),
                        'success' => 'complete',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'not_required' => __('filament.options.not_required'),
                        'pending' => __('filament.options.pending'),
                        'partial' => __('filament.options.partial'),
                        'complete' => __('filament.options.complete'),
                        default => $state,
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('linked_at')
                    ->label(__('filament.labels.linked_to_account'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('linked_method')
                    ->label(__('filament.labels.link_method'))
                    ->colors([
                        'secondary' => 'auto',
                        'primary' => 'manual',
                        'success' => 'claimed',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'auto' => __('filament.options.auto'),
                        'manual' => __('filament.options.manual'),
                        'claimed' => __('filament.options.claimed'),
                        default => __('filament.options.na'),
                    })
                    ->visible(fn ($record) => $record->linked_at !== null)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament.labels.status'))
                    ->options(BookingStatus::class)
                    ->multiple(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label(__('filament.labels.from')),
                        Forms\Components\DatePicker::make('created_until')
                            ->label(__('filament.labels.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('listing_id')
                    ->label(__('filament.resources.listings'))
                    ->options(
                        fn () => \App\Models\Listing::query()
                            ->orderBy('slug')
                            ->get()
                            ->mapWithKeys(fn ($listing) => [$listing->id => self::extractTranslation($listing->getTranslation('title', app()->getLocale()), 'Untitled')])
                    )
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('cancel')
                    ->label(__('filament.actions.cancel'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label(__('filament.labels.cancellation_reason'))
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Booking $record, array $data) {
                        $service = app(BookingService::class);
                        $service->cancel($record, $data['reason']);
                    })
                    ->visible(fn (Booking $record) => $record->canBeCancelled()),

                Tables\Actions\Action::make('mark_no_show')
                    ->label(__('filament.actions.mark_no_show'))
                    ->icon('heroicon-o-user-minus')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Booking $record) {
                        $service = app(BookingService::class);
                        $service->markAsNoShow($record);
                    })
                    ->visible(fn (Booking $record) => $record->isConfirmed()),

                Tables\Actions\Action::make('mark_completed')
                    ->label(__('filament.actions.mark_completed'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Booking $record) {
                        $service = app(BookingService::class);
                        $service->complete($record);
                    })
                    ->visible(fn (Booking $record) => $record->isConfirmed()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'view' => Pages\ViewBooking::route('/{record}'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', BookingStatus::PENDING_PAYMENT)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
