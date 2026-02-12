<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ReviewResource\Pages;
use App\Filament\Concerns\SafeTranslation;
use App\Models\Review;
use App\Models\ReviewReply;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewResource extends Resource
{
    use SafeTranslation;

    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = null;

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.reviews');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['listing', 'user', 'reply', 'listing.vendor']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state) . str_repeat('☆', 5 - $state))
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.display_name')
                    ->label('Reviewer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('listing.title')
                    ->label('Listing')
                    ->formatStateUsing(fn ($record) => self::extractTranslation($record->listing?->getTranslation('title', app()->getLocale()), 'Untitled'))
                    ->limit(25),

                Tables\Columns\TextColumn::make('listing.vendor.display_name')
                    ->label('Vendor'),

                Tables\Columns\TextColumn::make('moderation_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('moderation_status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'published' => 'Published',
                        'rejected' => 'Rejected',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'pending' => $query->pending(),
                            'published' => $query->published(),
                            'rejected' => $query->rejected(),
                            default => $query,
                        };
                    }),

                Tables\Filters\SelectFilter::make('rating')
                    ->options([
                        5 => '5 Stars',
                        4 => '4 Stars',
                        3 => '3 Stars',
                        2 => '2 Stars',
                        1 => '1 Star',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Review $record) {
                        try {
                            $record->publish();
                            $record->load('listing');
                            Review::recalculateListingRating($record->listing);

                            Notification::make()
                                ->title('Review Approved')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            report($e);
                            Notification::make()
                                ->title('Failed to approve review')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Review $record): bool => $record->moderation_status !== 'published'),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->requiresConfirmation()
                    ->action(function (Review $record, array $data) {
                        try {
                            $record->reject($data['reason']);
                            $record->load('listing');
                            Review::recalculateListingRating($record->listing);

                            Notification::make()
                                ->title('Review Rejected')
                                ->warning()
                                ->send();
                        } catch (\Throwable $e) {
                            report($e);
                            Notification::make()
                                ->title('Failed to reject review')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Review $record): bool => $record->moderation_status !== 'rejected'),

                Tables\Actions\Action::make('unpublish')
                    ->label('Unpublish')
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (Review $record) {
                        try {
                            $record->unpublish();
                            $record->load('listing');
                            Review::recalculateListingRating($record->listing);

                            Notification::make()
                                ->title('Review Unpublished')
                                ->warning()
                                ->send();
                        } catch (\Throwable $e) {
                            report($e);
                            Notification::make()
                                ->title('Failed to unpublish review')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Review $record): bool => $record->is_published),

                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No reviews')
            ->emptyStateDescription('No reviews have been submitted yet.')
            ->emptyStateIcon('heroicon-o-star');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Review Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('rating')
                            ->label('Rating')
                            ->formatStateUsing(fn (int $state): string => str_repeat('★', $state) . str_repeat('☆', 5 - $state) . " ({$state}/5)")
                            ->color(fn (int $state): string => match (true) {
                                $state >= 4 => 'success',
                                $state >= 3 => 'warning',
                                default => 'danger',
                            }),

                        Infolists\Components\TextEntry::make('moderation_status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'published' => 'success',
                                'pending' => 'warning',
                                'rejected' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                        Infolists\Components\TextEntry::make('user.display_name')
                            ->label('Reviewer'),

                        Infolists\Components\TextEntry::make('listing.title')
                            ->label('Listing')
                            ->formatStateUsing(fn ($record) => self::extractTranslation($record->listing?->getTranslation('title', app()->getLocale()), 'Untitled')),

                        Infolists\Components\TextEntry::make('listing.vendor.display_name')
                            ->label('Vendor'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Review Content')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('Title')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('content')
                            ->label('Content')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('pros')
                            ->label('Highlights')
                            ->listWithLineBreaks()
                            ->visible(fn ($record) => ! empty($record->pros)),

                        Infolists\Components\TextEntry::make('cons')
                            ->label('Could Improve')
                            ->listWithLineBreaks()
                            ->visible(fn ($record) => ! empty($record->cons)),
                    ]),

                Infolists\Components\Section::make('Rejection Info')
                    ->schema([
                        Infolists\Components\TextEntry::make('rejection_reason')
                            ->label('Reason'),
                        Infolists\Components\TextEntry::make('rejected_at')
                            ->label('Rejected At')
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->rejected_at !== null),

                Infolists\Components\Section::make('Vendor Reply')
                    ->schema([
                        Infolists\Components\TextEntry::make('reply.content')
                            ->label('Reply'),
                        Infolists\Components\TextEntry::make('reply.created_at')
                            ->label('Replied At')
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->reply !== null),

                Infolists\Components\Section::make('Stats')
                    ->schema([
                        Infolists\Components\TextEntry::make('helpful_count')
                            ->label('Helpful Votes'),
                        Infolists\Components\TextEntry::make('published_at')
                            ->label('Published At')
                            ->dateTime()
                            ->placeholder('Not published'),
                        Infolists\Components\IconEntry::make('is_verified_booking')
                            ->label('Verified Booking')
                            ->boolean(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'view' => Pages\ViewReview::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = static::getEloquentQuery()->pending()->count();

            return $count > 0 ? (string) $count : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
