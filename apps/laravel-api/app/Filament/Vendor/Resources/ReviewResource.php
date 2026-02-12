<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources;

use App\Filament\Concerns\SafeTranslation;
use App\Filament\Vendor\Resources\ReviewResource\Pages;
use App\Models\Listing;
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

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.bookings');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.reviews');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('listing', function (Builder $query) {
                $query->where('vendor_id', auth()->id());
            })
            ->with(['listing', 'user', 'reply']);
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
                    ->limit(25)
                    ->tooltip(fn ($record) => self::extractTranslation($record->listing?->getTranslation('title', app()->getLocale()), 'Untitled')),

                Tables\Columns\TextColumn::make('moderation_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\IconColumn::make('reply_exists')
                    ->label('Replied')
                    ->state(fn (Review $record): bool => $record->reply !== null)
                    ->boolean()
                    ->trueIcon('heroicon-o-chat-bubble-left-right')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray'),

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

                Tables\Filters\SelectFilter::make('listing_id')
                    ->label('Listing')
                    ->options(
                        fn () => Listing::query()
                            ->where('vendor_id', auth()->id())
                            ->get()
                            ->mapWithKeys(fn ($listing) => [$listing->id => self::extractTranslation($listing->getTranslation('title', app()->getLocale()), 'Untitled')])
                    )
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Review')
                    ->modalDescription('This review will be published and visible to all travelers.')
                    ->action(function (Review $record) {
                        $record->publish();
                        Review::recalculateListingRating($record->listing);

                        Notification::make()
                            ->title('Review Approved')
                            ->body('The review is now published.')
                            ->success()
                            ->send();
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
                            ->rows(3)
                            ->placeholder('Explain why this review is being rejected...'),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Reject Review')
                    ->action(function (Review $record, array $data) {
                        $record->reject($data['reason']);
                        Review::recalculateListingRating($record->listing);

                        Notification::make()
                            ->title('Review Rejected')
                            ->body('The review has been rejected.')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (Review $record): bool => $record->moderation_status !== 'rejected'),

                Tables\Actions\Action::make('reply')
                    ->label('Reply')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->form([
                        Forms\Components\Textarea::make('content')
                            ->label('Your Reply')
                            ->required()
                            ->rows(4)
                            ->placeholder('Write your response to this review...'),
                    ])
                    ->action(function (Review $record, array $data) {
                        ReviewReply::create([
                            'review_id' => $record->id,
                            'vendor_id' => auth()->id(),
                            'content' => $data['content'],
                        ]);

                        Notification::make()
                            ->title('Reply Posted')
                            ->body('Your reply has been added to the review.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Review $record): bool => $record->reply === null),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No reviews yet')
            ->emptyStateDescription('When travelers review your listings, they will appear here.')
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
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                        Infolists\Components\TextEntry::make('user.display_name')
                            ->label('Reviewer'),

                        Infolists\Components\TextEntry::make('listing.title')
                            ->label('Listing')
                            ->formatStateUsing(fn ($record) => self::extractTranslation($record->listing?->getTranslation('title', app()->getLocale()), 'Untitled')),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime(),

                        Infolists\Components\IconEntry::make('is_verified_booking')
                            ->label('Verified Booking')
                            ->boolean(),
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
                            ->label('Your Reply'),
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
                    ])
                    ->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
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
