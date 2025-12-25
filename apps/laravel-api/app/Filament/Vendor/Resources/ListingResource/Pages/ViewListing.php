<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\ListingResource\Pages;

use App\Filament\Vendor\Resources\ListingResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewListing extends ViewRecord
{
    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->status->canEdit()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Basic Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('Title')
                            ->formatStateUsing(fn ($record) => $record->getTranslation('title', app()->getLocale())),

                        Infolists\Components\TextEntry::make('service_type')
                            ->label('Type')
                            ->badge(),

                        Infolists\Components\TextEntry::make('status')
                            ->badge(),

                        Infolists\Components\TextEntry::make('slug')
                            ->label('URL Slug'),

                        Infolists\Components\TextEntry::make('location.name')
                            ->label('Location')
                            ->formatStateUsing(fn ($record) => $record->location?->getTranslation('name', app()->getLocale())),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Description')
                    ->schema([
                        Infolists\Components\TextEntry::make('summary')
                            ->label('Summary')
                            ->formatStateUsing(fn ($record) => $record->getTranslation('summary', app()->getLocale()))
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->html()
                            ->formatStateUsing(fn ($record) => $record->getTranslation('description', app()->getLocale()))
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Pricing & Capacity')
                    ->schema([
                        Infolists\Components\TextEntry::make('pricing.base')
                            ->label('Base Price')
                            ->formatStateUsing(
                                fn ($state, $record) => number_format($state / 100, 2) . ' ' . ($record->pricing['currency'] ?? 'TND')
                            ),

                        Infolists\Components\TextEntry::make('min_group_size')
                            ->label('Min Group Size'),

                        Infolists\Components\TextEntry::make('max_group_size')
                            ->label('Max Group Size'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('bookings_count')
                            ->label('Total Bookings'),

                        Infolists\Components\TextEntry::make('reviews_count')
                            ->label('Reviews'),

                        Infolists\Components\TextEntry::make('rating')
                            ->label('Rating')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . '/5' : 'No ratings yet'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('published_at')
                            ->label('Published')
                            ->dateTime()
                            ->placeholder('Not published yet'),
                    ])
                    ->columns(3),
            ]);
    }
}
