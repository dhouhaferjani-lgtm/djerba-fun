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

                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Traveler'),

                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Email')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('listing.title')
                            ->label('Listing'),

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
                            ->money(fn($record) => $record->currency),

                        Infolists\Components\TextEntry::make('currency')
                            ->label('Currency'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Traveler Information')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('traveler_info')
                            ->label('Details')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Extras & Add-ons')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('extras')
                            ->label('Items')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn($record) => !empty($record->extras)),

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
                                    ->money(fn($record) => $record?->currency ?? 'USD'),

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
                            ->visible(fn($record) => $record->cancelled_at !== null),

                        Infolists\Components\TextEntry::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->columnSpanFull()
                            ->visible(fn($record) => $record->cancellation_reason !== null),
                    ])
                    ->columns(3),
            ]);
    }
}
