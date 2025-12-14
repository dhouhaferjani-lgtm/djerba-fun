<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FraudAlertWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Suspicious Activities')
            ->description('Recent bookings and activities flagged for review')
            ->query(
                Booking::query()
                    ->where('status', BookingStatus::CANCELLED)
                    ->whereNotNull('cancelled_at')
                    ->latest('cancelled_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('booking_number')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.display_name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('listing.title')
                    ->label('Listing')
                    ->formatStateUsing(fn ($record) => $record->listing?->getTranslation('title', app()->getLocale()))
                    ->limit(30),

                Tables\Columns\TextColumn::make('total_amount')
                    ->money('CAD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (BookingStatus $state) => $state->label())
                    ->color(fn (BookingStatus $state) => $state->color()),

                Tables\Columns\TextColumn::make('cancellation_reason')
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cancelled_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (Booking $record): string => route('filament.admin.resources.bookings.view', ['record' => $record]))
                    ->icon('heroicon-o-eye'),
            ]);
    }
}
