<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\ReviewResource\Pages;

use App\Filament\Vendor\Resources\ReviewResource;
use App\Models\Review;
use App\Models\ReviewReply;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewReview extends ViewRecord
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve Review')
                ->modalDescription('This review will be published and visible to all travelers.')
                ->action(function () {
                    $this->record->publish();
                    Review::recalculateListingRating($this->record->listing);

                    Notification::make()
                        ->title('Review Approved')
                        ->body('The review is now published.')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->moderation_status !== 'published'),

            Actions\Action::make('reject')
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
                ->modalHeading('Reject Review')
                ->action(function (array $data) {
                    $this->record->reject($data['reason']);
                    Review::recalculateListingRating($this->record->listing);

                    Notification::make()
                        ->title('Review Rejected')
                        ->warning()
                        ->send();
                })
                ->visible(fn () => $this->record->moderation_status !== 'rejected'),

            Actions\Action::make('reply')
                ->label('Reply to Review')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('primary')
                ->form([
                    Forms\Components\Textarea::make('content')
                        ->label('Your Reply')
                        ->required()
                        ->rows(4)
                        ->placeholder('Write your response to this review...'),
                ])
                ->action(function (array $data) {
                    ReviewReply::create([
                        'review_id' => $this->record->id,
                        'vendor_id' => auth()->id(),
                        'content' => $data['content'],
                    ]);

                    Notification::make()
                        ->title('Reply Posted')
                        ->body('Your reply has been added to the review.')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->reply === null),
        ];
    }
}
