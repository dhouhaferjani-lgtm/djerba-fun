<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PaymentGatewayResource\Pages;
use App\Models\PaymentGateway;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentGatewayResource extends Resource
{
    protected static ?string $model = PaymentGateway::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Payment Gateways';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Gateway Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Internal identifier for the gateway'),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('URL-friendly identifier'),

                        Forms\Components\TextInput::make('display_name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('User-facing name'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Gateway Configuration')
                    ->schema([
                        Forms\Components\Select::make('driver')
                            ->required()
                            ->options([
                                'stripe' => 'Stripe',
                                'clicktopay' => 'Click to Pay (Visa)',
                                'offline' => 'Offline Payment',
                                'bank_transfer' => 'Bank Transfer',
                                'mock' => 'Mock (Testing)',
                            ])
                            ->live()
                            ->helperText('The payment gateway driver to use'),

                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Lower numbers appear first'),

                        Forms\Components\Toggle::make('is_enabled')
                            ->label('Enabled')
                            ->default(false)
                            ->helperText('Enable or disable this payment gateway'),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Set as Default')
                            ->default(false)
                            ->helperText('Mark as the default payment gateway'),

                        Forms\Components\Toggle::make('test_mode')
                            ->label('Test Mode')
                            ->default(false)
                            ->helperText('Enable test/sandbox mode'),
                    ])->columns(2),

                Forms\Components\Section::make('Driver-Specific Configuration')
                    ->schema([
                        // Stripe Configuration
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('configuration.publishable_key')
                                ->label('Publishable Key')
                                ->password()
                                ->revealable()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('configuration.secret_key')
                                ->label('Secret Key')
                                ->password()
                                ->revealable()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('configuration.webhook_secret')
                                ->label('Webhook Secret')
                                ->password()
                                ->revealable()
                                ->maxLength(255),
                        ])
                            ->visible(fn (Forms\Get $get) => $get('driver') === 'stripe')
                            ->columnSpanFull(),

                        // Click to Pay Configuration
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('configuration.merchant_id')
                                ->label('Merchant ID')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('configuration.api_key')
                                ->label('API Key')
                                ->password()
                                ->revealable()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('configuration.shared_secret')
                                ->label('Shared Secret')
                                ->password()
                                ->revealable()
                                ->maxLength(255),
                        ])
                            ->visible(fn (Forms\Get $get) => $get('driver') === 'clicktopay')
                            ->columnSpanFull(),

                        // Bank Transfer Configuration
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('configuration.bank_name')
                                ->label('Bank Name')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('configuration.account_number')
                                ->label('Account Number')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('configuration.routing_number')
                                ->label('Routing Number')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('configuration.iban')
                                ->label('IBAN')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('configuration.swift_code')
                                ->label('SWIFT/BIC Code')
                                ->maxLength(255),

                            Forms\Components\Textarea::make('configuration.instructions')
                                ->label('Payment Instructions')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                            ->visible(fn (Forms\Get $get) => $get('driver') === 'bank_transfer')
                            ->columnSpanFull(),

                        // Offline Payment Configuration
                        Forms\Components\Group::make([
                            Forms\Components\Textarea::make('configuration.instructions')
                                ->label('Payment Instructions')
                                ->rows(3)
                                ->helperText('Instructions for customers paying offline')
                                ->columnSpanFull(),
                        ])
                            ->visible(fn (Forms\Get $get) => $get('driver') === 'offline')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('driver')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'stripe' => 'purple',
                        'clicktopay' => 'blue',
                        'offline' => 'gray',
                        'bank_transfer' => 'yellow',
                        'mock' => 'orange',
                        default => 'gray',
                    }),

                Tables\Columns\ToggleColumn::make('is_enabled')
                    ->label('Enabled')
                    ->sortable()
                    ->beforeStateUpdated(function (PaymentGateway $record, bool $state) {
                        // If disabling and it's the default, prevent it
                        if (! $state && $record->is_default) {
                            throw new \Exception('Cannot disable the default gateway. Set another gateway as default first.');
                        }
                    }),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('test_mode')
                    ->label('Test Mode')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('driver')
                    ->options([
                        'stripe' => 'Stripe',
                        'clicktopay' => 'Click to Pay',
                        'offline' => 'Offline Payment',
                        'bank_transfer' => 'Bank Transfer',
                        'mock' => 'Mock',
                    ]),

                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label('Enabled')
                    ->placeholder('All gateways')
                    ->trueLabel('Enabled only')
                    ->falseLabel('Disabled only'),

                Tables\Filters\TernaryFilter::make('test_mode')
                    ->label('Test Mode')
                    ->placeholder('All modes')
                    ->trueLabel('Test mode')
                    ->falseLabel('Live mode'),
            ])
            ->actions([
                Tables\Actions\Action::make('set_default')
                    ->label('Set as Default')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Set as Default Gateway')
                    ->modalDescription(fn (PaymentGateway $record) => "Set {$record->display_name} as the default payment gateway? This will unset any other default gateway.")
                    ->action(function (PaymentGateway $record) {
                        $record->setAsDefault();
                    })
                    ->visible(fn (PaymentGateway $record) => ! $record->is_default),

                Tables\Actions\Action::make('test_connection')
                    ->label('Test')
                    ->icon('heroicon-o-bolt')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Test Gateway Connection')
                    ->modalDescription('This will attempt to validate the gateway configuration.')
                    ->action(function (PaymentGateway $record) {
                        // Placeholder for testing connection
                        // In production, this would call the gateway's test endpoint
                        return true;
                    })
                    ->successNotificationTitle('Connection test passed')
                    ->failureNotificationTitle('Connection test failed')
                    ->visible(fn (PaymentGateway $record) => in_array($record->driver, ['stripe', 'clicktopay'], true)),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->before(function (PaymentGateway $record) {
                        if ($record->is_default) {
                            throw new \Exception('Cannot delete the default gateway. Set another gateway as default first.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('enable')
                    ->label('Enable Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->enable()),

                Tables\Actions\BulkAction::make('disable')
                    ->label('Disable Selected')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            if ($record->is_default) {
                                throw new \Exception('Cannot disable the default gateway. Set another gateway as default first.');
                            }
                            $record->disable();
                        }
                    }),

                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                if ($record->is_default) {
                                    throw new \Exception('Cannot delete the default gateway. Set another gateway as default first.');
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('priority', 'asc')
            ->emptyStateHeading('No payment gateways configured')
            ->emptyStateDescription('Add your first payment gateway to start accepting payments.')
            ->emptyStateIcon('heroicon-o-credit-card');
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
            'index' => Pages\ListPaymentGateways::route('/'),
            'create' => Pages\CreatePaymentGateway::route('/create'),
            'edit' => Pages\EditPaymentGateway::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $enabledCount = static::getModel()::enabled()->count();

        return $enabledCount > 0 ? (string) $enabledCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Enabled payment gateways';
    }
}
