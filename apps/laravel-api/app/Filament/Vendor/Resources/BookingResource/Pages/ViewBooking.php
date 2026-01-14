<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\BookingResource\Pages;

use App\Filament\Concerns\SafeTranslation;
use App\Filament\Vendor\Resources\BookingResource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    use SafeTranslation;

    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
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
                            ->formatStateUsing(fn ($record) => $this->safeTranslation($record->listing?->getTranslation('title', app()->getLocale()), 'Untitled')),

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
