<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ReviewResource\Pages;

use App\Filament\Admin\Resources\ReviewResource;
use App\Models\Review;
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
                ->action(function () {
                    $this->record->publish();
                    $this->record->load('listing');
                    Review::recalculateListingRating($this->record->listing);

                    Notification::make()
                        ->title('Review Approved')
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
                ->action(function (array $data) {
                    $this->record->reject($data['reason']);
                    $this->record->load('listing');
                    Review::recalculateListingRating($this->record->listing);

                    Notification::make()
                        ->title('Review Rejected')
                        ->warning()
                        ->send();
                })
                ->visible(fn () => $this->record->moderation_status !== 'rejected'),

            Actions\Action::make('unpublish')
                ->label('Unpublish')
                ->icon('heroicon-o-eye-slash')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->unpublish();
                    $this->record->load('listing');
                    Review::recalculateListingRating($this->record->listing);

                    Notification::make()
                        ->title('Review Unpublished')
                        ->warning()
                        ->send();
                })
                ->visible(fn () => $this->record->is_published),

            Actions\DeleteAction::make(),
        ];
    }
}
