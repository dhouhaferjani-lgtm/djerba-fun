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

    /**
     * Eager load relationships to prevent N+1 queries during rendering.
     * This fixes 500 errors caused by relationship queries in visibility callbacks.
     */
    protected function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
    {
        $record = static::getResource()::resolveRecordRouteBinding($key);

        if ($record === null) {
            abort(404);
        }

        return $record->loadMissing([
            'bookingExtras',
            'participants',
            'user',
            'listing',
            'availabilitySlot',
        ]);
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

                Infolists\Components\Section::make('Billing Contact')
                    ->description('Person who made the payment')
                    ->schema([
                        Infolists\Components\TextEntry::make('billing_contact.first_name')
                            ->label('First Name')
                            ->placeholder('Not provided'),

                        Infolists\Components\TextEntry::make('billing_contact.last_name')
                            ->label('Last Name')
                            ->placeholder('Not provided'),

                        Infolists\Components\TextEntry::make('billing_contact.email')
                            ->label('Email')
                            ->copyable()
                            ->icon('heroicon-o-envelope')
                            ->placeholder('Not provided'),

                        Infolists\Components\TextEntry::make('billing_contact.phone')
                            ->label('Phone')
                            ->copyable()
                            ->icon('heroicon-o-phone')
                            ->placeholder('Not provided'),
                    ])
                    ->columns(4)
                    ->visible(fn ($record) => ! empty($record->billing_contact)),

                Infolists\Components\Section::make('Pricing Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('quantity')
                            ->label('Quantity'),

                        Infolists\Components\TextEntry::make('person_type_breakdown')
                            ->label('Breakdown')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) {
                                    return '-';
                                }
                                $parts = [];
                                foreach ($state as $type => $count) {
                                    $parts[] = "{$count} {$type}";
                                }

                                return implode(', ', $parts);
                            })
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Total Amount')
                            ->money(fn ($record) => $record->currency),

                        Infolists\Components\TextEntry::make('discount_amount')
                            ->label('Discount')
                            ->money(fn ($record) => $record->currency)
                            ->visible(fn ($record) => $record->discount_amount > 0),

                        Infolists\Components\TextEntry::make('currency')
                            ->label('Currency'),
                    ])
                    ->columns(5),

                Infolists\Components\Section::make('Selected Extras')
                    ->description('Add-ons purchased with this booking')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('bookingExtras')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('extra_name')
                                    ->label('Extra')
                                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['en'] ?? $state['fr'] ?? 'Extra') : ($state ?? 'Extra')),

                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Qty'),

                                Infolists\Components\TextEntry::make('unit_price_tnd')
                                    ->label('Unit Price')
                                    ->formatStateUsing(fn ($state) => number_format((float) ($state ?? 0), 2).' TND'),

                                Infolists\Components\TextEntry::make('subtotal_tnd')
                                    ->label('Total')
                                    ->formatStateUsing(fn ($state) => number_format((float) ($state ?? 0), 2).' TND'),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->bookingExtras->isNotEmpty()),

                Infolists\Components\Section::make('Participants')
                    ->description('All participants with contact info and check-in status')
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

                                Infolists\Components\TextEntry::make('email')
                                    ->label('Email')
                                    ->copyable()
                                    ->icon('heroicon-o-envelope')
                                    ->placeholder('-'),

                                Infolists\Components\TextEntry::make('phone')
                                    ->label('Phone')
                                    ->copyable()
                                    ->icon('heroicon-o-phone')
                                    ->placeholder('-'),

                                Infolists\Components\TextEntry::make('person_type')
                                    ->label('Type')
                                    ->badge()
                                    ->placeholder('-'),

                                Infolists\Components\TextEntry::make('voucher_code')
                                    ->label('Voucher')
                                    ->copyable()
                                    ->fontFamily('mono'),

                                Infolists\Components\TextEntry::make('special_requests')
                                    ->label('Special Requests')
                                    ->placeholder('-')
                                    ->columnSpanFull()
                                    ->visible(fn ($state) => ! empty($state)),

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
                            ->columns(4)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->participants->isNotEmpty()),

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
