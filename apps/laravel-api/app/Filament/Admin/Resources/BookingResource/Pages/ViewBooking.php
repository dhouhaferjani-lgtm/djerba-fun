<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\BookingResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Booking Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('booking_number')
                            ->label('Booking Number')
                            ->copyable()
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('status')
                            ->badge(),

                        Infolists\Components\TextEntry::make('user.display_name')
                            ->label('Traveler'),

                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Email')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('listing.title')
                            ->label('Listing')
                            ->formatStateUsing(fn ($record) => $record->listing?->getTranslation('title', app()->getLocale())),

                        Infolists\Components\TextEntry::make('availabilitySlot.start_time')
                            ->label('Date & Time')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Pricing Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('quantity')
                            ->label('Quantity'),

                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Total Amount')
                            ->money(fn ($record) => $record->currency),

                        Infolists\Components\TextEntry::make('currency')
                            ->label('Currency'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Billing Contact')
                    ->schema([
                        Infolists\Components\TextEntry::make('billing_contact.first_name')
                            ->label('First Name'),
                        Infolists\Components\TextEntry::make('billing_contact.last_name')
                            ->label('Last Name'),
                        Infolists\Components\TextEntry::make('billing_contact.email')
                            ->label('Email')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('billing_contact.phone')
                            ->label('Phone')
                            ->placeholder('-'),
                    ])
                    ->columns(4)
                    ->visible(fn ($record) => ! empty($record->billing_contact)),

                Infolists\Components\Section::make('Participants')
                    ->description('All participants with voucher codes and check-in status')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('participants')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('first_name')
                                    ->label('First Name')
                                    ->placeholder('Not entered'),

                                Infolists\Components\TextEntry::make('last_name')
                                    ->label('Last Name')
                                    ->placeholder('Not entered'),

                                Infolists\Components\TextEntry::make('person_type')
                                    ->label('Type')
                                    ->badge()
                                    ->placeholder('-'),

                                Infolists\Components\TextEntry::make('voucher_code')
                                    ->label('Voucher')
                                    ->copyable()
                                    ->fontFamily('mono'),

                                Infolists\Components\TextEntry::make('checked_in')
                                    ->label('Check-in')
                                    ->formatStateUsing(fn ($state) => $state ? 'Checked In' : 'Not Checked In')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                                Infolists\Components\TextEntry::make('checked_in_at')
                                    ->label('Checked In At')
                                    ->dateTime('H:i')
                                    ->placeholder('-'),
                            ])
                            ->columns(6)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->participants()->count() > 0),

                // Fallback for legacy bookings with travelers array
                Infolists\Components\Section::make('Travelers (Legacy)')
                    ->description('All participants for this booking')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('travelers')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('first_name')
                                    ->label('First Name'),

                                Infolists\Components\TextEntry::make('last_name')
                                    ->label('Last Name'),

                                Infolists\Components\TextEntry::make('email')
                                    ->label('Email')
                                    ->copyable()
                                    ->placeholder('-'),

                                Infolists\Components\TextEntry::make('phone')
                                    ->label('Phone')
                                    ->placeholder('-'),

                                Infolists\Components\TextEntry::make('person_type')
                                    ->label('Type')
                                    ->badge()
                                    ->placeholder('-'),

                                Infolists\Components\TextEntry::make('special_requests')
                                    ->label('Special Requests')
                                    ->columnSpanFull()
                                    ->placeholder('None'),
                            ])
                            ->columns(5)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->participants()->count() === 0 && ! empty($record->travelers)),

                // Fallback for legacy bookings without travelers array
                Infolists\Components\Section::make('Traveler Information')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('traveler_info')
                            ->label('Details')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->participants()->count() === 0 && empty($record->travelers) && ! empty($record->traveler_info)),

                Infolists\Components\Section::make('Extras & Add-ons')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('extras')
                            ->label('Items')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => ! empty($record->extras)),

                Infolists\Components\Section::make('Payment History')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('paymentIntents')
                            ->label('Payments')
                            ->schema([
                                Infolists\Components\TextEntry::make('payment_method')
                                    ->badge(),

                                Infolists\Components\TextEntry::make('status')
                                    ->badge(),

                                Infolists\Components\TextEntry::make('amount')
                                    ->money(fn ($record) => $record?->currency ?? 'USD'),

                                Infolists\Components\TextEntry::make('gateway')
                                    ->label('Gateway'),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->dateTime(),
                            ])
                            ->columns(5),
                    ]),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('confirmed_at')
                            ->dateTime()
                            ->placeholder('Not confirmed yet'),

                        Infolists\Components\TextEntry::make('cancelled_at')
                            ->dateTime()
                            ->placeholder('Not cancelled')
                            ->visible(fn ($record) => $record->cancelled_at !== null),

                        Infolists\Components\TextEntry::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record->cancellation_reason !== null),
                    ])
                    ->columns(3),
            ]);
    }
}
