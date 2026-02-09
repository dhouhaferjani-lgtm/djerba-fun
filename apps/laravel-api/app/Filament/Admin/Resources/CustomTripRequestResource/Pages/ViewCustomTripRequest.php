<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CustomTripRequestResource\Pages;

use App\Enums\CustomTripRequestStatus;
use App\Filament\Admin\Resources\CustomTripRequestResource;
use App\Mail\CustomTripRequestConfirmationMail;
use App\Models\CustomTripRequest;
use App\Services\EmailLogService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomTripRequest extends ViewRecord
{
    protected static string $resource = CustomTripRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resend_confirmation')
                ->label('Resend Confirmation Email')
                ->icon('heroicon-o-envelope')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Resend Confirmation Email')
                ->modalDescription('This will send a new confirmation email to the traveler.')
                ->action(function (CustomTripRequest $record) {
                    try {
                        $service = app(EmailLogService::class);
                        $service->queue(
                            $record->contact_email,
                            new CustomTripRequestConfirmationMail($record),
                            null,
                            ['name' => $record->contact_name, 'phone' => $record->contact_phone]
                        );

                        Notification::make()
                            ->success()
                            ->title('Confirmation email queued')
                            ->body("Email queued for {$record->contact_email}")
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Email failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

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
