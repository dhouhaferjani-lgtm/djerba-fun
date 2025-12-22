<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\KycStatus;
use App\Filament\Admin\Resources\VendorProfileResource\Pages;
use App\Models\VendorProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VendorProfileResource extends Resource
{
    protected static ?string $model = VendorProfile::class;

    protected static ?string $navigationIcon = null;

    protected static ?string $navigationGroup = 'People';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Vendor KYC';

    protected static ?string $modelLabel = 'Vendor Profile';

    protected static ?string $pluralModelLabel = 'Vendor Profiles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Vendor Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'display_name')
                            ->disabled()
                            ->preload(),

                        Forms\Components\TextInput::make('company_name')
                            ->label('Company Name')
                            ->maxLength(255),

                        Forms\Components\Select::make('company_type')
                            ->label('Company Type')
                            ->options([
                                'individual' => 'Individual / Sole Proprietor',
                                'company' => 'Company / LLC',
                                'nonprofit' => 'Non-Profit Organization',
                                'government' => 'Government Entity',
                            ]),

                        Forms\Components\TextInput::make('tax_id')
                            ->label('Tax ID / VAT Number')
                            ->maxLength(50),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel(),

                        Forms\Components\TextInput::make('website_url')
                            ->label('Website')
                            ->url()
                            ->prefix('https://'),

                        Forms\Components\Textarea::make('description')
                            ->label('Business Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Address')
                    ->schema([
                        Forms\Components\TextInput::make('address.street')
                            ->label('Street Address'),

                        Forms\Components\TextInput::make('address.city')
                            ->label('City'),

                        Forms\Components\TextInput::make('address.postal_code')
                            ->label('Postal Code'),

                        Forms\Components\TextInput::make('address.country')
                            ->label('Country'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('KYC Status')
                    ->schema([
                        Forms\Components\Select::make('kyc_status')
                            ->label('KYC Status')
                            ->options([
                                KycStatus::PENDING->value => KycStatus::PENDING->label(),
                                KycStatus::SUBMITTED->value => KycStatus::SUBMITTED->label(),
                                KycStatus::VERIFIED->value => KycStatus::VERIFIED->label(),
                                KycStatus::REJECTED->value => KycStatus::REJECTED->label(),
                            ])
                            ->required(),

                        Forms\Components\Select::make('commission_tier')
                            ->label('Commission Tier')
                            ->options([
                                'standard' => 'Standard (15%)',
                                'silver' => 'Silver (12%)',
                                'gold' => 'Gold (10%)',
                                'platinum' => 'Platinum (8%)',
                            ])
                            ->helperText('Commission rate charged on bookings'),

                        Forms\Components\TextInput::make('payout_account_id')
                            ->label('Payout Account ID')
                            ->helperText('Stripe Connect account or bank account reference'),

                        Forms\Components\DateTimePicker::make('verified_at')
                            ->label('Verified At')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.display_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('company_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'individual' => 'gray',
                        'company' => 'info',
                        'nonprofit' => 'success',
                        'government' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('kyc_status')
                    ->label('KYC Status')
                    ->badge()
                    ->color(fn (KycStatus $state): string => $state->color()),

                Tables\Columns\TextColumn::make('commission_tier')
                    ->label('Tier')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'platinum' => 'warning',
                        'gold' => 'success',
                        'silver' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('listings_count')
                    ->label('Listings')
                    ->getStateUsing(fn (VendorProfile $record) => $record->user?->listings()->count() ?? 0)
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('verified_at')
                    ->label('Verified')
                    ->date()
                    ->sortable()
                    ->placeholder('Not verified'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kyc_status')
                    ->label('KYC Status')
                    ->options([
                        KycStatus::PENDING->value => KycStatus::PENDING->label(),
                        KycStatus::SUBMITTED->value => KycStatus::SUBMITTED->label(),
                        KycStatus::VERIFIED->value => KycStatus::VERIFIED->label(),
                        KycStatus::REJECTED->value => KycStatus::REJECTED->label(),
                    ]),

                Tables\Filters\SelectFilter::make('commission_tier')
                    ->label('Commission Tier')
                    ->options([
                        'standard' => 'Standard',
                        'silver' => 'Silver',
                        'gold' => 'Gold',
                        'platinum' => 'Platinum',
                    ]),

                Tables\Filters\Filter::make('pending_review')
                    ->label('Pending KYC Review')
                    ->query(fn (Builder $query): Builder => $query->where('kyc_status', KycStatus::SUBMITTED))
                    ->toggle(),

                Tables\Filters\Filter::make('verified')
                    ->label('Verified Only')
                    ->query(fn (Builder $query): Builder => $query->where('kyc_status', KycStatus::VERIFIED))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('verify')
                        ->label('Verify Vendor')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Verify Vendor')
                        ->modalDescription('This will mark the vendor as verified and allow them to publish listings.')
                        ->action(function (VendorProfile $record) {
                            $record->markAsVerified();

                            Notification::make()
                                ->title('Vendor Verified')
                                ->body('The vendor has been verified successfully.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (VendorProfile $record) => $record->kyc_status === KycStatus::SUBMITTED),

                    Tables\Actions\Action::make('reject')
                        ->label('Reject KYC')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Rejection Reason')
                                ->required()
                                ->rows(3)
                                ->helperText('This will be sent to the vendor.'),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Reject KYC Application')
                        ->action(function (VendorProfile $record, array $data) {
                            $record->update([
                                'kyc_status' => KycStatus::REJECTED,
                            ]);

                            // TODO: Send notification email to vendor

                            Notification::make()
                                ->title('KYC Rejected')
                                ->warning()
                                ->send();
                        })
                        ->visible(fn (VendorProfile $record) => $record->kyc_status === KycStatus::SUBMITTED),

                    Tables\Actions\Action::make('request_documents')
                        ->label('Request Documents')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->form([
                            Forms\Components\CheckboxList::make('documents')
                                ->label('Required Documents')
                                ->options([
                                    'id_proof' => 'Government ID (Passport/National ID)',
                                    'business_license' => 'Business License',
                                    'tax_certificate' => 'Tax Certificate',
                                    'bank_statement' => 'Bank Statement',
                                    'insurance' => 'Liability Insurance',
                                    'address_proof' => 'Proof of Address',
                                ])
                                ->required(),
                            Forms\Components\Textarea::make('notes')
                                ->label('Additional Notes')
                                ->rows(2),
                        ])
                        ->action(function (VendorProfile $record, array $data) {
                            // TODO: Send document request to vendor

                            Notification::make()
                                ->title('Document Request Sent')
                                ->body('The vendor has been notified to submit additional documents.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (VendorProfile $record) => $record->kyc_status === KycStatus::PENDING),

                    Tables\Actions\Action::make('update_tier')
                        ->label('Update Commission Tier')
                        ->icon('heroicon-o-arrow-trending-up')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('commission_tier')
                                ->label('New Commission Tier')
                                ->options([
                                    'standard' => 'Standard (15%)',
                                    'silver' => 'Silver (12%)',
                                    'gold' => 'Gold (10%)',
                                    'platinum' => 'Platinum (8%)',
                                ])
                                ->required(),
                        ])
                        ->action(function (VendorProfile $record, array $data) {
                            $record->update([
                                'commission_tier' => $data['commission_tier'],
                            ]);

                            Notification::make()
                                ->title('Commission Tier Updated')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (VendorProfile $record) => $record->kyc_status === KycStatus::VERIFIED),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_verify')
                        ->label('Verify Selected')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;

                            foreach ($records as $record) {
                                if ($record->kyc_status === KycStatus::SUBMITTED) {
                                    $record->markAsVerified();
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("$count vendors verified")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorProfiles::route('/'),
            'create' => Pages\CreateVendorProfile::route('/create'),
            'view' => Pages\ViewVendorProfile::route('/{record}'),
            'edit' => Pages\EditVendorProfile::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('kyc_status', KycStatus::SUBMITTED)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pending KYC review';
    }
}
