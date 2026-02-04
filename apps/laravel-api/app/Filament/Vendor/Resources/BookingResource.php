<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources;

use App\Enums\BookingStatus;
use App\Filament\Concerns\SafeTranslation;
use App\Filament\Vendor\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Models\Listing;
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
        return __('filament.nav.bookings');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.bookings');
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('filament.tooltips.upcoming_confirmed_bookings');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('listing', function (Builder $query) {
                $query->where('vendor_id', auth()->id());
            });
    }

    public static function canCreate(): bool
    {
        return false; // Vendors cannot create bookings directly
    }

    public static function form(Form $form): Form
    {
        // Form is read-only for vendors
        return $form
            ->schema([
                Forms\Components\Section::make('Booking Information')
                    ->schema([
                        Forms\Components\TextInput::make('booking_number')
                            ->label('Booking Number')
                            ->disabled(),

                        Forms\Components\TextInput::make('status')
                            ->formatStateUsing(fn ($state) => $state instanceof BookingStatus ? $state->label() : $state)
                            ->disabled(),

                        Forms\Components\TextInput::make('user.display_name')
                            ->label('Traveler')
                            ->disabled(),

                        Forms\Components\TextInput::make('listing.title')
                            ->label('Listing')
                            ->formatStateUsing(fn ($record) => self::extractTranslation($record->listing?->getTranslation('title', app()->getLocale()), 'Untitled'))
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Booking Details')
                    ->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Number of Guests')
                            ->disabled(),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount')
                            ->formatStateUsing(fn ($state, $record) => number_format((float) $state, 2) . ' ' . ($record->currency ?? 'TND'))
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Booked On')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('confirmed_at')
                            ->label('Confirmed At')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Traveler Information')
                    ->schema([
                        Forms\Components\KeyValue::make('traveler_info')
                            ->label('Traveler Details')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('extras')
                            ->label('Selected Extras')
                            ->disabled()
                            ->columnSpanFull()
                            ->visible(fn ($record) => ! empty($record?->extras)),
                    ]),

                Forms\Components\Section::make('Cancellation')
                    ->schema([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('cancelled_at')
                            ->label('Cancelled At')
                            ->disabled(),
                    ])
                    ->visible(fn ($record) => $record?->isCancelled()),
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
                    ->weight('bold')
                    ->url(fn (Booking $record): string => static::getUrl('view', ['record' => $record]))
                    ->color('primary'),

                Tables\Columns\TextColumn::make('listing.title')
                    ->label('Listing')
                    ->formatStateUsing(fn ($record) => self::extractTranslation($record->listing?->getTranslation('title', app()->getLocale()), 'Untitled'))
                    ->limit(25)
                    ->tooltip(fn ($record) => self::extractTranslation($record->listing?->getTranslation('title', app()->getLocale()), 'Untitled')),

                Tables\Columns\TextColumn::make('user.display_name')
                    ->label('Traveler')
                    ->searchable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Guests')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state, $record) => number_format((float) $state, 2) . ' ' . ($record->currency ?? 'TND'))
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (BookingStatus $state): string => $state->color())
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Booked On')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('availabilitySlot.start_time')
                    ->label('Event Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        BookingStatus::PENDING_PAYMENT->value => BookingStatus::PENDING_PAYMENT->label(),
                        BookingStatus::CONFIRMED->value => BookingStatus::CONFIRMED->label(),
                        BookingStatus::COMPLETED->value => BookingStatus::COMPLETED->label(),
                        BookingStatus::CANCELLED->value => BookingStatus::CANCELLED->label(),
                        BookingStatus::NO_SHOW->value => BookingStatus::NO_SHOW->label(),
                        BookingStatus::REFUND_REQUESTED->value => BookingStatus::REFUND_REQUESTED->label(),
                        BookingStatus::REFUNDED->value => BookingStatus::REFUNDED->label(),
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('listing_id')
                    ->label('Listing')
                    ->options(
                        fn () => Listing::query()
                            ->where('vendor_id', auth()->id())
                            ->orderBy('slug')
                            ->get()
                            ->mapWithKeys(fn ($listing) => [$listing->id => self::extractTranslation($listing->getTranslation('title', app()->getLocale()), 'Untitled')])
                    )
                    ->searchable(),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('upcoming')
                    ->label('Upcoming Only')
                    ->query(
                        fn (Builder $query): Builder => $query
                            ->whereHas('availabilitySlot', fn (Builder $q) => $q->where('start_time', '>=', now()))
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('payment_notes')
                            ->label('Payment Notes')
                            ->rows(3)
                            ->helperText('Optional note about how payment was received (e.g., "Cash payment received on 2024-01-15")')
                            ->placeholder('Enter payment details...'),
                    ])
                    ->modalHeading('Mark Booking as Paid')
                    ->modalDescription(fn (Booking $record) => "Confirm that full payment has been received for booking {$record->booking_number}. Total amount: " . number_format((float) $record->total_amount, 2) . ' ' . ($record->currency ?? 'TND'))
                    ->modalSubmitActionLabel('Confirm Payment Received')
                    ->action(function (Booking $record, array $data) {
                        $service = app(BookingService::class);
                        $service->markAsPaid($record);

                        // Update payment tracking fields
                        $record->update([
                            'payment_notes' => $data['payment_notes'] ?? null,
                            'manual_payment_confirmed_by' => auth()->id(),
                            'manual_payment_confirmed_at' => now(),
                        ]);

                        // Log who marked it as paid
                        activity()
                            ->performedOn($record)
                            ->causedBy(auth()->user())
                            ->event('payment_confirmed')
                            ->withProperties([
                                'amount' => $record->total_amount,
                                'currency' => $record->currency,
                                'notes' => $data['payment_notes'] ?? null,
                            ])
                            ->log('Vendor manually confirmed full payment received');
                    })
                    ->successNotificationTitle('Payment confirmed')
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Payment Confirmed')
                            ->body('Booking has been marked as paid and confirmed.')
                    )
                    ->visible(
                        fn (Booking $record) => $record->status === BookingStatus::PENDING_PAYMENT &&
                        in_array($record->paymentIntents()->latest()->first()?->payment_method?->value ?? 'offline', ['offline', 'bank_transfer', 'cash'])
                    ),

                Tables\Actions\Action::make('mark_partially_paid')
                    ->label('Partial Payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Amount Paid')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->suffix(fn (Booking $record) => $record->currency ?? 'TND')
                            ->helperText(fn (Booking $record) => 'Total booking amount: ' . number_format((float) $record->total_amount, 2) . ' ' . ($record->currency ?? 'TND')),

                        Forms\Components\Textarea::make('payment_note')
                            ->label('Payment Note')
                            ->rows(3)
                            ->helperText('Optional note about this partial payment')
                            ->placeholder('e.g., "First installment received via bank transfer"'),
                    ])
                    ->modalHeading('Record Partial Payment')
                    ->modalDescription('Record a partial payment for this booking. The booking will remain in pending payment status until fully paid.')
                    ->modalSubmitActionLabel('Record Payment')
                    ->action(function (Booking $record, array $data) {
                        // Create a payment intent record for the partial payment
                        $paymentIntent = $record->paymentIntents()->create([
                            'amount' => $data['amount_paid'],
                            'currency' => $record->currency ?? 'TND',
                            'payment_method' => \App\Enums\PaymentMethod::OFFLINE,
                            'status' => \App\Enums\PaymentStatus::SUCCEEDED,
                            'gateway' => 'offline',
                            'metadata' => [
                                'type' => 'partial_payment',
                                'note' => $data['payment_note'] ?? null,
                                'recorded_by' => auth()->id(),
                                'recorded_at' => now()->toDateTimeString(),
                            ],
                            'paid_at' => now(),
                        ]);

                        // Update payment notes on booking
                        $existingNotes = $record->payment_notes ?? '';
                        $newNote = now()->format('Y-m-d H:i') . ': Partial payment of ' . number_format((float) $data['amount_paid'], 2) . ' ' . $record->currency . ' recorded by ' . auth()->user()->name;

                        if (! empty($data['payment_note'])) {
                            $newNote .= ' - ' . $data['payment_note'];
                        }

                        $record->update([
                            'payment_notes' => $existingNotes ? $existingNotes . "\n" . $newNote : $newNote,
                        ]);

                        // Log the partial payment
                        activity()
                            ->performedOn($record)
                            ->causedBy(auth()->user())
                            ->event('partial_payment_recorded')
                            ->withProperties([
                                'amount' => $data['amount_paid'],
                                'currency' => $record->currency,
                                'note' => $data['payment_note'] ?? null,
                            ])
                            ->log("Partial payment recorded: {$data['amount_paid']} {$record->currency}");
                    })
                    ->successNotificationTitle('Partial payment recorded')
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Partial Payment Recorded')
                            ->body('The partial payment has been logged. Booking remains pending until full payment.')
                    )
                    ->visible(
                        fn (Booking $record) => $record->status === BookingStatus::PENDING_PAYMENT &&
                        in_array($record->paymentIntents()->latest()->first()?->payment_method?->value ?? 'offline', ['offline', 'bank_transfer', 'cash'])
                    ),

                Tables\Actions\Action::make('mark_completed')
                    ->label('Mark Completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Booking as Completed')
                    ->modalDescription('This will mark the booking as completed. This action cannot be undone.')
                    ->action(function (Booking $record) {
                        $service = app(BookingService::class);
                        $service->complete($record);
                    })
                    ->visible(fn (Booking $record) => $record->status === BookingStatus::CONFIRMED),

                Tables\Actions\Action::make('mark_no_show')
                    ->label('No-Show')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as No-Show')
                    ->modalDescription('Mark this booking as a no-show. The traveler did not attend.')
                    ->action(function (Booking $record) {
                        $service = app(BookingService::class);
                        $service->markAsNoShow($record);
                    })
                    ->visible(fn (Booking $record) => $record->status === BookingStatus::CONFIRMED),

                Tables\Actions\Action::make('contact_traveler')
                    ->label('Contact')
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->url(fn (Booking $record) => 'mailto:' . ($record->user?->email ?? ''))
                    ->openUrlInNewTab()
                    ->visible(fn (Booking $record) => $record->user?->email),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_multiple_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->form([
                            Forms\Components\Textarea::make('payment_notes')
                                ->label('Payment Notes')
                                ->rows(3)
                                ->helperText('Optional note to add to all selected bookings')
                                ->placeholder('e.g., "Cash payments received on 2024-01-15"'),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Mark Selected Bookings as Paid')
                        ->modalDescription('Confirm that payment has been received for all selected bookings. Only bookings with offline/bank transfer payment methods will be affected.')
                        ->modalSubmitActionLabel('Confirm Payments Received')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $service = app(BookingService::class);
                            $successCount = 0;

                            foreach ($records as $record) {
                                // Only process bookings that are pending payment and use offline methods
                                if (
                                    $record->status === BookingStatus::PENDING_PAYMENT &&
                                    in_array($record->paymentIntents()->latest()->first()?->payment_method?->value ?? 'offline', ['offline', 'bank_transfer', 'cash'])
                                ) {
                                    $service->markAsPaid($record);

                                    $record->update([
                                        'payment_notes' => $data['payment_notes'] ?? null,
                                        'manual_payment_confirmed_by' => auth()->id(),
                                        'manual_payment_confirmed_at' => now(),
                                    ]);

                                    activity()
                                        ->performedOn($record)
                                        ->causedBy(auth()->user())
                                        ->event('payment_confirmed_bulk')
                                        ->withProperties([
                                            'amount' => $record->total_amount,
                                            'currency' => $record->currency,
                                            'notes' => $data['payment_notes'] ?? null,
                                        ])
                                        ->log('Vendor bulk-confirmed payment received');

                                    $successCount++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Bulk Payment Confirmation')
                                ->body("{$successCount} booking(s) marked as paid.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No bookings yet')
            ->emptyStateDescription('When travelers book your listings, they will appear here.')
            ->emptyStateIcon('heroicon-o-calendar');
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
            'view' => Pages\ViewBooking::route('/{record}'),
            'manage-event' => Pages\ManageEventBookings::route('/event/{slot}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->where('status', BookingStatus::CONFIRMED)
            ->whereHas('availabilitySlot', fn (Builder $q) => $q->where('start_time', '>=', now()))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
