<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PartnerResource\Pages;
use App\Models\Partner;
use App\Enums\PartnerKycStatus;
use App\Enums\PartnerTier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static ?string $navigationIcon = null;

    protected static ?string $navigationLabel = 'API Partners';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Partner Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Partner Name'),

                        Forms\Components\TextInput::make('company_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Company Name'),

                        Forms\Components\TextInput::make('company_type')
                            ->maxLength(255)
                            ->label('Company Type')
                            ->placeholder('e.g., Hotel, Tour Operator, Travel Agency'),

                        Forms\Components\Select::make('partner_tier')
                            ->options([
                                'standard' => 'Standard',
                                'premium' => 'Premium',
                                'enterprise' => 'Enterprise',
                            ])
                            ->default('standard')
                            ->required()
                            ->label('Partner Tier'),

                        Forms\Components\Select::make('kyc_status')
                            ->options([
                                'pending' => 'Pending',
                                'under_review' => 'Under Review',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->default('pending')
                            ->required()
                            ->label('KYC Status'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->required(),

                        Forms\Components\Toggle::make('sandbox_mode')
                            ->label('Sandbox Mode')
                            ->default(true)
                            ->helperText('Sandbox mode partners can only access test data'),

                        Forms\Components\TextInput::make('rate_limit')
                            ->label('Rate Limit (per minute)')
                            ->numeric()
                            ->default(60)
                            ->required()
                            ->minValue(1)
                            ->maxValue(10000),
                    ])->columns(3),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('website_url')
                            ->url()
                            ->maxLength(255)
                            ->label('Website URL'),

                        Forms\Components\TextInput::make('contact_email')
                            ->email()
                            ->maxLength(255)
                            ->label('Contact Email'),

                        Forms\Components\TextInput::make('contact_phone')
                            ->tel()
                            ->maxLength(50)
                            ->label('Contact Phone'),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000)
                            ->label('Description')
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('Permissions')
                    ->schema([
                        Forms\Components\TagsInput::make('permissions')
                            ->label('Permissions')
                            ->placeholder('Enter permissions (e.g., listings:read, bookings:create)')
                            ->helperText('Use * for all permissions, or resource:action format')
                            ->suggestions([
                                'listings:read',
                                'listings:search',
                                'bookings:read',
                                'bookings:create',
                                'bookings:cancel',
                                '*',
                            ]),
                    ]),

                Forms\Components\Section::make('Webhooks')
                    ->schema([
                        Forms\Components\TextInput::make('webhook_url')
                            ->url()
                            ->maxLength(255)
                            ->label('Webhook URL')
                            ->helperText('Events will be sent to this URL'),

                        Forms\Components\TextInput::make('webhook_secret')
                            ->maxLength(255)
                            ->label('Webhook Secret')
                            ->helperText('Used to verify webhook signatures')
                            ->password()
                            ->revealable(),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Security')
                    ->schema([
                        Forms\Components\TagsInput::make('ip_whitelist')
                            ->label('IP Whitelist')
                            ->placeholder('Add IP addresses')
                            ->helperText('Leave empty to allow all IPs'),

                        Forms\Components\DateTimePicker::make('api_key_expires_at')
                            ->label('API Key Expiration')
                            ->helperText('Leave empty for no expiration'),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Additional Metadata')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->addActionLabel('Add metadata'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('API Credentials')
                    ->schema([
                        Forms\Components\Placeholder::make('api_key_info')
                            ->label('')
                            ->content('API credentials were generated automatically when this partner was created. The credentials were shown once in a notification and cannot be retrieved again. To generate new credentials, use the CLI command: php artisan partner:create'),

                        Forms\Components\TextInput::make('api_key')
                            ->label('API Key (Current)')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('This is the current API key. The secret cannot be displayed for security reasons.'),
                    ])
                    ->hiddenOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('partner_tier')
                    ->label('Tier')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'standard' => 'gray',
                        'premium' => 'warning',
                        'enterprise' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('kyc_status')
                    ->label('KYC Status')
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
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('sandbox_mode')
                    ->label('Sandbox')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rate_limit')
                    ->label('Rate Limit')
                    ->suffix(' req/min')
                    ->sortable(),

                Tables\Columns\TextColumn::make('permissions')
                    ->label('Permissions')
                    ->badge()
                    ->separator(',')
                    ->limitList(3)
                    ->formatStateUsing(fn ($state) => $state === '*' ? 'All' : $state),

                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All partners')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\SelectFilter::make('kyc_status')
                    ->options([
                        'pending' => 'Pending',
                        'under_review' => 'Under Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                Tables\Filters\SelectFilter::make('partner_tier')
                    ->options([
                        'standard' => 'Standard',
                        'premium' => 'Premium',
                        'enterprise' => 'Enterprise',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_logs')
                    ->label('View Logs')
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
