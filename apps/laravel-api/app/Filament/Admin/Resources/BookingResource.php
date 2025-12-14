<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\BookingStatus;
use App\Filament\Admin\Resources\BookingResource\Pages;
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
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Bookings';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Booking Information')
                    ->schema([
                        Forms\Components\TextInput::make('booking_number')
                            ->label('Booking Number')
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\Select::make('status')
                            ->options(BookingStatus::class)
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'display_name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('listing_id')
                            ->relationship(
                                name: 'listing',
                                modifyQueryUsing: fn (Builder $query) => $query->orderBy('slug'),
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('title', app()->getLocale()))
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

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(1),

                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->step(0.01),

                        Forms\Components\TextInput::make('currency')
                            ->default('USD')
                            ->maxLength(3),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Traveler Information')
                    ->schema([
                        Forms\Components\KeyValue::make('traveler_info')
                            ->label('Traveler Details')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('extras')
                            ->label('Extras/Add-ons')
                            ->keyLabel('Name')
                            ->valueLabel('Details')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Cancellation')
                    ->schema([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('cancelled_at')
                            ->label('Cancelled At')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('confirmed_at')
                            ->label('Confirmed At')
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
                    ->label('Booking #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('listing.title')
                    ->label('Listing')
                    ->formatStateUsing(fn ($record) => $record->listing?->getTranslation('title', app()->getLocale()))
                    ->limit(30),

                Tables\Columns\TextColumn::make('user.display_name')
                    ->label('Traveler')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Booked On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('confirmed_at')
                    ->label('Confirmed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(BookingStatus::class)
                    ->multiple(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until'),
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
                    ->label('Listing')
                    ->options(
                        fn () => \App\Models\Listing::query()
                            ->orderBy('slug')
                            ->get()
                            ->mapWithKeys(fn ($listing) => [$listing->id => $listing->getTranslation('title', app()->getLocale())])
                    )
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Cancellation Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Booking $record, array $data) {
                        $service = app(BookingService::class);
                        $service->cancel($record, $data['reason']);
                    })
                    ->visible(fn (Booking $record) => $record->canBeCancelled()),

                Tables\Actions\Action::make('mark_no_show')
                    ->label('Mark No-Show')
                    ->icon('heroicon-o-user-minus')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Booking $record) {
                        $service = app(BookingService::class);
                        $service->markAsNoShow($record);
                    })
                    ->visible(fn (Booking $record) => $record->isConfirmed()),

                Tables\Actions\Action::make('mark_completed')
                    ->label('Mark Completed')
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
