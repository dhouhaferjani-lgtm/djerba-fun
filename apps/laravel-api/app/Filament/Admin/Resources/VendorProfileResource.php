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

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.people');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.vendor_kyc');
    }

    public static function getModelLabel(): string
    {
        return __('filament.resources.vendor_profile');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.resources.vendor_profiles');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.sections.vendor_information'))
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'display_name')
                            ->disabled()
                            ->preload(),

                        Forms\Components\TextInput::make('company_name')
                            ->label(__('filament.labels.company_name'))
                            ->maxLength(255),

                        Forms\Components\Select::make('company_type')
                            ->label(__('filament.labels.company_type'))
                            ->options([
                                'individual' => __('filament.options.individual'),
                                'company' => __('filament.options.company_llc'),
                                'nonprofit' => __('filament.options.nonprofit'),
                                'government' => __('filament.options.government'),
                            ]),

                        Forms\Components\TextInput::make('tax_id')
                            ->label(__('filament.labels.tax_id'))
                            ->maxLength(50),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('filament.sections.contact_information'))
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label(__('filament.labels.phone'))
                            ->tel(),

                        Forms\Components\TextInput::make('website_url')
                            ->label(__('filament.labels.website'))
                            ->url()
                            ->prefix('https://'),

                        Forms\Components\Textarea::make('description')
                            ->label(__('filament.labels.business_description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('filament.sections.address'))
                    ->schema([
                        Forms\Components\TextInput::make('address.street')
                            ->label(__('filament.labels.street_address')),

                        Forms\Components\TextInput::make('address.city')
                            ->label(__('filament.labels.city')),

                        Forms\Components\TextInput::make('address.postal_code')
                            ->label(__('filament.labels.postal_code')),

                        Forms\Components\TextInput::make('address.country')
                            ->label(__('filament.labels.country')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('filament.sections.kyc_status'))
                    ->schema([
                        Forms\Components\Select::make('kyc_status')
                            ->label(__('filament.labels.status'))
                            ->options([
                                KycStatus::PENDING->value => KycStatus::PENDING->label(),
                                KycStatus::SUBMITTED->value => KycStatus::SUBMITTED->label(),
                                KycStatus::VERIFIED->value => KycStatus::VERIFIED->label(),
                                KycStatus::REJECTED->value => KycStatus::REJECTED->label(),
                            ])
                            ->required(),

                        Forms\Components\Select::make('commission_tier')
                            ->label(__('filament.labels.commission_tier'))
                            ->options([
                                'standard' => __('filament.options.standard'),
                                'silver' => __('filament.options.silver'),
                                'gold' => __('filament.options.gold'),
                                'platinum' => __('filament.options.platinum'),
                            ])
                            ->helperText(__('filament.helpers.commission_tier_helper')),

                        Forms\Components\TextInput::make('payout_account_id')
                            ->label(__('filament.labels.payout_account_id'))
                            ->helperText(__('filament.helpers.payout_account_helper')),

                        Forms\Components\DateTimePicker::make('verified_at')
                            ->label(__('filament.labels.verified_at'))
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
                    ->label(__('filament.labels.vendor'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label(__('filament.labels.email'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('company_name')
                    ->label(__('filament.labels.company'))
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('company_type')
                    ->label(__('filament.labels.type'))
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
                    ->label(__('filament.labels.status'))
                    ->badge()
                    ->color(fn (KycStatus $state): string => $state->color()),

                Tables\Columns\TextColumn::make('commission_tier')
                    ->label(__('filament.labels.tier'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'platinum' => 'warning',
                        'gold' => 'success',
                        'silver' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('listings_count')
                    ->label(__('filament.resources.listings'))
                    ->getStateUsing(fn (VendorProfile $record) => $record->user?->listings()->count() ?? 0)
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('verified_at')
                    ->label(__('filament.labels.verified'))
                    ->date()
                    ->sortable()
                    ->placeholder(__('filament.tooltips.not_verified')),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.labels.joined'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kyc_status')
                    ->label(__('filament.labels.status'))
                    ->options([
                        KycStatus::PENDING->value => KycStatus::PENDING->label(),
                        KycStatus::SUBMITTED->value => KycStatus::SUBMITTED->label(),
                        KycStatus::VERIFIED->value => KycStatus::VERIFIED->label(),
                        KycStatus::REJECTED->value => KycStatus::REJECTED->label(),
                    ]),

                Tables\Filters\SelectFilter::make('commission_tier')
                    ->label(__('filament.labels.commission_tier'))
                    ->options([
                        'standard' => __('filament.options.standard_label'),
                        'silver' => __('filament.options.silver_label'),
                        'gold' => __('filament.options.gold_label'),
                        'platinum' => __('filament.options.platinum_label'),
                    ]),

                Tables\Filters\Filter::make('pending_review')
                    ->label(__('filament.filters.pending_kyc_review'))
                    ->query(fn (Builder $query): Builder => $query->where('kyc_status', KycStatus::SUBMITTED))
                    ->toggle(),

                Tables\Filters\Filter::make('verified')
                    ->label(__('filament.filters.verified_only'))
                    ->query(fn (Builder $query): Builder => $query->where('kyc_status', KycStatus::VERIFIED))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('verify')
                        ->label(__('filament.actions.verify_vendor'))
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('filament.modals.verify_vendor'))
                        ->modalDescription(__('filament.modals.verify_vendor_description'))
                        ->action(function (VendorProfile $record) {
                            $record->markAsVerified();

                            Notification::make()
                                ->title(__('filament.notifications.vendor_verified'))
                                ->body(__('filament.notifications.vendor_verified_body'))
                                ->success()
                                ->send();
                        })
                        ->visible(fn (VendorProfile $record) => $record->kyc_status === KycStatus::SUBMITTED),

                    Tables\Actions\Action::make('reject')
                        ->label(__('filament.actions.reject_kyc'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label(__('filament.labels.rejection_reason'))
                                ->required()
                                ->rows(3)
                                ->helperText(__('filament.helpers.document_request_helper')),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading(__('filament.modals.reject_kyc'))
                        ->action(function (VendorProfile $record, array $data) {
                            $record->update([
                                'kyc_status' => KycStatus::REJECTED,
                            ]);

                            // TODO: Send notification email to vendor

                            Notification::make()
                                ->title(__('filament.notifications.kyc_rejected'))
                                ->warning()
                                ->send();
                        })
                        ->visible(fn (VendorProfile $record) => $record->kyc_status === KycStatus::SUBMITTED),

                    Tables\Actions\Action::make('request_documents')
                        ->label(__('filament.actions.request_documents'))
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->form([
                            Forms\Components\CheckboxList::make('documents')
                                ->label(__('filament.labels.required_documents'))
                                ->options([
                                    'id_proof' => __('filament.options.id_proof'),
                                    'business_license' => __('filament.options.business_license'),
                                    'tax_certificate' => __('filament.options.tax_certificate'),
                                    'bank_statement' => __('filament.options.bank_statement'),
                                    'insurance' => __('filament.options.insurance'),
                                    'address_proof' => __('filament.options.address_proof'),
                                ])
                                ->required(),
                            Forms\Components\Textarea::make('notes')
                                ->label(__('filament.labels.additional_notes'))
                                ->rows(2),
                        ])
                        ->action(function (VendorProfile $record, array $data) {
                            // TODO: Send document request to vendor

                            Notification::make()
                                ->title(__('filament.notifications.document_request_sent'))
                                ->body(__('filament.notifications.document_request_sent_body'))
                                ->success()
                                ->send();
                        })
                        ->visible(fn (VendorProfile $record) => $record->kyc_status === KycStatus::PENDING),

                    Tables\Actions\Action::make('update_tier')
                        ->label(__('filament.actions.update_commission_tier'))
                        ->icon('heroicon-o-arrow-trending-up')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('commission_tier')
                                ->label(__('filament.labels.new_commission_tier'))
                                ->options([
                                    'standard' => __('filament.options.standard'),
                                    'silver' => __('filament.options.silver'),
                                    'gold' => __('filament.options.gold'),
                                    'platinum' => __('filament.options.platinum'),
                                ])
                                ->required(),
                        ])
                        ->action(function (VendorProfile $record, array $data) {
                            $record->update([
                                'commission_tier' => $data['commission_tier'],
                            ]);

                            Notification::make()
                                ->title(__('filament.notifications.commission_tier_updated'))
                                ->success()
                                ->send();
                        })
                        ->visible(fn (VendorProfile $record) => $record->kyc_status === KycStatus::VERIFIED),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_verify')
                        ->label(__('filament.actions.verify_selected'))
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
                                ->title(__('filament.notifications.vendors_verified', ['count' => $count]))
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
        return __('filament.tooltips.pending_kyc_review');
    }
}
