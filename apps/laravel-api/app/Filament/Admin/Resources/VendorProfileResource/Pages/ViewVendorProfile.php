<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\VendorProfileResource\Pages;

use App\Enums\KycStatus;
use App\Filament\Admin\Resources\VendorProfileResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewVendorProfile extends ViewRecord
{
    protected static string $resource = VendorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('verify')
                ->label('Verify Vendor')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Verify Vendor')
                ->modalDescription('This will mark the vendor as verified and allow them to publish listings.')
                ->action(function () {
                    $this->record->markAsVerified();

                    Notification::make()
                        ->title('Vendor Verified')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->kyc_status === KycStatus::SUBMITTED),

            Actions\Action::make('reject')
                ->label('Reject KYC')
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
                    $this->record->update([
                        'kyc_status' => KycStatus::REJECTED,
                    ]);

                    Notification::make()
                        ->title('KYC Rejected')
                        ->warning()
                        ->send();
                })
                ->visible(fn () => $this->record->kyc_status === KycStatus::SUBMITTED),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Vendor Overview')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.display_name')
                            ->label('Name'),

                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Email')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('kyc_status')
                            ->label('KYC Status')
                            ->badge()
                            ->color(fn (KycStatus $state): string => $state->color()),

                        Infolists\Components\TextEntry::make('verified_at')
                            ->label('Verified At')
                            ->dateTime()
                            ->placeholder('Not verified'),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Company Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('company_name')
                            ->label('Company Name')
                            ->placeholder('Not provided'),

                        Infolists\Components\TextEntry::make('company_type')
                            ->label('Company Type')
                            ->badge()
                            ->placeholder('Not provided'),

                        Infolists\Components\TextEntry::make('tax_id')
                            ->label('Tax ID / VAT')
                            ->copyable()
                            ->placeholder('Not provided'),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Business Description')
                            ->columnSpanFull()
                            ->placeholder('No description provided'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Contact Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('phone')
                            ->label('Phone')
                            ->copyable()
                            ->placeholder('Not provided'),

                        Infolists\Components\TextEntry::make('website_url')
                            ->label('Website')
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab()
                            ->placeholder('Not provided'),

                        Infolists\Components\TextEntry::make('address')
                            ->label('Address')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) {
                                    return null;
                                }
                                $parts = array_filter([
                                    $state['street'] ?? null,
                                    $state['city'] ?? null,
                                    $state['postal_code'] ?? null,
                                    $state['country'] ?? null,
                                ]);

                                return implode(', ', $parts);
                            })
                            ->placeholder('Not provided'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Commission & Payouts')
                    ->schema([
                        Infolists\Components\TextEntry::make('commission_tier')
                            ->label('Commission Tier')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'platinum' => 'warning',
                                'gold' => 'success',
                                'silver' => 'info',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'platinum' => 'Platinum (8%)',
                                'gold' => 'Gold (10%)',
                                'silver' => 'Silver (12%)',
                                'standard' => 'Standard (15%)',
                                default => 'Standard (15%)',
                            }),

                        Infolists\Components\TextEntry::make('payout_account_id')
                            ->label('Payout Account')
                            ->copyable()
                            ->placeholder('Not configured'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Activity')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.listings_count')
                            ->label('Total Listings')
                            ->placeholder('0'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Profile Created')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])
                    ->columns(3),
            ]);
    }
}
