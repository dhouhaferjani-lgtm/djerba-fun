<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PartnerResource\Pages;
use App\Models\Partner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static ?string $navigationIcon = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.partners');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.partner.partner_information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label(__('filament.partner.partner_name')),

                        Forms\Components\TextInput::make('company_name')
                            ->required()
                            ->maxLength(255)
                            ->label(__('filament.partner.company_name')),

                        Forms\Components\TextInput::make('company_type')
                            ->maxLength(255)
                            ->label(__('filament.partner.company_type'))
                            ->placeholder(__('filament.partner.company_type_placeholder')),

                        Forms\Components\Select::make('partner_tier')
                            ->options([
                                'standard' => __('filament.partner.tier_standard'),
                                'premium' => __('filament.partner.tier_premium'),
                                'enterprise' => __('filament.partner.tier_enterprise'),
                            ])
                            ->default('standard')
                            ->required()
                            ->label(__('filament.partner.partner_tier')),

                        Forms\Components\Select::make('kyc_status')
                            ->options([
                                'pending' => __('filament.partner.kyc_pending'),
                                'under_review' => __('filament.partner.kyc_under_review'),
                                'approved' => __('filament.partner.kyc_approved'),
                                'rejected' => __('filament.partner.kyc_rejected'),
                            ])
                            ->default('pending')
                            ->required()
                            ->label(__('filament.partner.kyc_status')),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('filament.partner.active'))
                            ->default(true)
                            ->required(),

                        Forms\Components\Toggle::make('sandbox_mode')
                            ->label(__('filament.partner.sandbox_mode'))
                            ->default(true)
                            ->helperText(__('filament.partner.sandbox_mode_helper')),

                        Forms\Components\TextInput::make('rate_limit')
                            ->label(__('filament.partner.rate_limit'))
                            ->numeric()
                            ->default(60)
                            ->required()
                            ->minValue(1)
                            ->maxValue(10000),
                    ])->columns(3),

                Forms\Components\Section::make(__('filament.partner.contact_information'))
                    ->schema([
                        Forms\Components\TextInput::make('website_url')
                            ->url()
                            ->maxLength(255)
                            ->label(__('filament.partner.website_url')),

                        Forms\Components\TextInput::make('contact_email')
                            ->email()
                            ->maxLength(255)
                            ->label(__('filament.partner.contact_email')),

                        Forms\Components\TextInput::make('contact_phone')
                            ->tel()
                            ->maxLength(50)
                            ->label(__('filament.partner.contact_phone')),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000)
                            ->label(__('filament.partner.description'))
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make(__('filament.partner.permissions'))
                    ->schema([
                        Forms\Components\TagsInput::make('permissions')
                            ->label(__('filament.partner.permissions'))
                            ->placeholder(__('filament.partner.permissions_placeholder'))
                            ->helperText(__('filament.partner.permissions_helper'))
                            ->suggestions([
                                'listings:read',
                                'listings:search',
                                'bookings:read',
                                'bookings:create',
                                'bookings:cancel',
                                '*',
                            ]),
                    ]),

                Forms\Components\Section::make(__('filament.partner.webhooks'))
                    ->schema([
                        Forms\Components\TextInput::make('webhook_url')
                            ->url()
                            ->maxLength(255)
                            ->label(__('filament.partner.webhook_url'))
                            ->helperText(__('filament.partner.webhook_url_helper')),

                        Forms\Components\TextInput::make('webhook_secret')
                            ->maxLength(255)
                            ->label(__('filament.partner.webhook_secret'))
                            ->helperText(__('filament.partner.webhook_secret_helper'))
                            ->password()
                            ->revealable(),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make(__('filament.partner.security'))
                    ->schema([
                        Forms\Components\TagsInput::make('ip_whitelist')
                            ->label(__('filament.partner.ip_whitelist'))
                            ->placeholder(__('filament.partner.ip_whitelist_placeholder'))
                            ->helperText(__('filament.partner.ip_whitelist_helper')),

                        Forms\Components\DateTimePicker::make('api_key_expires_at')
                            ->label(__('filament.partner.api_key_expiration'))
                            ->helperText(__('filament.partner.api_key_expiration_helper')),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make(__('filament.partner.metadata'))
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label(__('filament.partner.additional_metadata'))
                            ->keyLabel(__('filament.partner.key'))
                            ->valueLabel(__('filament.partner.value'))
                            ->addActionLabel(__('filament.partner.add_metadata')),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make(__('filament.partner.api_credentials'))
                    ->schema([
                        Forms\Components\Placeholder::make('api_key_info')
                            ->label('')
                            ->content(__('filament.partner.api_key_info')),

                        Forms\Components\TextInput::make('api_key')
                            ->label(__('filament.partner.api_key_current'))
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText(__('filament.partner.api_key_helper')),
                    ])
                    ->hiddenOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament.partner.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('company_name')
                    ->label(__('filament.partner.company'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('partner_tier')
                    ->label(__('filament.partner.tier'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'standard' => 'gray',
                        'premium' => 'warning',
                        'enterprise' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('kyc_status')
                    ->label(__('filament.partner.kyc_status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'under_review' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('filament.partner.active'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('sandbox_mode')
                    ->label(__('filament.partner.sandbox'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rate_limit')
                    ->label(__('filament.partner.rate_limit'))
                    ->suffix(' ' . __('filament.partner.req_min'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('permissions')
                    ->label(__('filament.partner.permissions'))
                    ->badge()
                    ->separator(',')
                    ->limitList(3)
                    ->formatStateUsing(fn ($state) => $state === '*' ? __('filament.partner.all_permissions') : $state),

                Tables\Columns\TextColumn::make('last_used_at')
                    ->label(__('filament.partner.last_used'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('filament.partner.never')),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.partner.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('filament.partner.active_status'))
                    ->placeholder(__('filament.partner.all_partners'))
                    ->trueLabel(__('filament.partner.active_only'))
                    ->falseLabel(__('filament.partner.inactive_only')),

                Tables\Filters\SelectFilter::make('kyc_status')
                    ->label(__('filament.partner.kyc_status'))
                    ->options([
                        'pending' => __('filament.partner.kyc_pending'),
                        'under_review' => __('filament.partner.kyc_under_review'),
                        'approved' => __('filament.partner.kyc_approved'),
                        'rejected' => __('filament.partner.kyc_rejected'),
                    ]),

                Tables\Filters\SelectFilter::make('partner_tier')
                    ->label(__('filament.partner.partner_tier'))
                    ->options([
                        'standard' => __('filament.partner.tier_standard'),
                        'premium' => __('filament.partner.tier_premium'),
                        'enterprise' => __('filament.partner.tier_enterprise'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_logs')
                    ->label(__('filament.partner.view_logs'))
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Partner $record) => route('filament.admin.resources.partners.logs', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
            'logs' => Pages\ViewPartnerLogs::route('/{record}/logs'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::active()->count();

        return $count > 0 ? (string) $count : null;
    }
}
