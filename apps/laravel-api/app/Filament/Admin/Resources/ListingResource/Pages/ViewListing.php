<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ListingResource\Pages;

use App\Enums\DifficultyLevel;
use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Filament\Admin\Resources\ListingResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewListing extends ViewRecord
{
    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('approve')
                ->label('Approve & Publish')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve Listing')
                ->modalDescription('This will publish the listing and make it visible to travelers.')
                ->action(function () {
                    $this->record->update([
                        'status' => ListingStatus::PUBLISHED,
                        'published_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Listing Approved')
                        ->body('The listing has been published.')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status === ListingStatus::PENDING_REVIEW),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => ListingStatus::REJECTED,
                    ]);

                    Notification::make()
                        ->title('Listing Rejected')
                        ->warning()
                        ->send();
                })
                ->visible(fn () => $this->record->status === ListingStatus::PENDING_REVIEW),

            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Listing Overview')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('Title')
                            ->formatStateUsing(fn ($record) => $record->getTranslation('title', app()->getLocale())),

                        Infolists\Components\TextEntry::make('service_type')
                            ->label('Type')
                            ->badge()
                            ->color(fn (ServiceType $state): string => match ($state) {
                                ServiceType::TOUR => 'info',
                                ServiceType::EVENT => 'success',
                            }),

                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (ListingStatus $state): string => $state->color()),

                        Infolists\Components\TextEntry::make('slug')
                            ->copyable(),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Vendor Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('vendor.display_name')
                            ->label('Vendor Name'),

                        Infolists\Components\TextEntry::make('vendor.email')
                            ->label('Email')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('location.name')
                            ->label('Location')
                            ->formatStateUsing(fn ($record) => $record->location?->getTranslation('name', app()->getLocale())),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Content')
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
                                fn ($state, $record) => number_format((float) $state / 100, 2) . ' ' . ($record->pricing['currency'] ?? 'EUR')
                            ),

                        Infolists\Components\TextEntry::make('min_group_size')
                            ->label('Min Group'),

                        Infolists\Components\TextEntry::make('max_group_size')
                            ->label('Max Group'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Tour Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('duration.value')
                            ->label('Duration')
                            ->formatStateUsing(
                                fn ($state, $record) => ($state ?? '-') . ' ' . ($record->duration['unit'] ?? 'hours')
                            ),

                        Infolists\Components\TextEntry::make('difficulty')
                            ->label('Difficulty')
                            ->formatStateUsing(fn (?DifficultyLevel $state): string => $state?->label() ?? 'Not set')
                            ->badge(),

                        Infolists\Components\TextEntry::make('distance.value')
                            ->label('Distance')
                            ->formatStateUsing(
                                fn ($state, $record) => $state ? ($state . ' ' . ($record->distance['unit'] ?? 'km')) : 'Not set'
                            ),
                    ])
                    ->columns(3)
                    ->visible(fn ($record): bool => $record->service_type === ServiceType::TOUR),

                Infolists\Components\Section::make('Event Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('event_type')
                            ->label('Event Type'),

                        Infolists\Components\TextEntry::make('start_date')
                            ->label('Start Date')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('end_date')
                            ->label('End Date')
                            ->dateTime(),
                    ])
                    ->columns(3)
                    ->visible(fn ($record): bool => $record->service_type === ServiceType::EVENT),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('bookings_count')
                            ->label('Total Bookings'),

                        Infolists\Components\TextEntry::make('reviews_count')
                            ->label('Reviews'),

                        Infolists\Components\TextEntry::make('rating')
                            ->label('Rating')
                            ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 1) . '/5' : 'No ratings'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('published_at')
                            ->label('Published')
                            ->dateTime()
                            ->placeholder('Not published'),
                    ])
                    ->columns(5),
            ]);
    }
}
