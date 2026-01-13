<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CustomTripRequestResource\Pages;

use App\Enums\CustomTripRequestStatus;
use App\Filament\Admin\Resources\CustomTripRequestResource;
use App\Models\CustomTripRequest;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomTripRequest extends ViewRecord
{
    protected static string $resource = CustomTripRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_contacted')
                ->label('Mark as Contacted')
                ->icon('heroicon-o-phone')
                ->color('info')
                ->requiresConfirmation()
                ->action(fn (CustomTripRequest $record) => $record->markAsContacted())
                ->visible(fn (CustomTripRequest $record) => $record->status === CustomTripRequestStatus::PENDING),

            Actions\Action::make('send_proposal')
                ->label('Mark Proposal Sent')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->requiresConfirmation()
                ->action(fn (CustomTripRequest $record) => $record->markAsProposal())
                ->visible(fn (CustomTripRequest $record) => $record->status === CustomTripRequestStatus::CONTACTED),

            Actions\Action::make('confirm')
                ->label('Mark Confirmed')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn (CustomTripRequest $record) => $record->markAsConfirmed())
                ->visible(fn (CustomTripRequest $record) => $record->status === CustomTripRequestStatus::PROPOSAL),

            Actions\Action::make('complete')
                ->label('Mark Completed')
                ->icon('heroicon-o-flag')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn (CustomTripRequest $record) => $record->markAsCompleted())
                ->visible(fn (CustomTripRequest $record) => $record->status === CustomTripRequestStatus::CONFIRMED),

            Actions\Action::make('cancel')
                ->label('Cancel Request')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel Custom Trip Request')
                ->modalDescription('Are you sure you want to cancel this request? This action cannot be undone.')
                ->action(fn (CustomTripRequest $record) => $record->markAsCancelled())
                ->visible(fn (CustomTripRequest $record) => $record->isActive()),
        ];
    }
}
