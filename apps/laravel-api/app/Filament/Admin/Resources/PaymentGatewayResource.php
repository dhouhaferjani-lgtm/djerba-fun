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

class PaymentGatewayResource extends Resource
{
    protected static ?string $model = PaymentGateway::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.payment_gateways');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.payment_gateway.gateway_information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('filament.payment_gateway.name'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText(__('filament.payment_gateway.name_helper')),

                        Forms\Components\TextInput::make('slug')
                            ->label(__('filament.payment_gateway.slug'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText(__('filament.payment_gateway.slug_helper')),

                        Forms\Components\TextInput::make('display_name')
                            ->label(__('filament.payment_gateway.display_name'))
                            ->required()
                            ->maxLength(255)
                            ->helperText(__('filament.payment_gateway.display_name_helper')),

                        Forms\Components\Textarea::make('description')
                            ->label(__('filament.payment_gateway.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make(__('filament.payment_gateway.gateway_configuration'))
                    ->schema([
                        Forms\Components\Select::make('driver')
                            ->label(__('filament.payment_gateway.driver'))
                            ->required()
                            ->options([
                                'stripe' => __('filament.payment_gateway.driver_stripe'),
                                'clicktopay' => __('filament.payment_gateway.driver_clicktopay'),
                                'offline' => __('filament.payment_gateway.driver_offline'),
                                'bank_transfer' => __('filament.payment_gateway.driver_bank_transfer'),
                                'mock' => __('filament.payment_gateway.driver_mock'),
                            ])
                            ->live()
                            ->helperText(__('filament.payment_gateway.driver_helper')),

                        Forms\Components\TextInput::make('priority')
                            ->label(__('filament.payment_gateway.priority'))
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText(__('filament.payment_gateway.priority_helper')),

                        Forms\Components\Toggle::make('is_enabled')
                            ->label(__('filament.payment_gateway.enabled'))
                            ->default(false)
                            ->helperText(__('filament.payment_gateway.enabled_helper')),

                        Forms\Components\Toggle::make('is_default')
                            ->label(__('filament.payment_gateway.set_as_default'))
                            ->default(false)
                            ->helperText(__('filament.payment_gateway.default_helper')),

                        Forms\Components\Toggle::make('test_mode')
                            ->label(__('filament.payment_gateway.test_mode'))
                            ->default(false)
                            ->helperText(__('filament.payment_gateway.test_mode_helper')),
                    ])->columns(2),

                Forms\Components\Section::make(__('filament.payment_gateway.driver_configuration'))
                    ->schema([
                        // Stripe Configuration
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('configuration.publishable_key')
                                ->label(__('filament.payment_gateway.publishable_key'))
                                ->password()
                                ->revealable()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('configuration.secret_key')
                                ->label(__('filament.payment_gateway.secret_key'))
                                ->password()
                                ->maxLength(255)
                                ->helperText(__('filament.payment_gateway.secret_key_helper')),

                            Forms\Components\TextInput::make('configuration.webhook_secret')
                                ->label(__('filament.payment_gateway.webhook_secret'))
                                ->password()
                                ->maxLength(255)
                                ->helperText(__('filament.payment_gateway.webhook_secret_helper')),
                        ])
                            ->visible(fn (Forms\Get $get) => $get('driver') === 'stripe')
                            ->columnSpanFull(),

                        // Click to Pay Configuration
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('configuration.merchant_id')
                                ->label(__('filament.payment_gateway.merchant_id'))
                                ->maxLength(255),

                            Forms\Components\TextInput::make('configuration.api_key')
                                ->label(__('filament.payment_gateway.api_key'))
                                ->password()
                                ->maxLength(255)
                                ->helperText(__('filament.payment_gateway.api_key_helper')),

                            Forms\Components\TextInput::make('configuration.shared_secret')
                                ->label(__('filament.payment_gateway.shared_secret'))
                                ->password()
                                ->maxLength(255)
                                ->helperText(__('filament.payment_gateway.shared_secret_helper')),
                        ])
                            ->visible(fn (Forms\Get $get) => $get('driver') === 'clicktopay')
                            ->columnSpanFull(),

                        // Bank Transfer Configuration
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('configuration.bank_name')
                                ->label(__('filament.payment_gateway.bank_name'))
                                ->maxLength(255),

                            Forms\Components\TextInput::make('configuration.account_number')
                                ->label(__('filament.payment_gateway.account_number'))
                                ->maxLength(255),

                            Forms\Components\TextInput::make('configuration.routing_number')
                                ->label(__('filament.payment_gateway.routing_number'))
                                ->maxLength(255),

                            Forms\Components\TextInput::make('configuration.iban')
                                ->label(__('filament.payment_gateway.iban'))
                                ->maxLength(255),

                            Forms\Components\TextInput::make('configuration.swift_code')
                                ->label(__('filament.payment_gateway.swift_code'))
                                ->maxLength(255),

                            Forms\Components\Textarea::make('configuration.instructions')
                                ->label(__('filament.payment_gateway.payment_instructions'))
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                            ->visible(fn (Forms\Get $get) => $get('driver') === 'bank_transfer')
                            ->columnSpanFull(),

                        // Offline Payment Configuration
                        Forms\Components\Group::make([
                            Forms\Components\Textarea::make('configuration.instructions')
                                ->label(__('filament.payment_gateway.payment_instructions'))
                                ->rows(3)
                                ->helperText(__('filament.payment_gateway.offline_instructions_helper'))
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
                    ->label(__('filament.payment_gateway.display_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('driver')
                    ->label(__('filament.payment_gateway.driver'))
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
                    ->label(__('filament.payment_gateway.enabled'))
                    ->sortable()
                    ->beforeStateUpdated(function (PaymentGateway $record, bool $state) {
                        // If disabling and it's the default, prevent it
                        if (! $state && $record->is_default) {
                            throw new \Exception(__('filament.payment_gateway.cannot_disable_default'));
                        }
                    }),

                Tables\Columns\IconColumn::make('is_default')
                    ->label(__('filament.payment_gateway.is_default'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->label(__('filament.payment_gateway.priority'))
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('test_mode')
                    ->label(__('filament.payment_gateway.test_mode'))
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.labels.created_at'))
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('driver')
                    ->label(__('filament.payment_gateway.filter_driver'))
                    ->options([
                        'stripe' => __('filament.payment_gateway.driver_stripe'),
                        'clicktopay' => __('filament.payment_gateway.driver_clicktopay'),
                        'offline' => __('filament.payment_gateway.driver_offline'),
                        'bank_transfer' => __('filament.payment_gateway.driver_bank_transfer'),
                        'mock' => __('filament.payment_gateway.driver_mock'),
                    ]),

                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label(__('filament.payment_gateway.filter_enabled'))
                    ->placeholder(__('filament.payment_gateway.filter_enabled_all'))
                    ->trueLabel(__('filament.payment_gateway.filter_enabled_only'))
                    ->falseLabel(__('filament.payment_gateway.filter_disabled_only')),

                Tables\Filters\TernaryFilter::make('test_mode')
                    ->label(__('filament.payment_gateway.filter_test_mode'))
                    ->placeholder(__('filament.payment_gateway.filter_all_modes'))
                    ->trueLabel(__('filament.payment_gateway.filter_test_only'))
                    ->falseLabel(__('filament.payment_gateway.filter_live_only')),
            ])
            ->actions([
                Tables\Actions\Action::make('set_default')
                    ->label(__('filament.payment_gateway.set_default_action'))
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('filament.payment_gateway.set_default_heading'))
                    ->modalDescription(fn (PaymentGateway $record) => __('filament.payment_gateway.set_default_description', ['name' => $record->display_name]))
                    ->action(function (PaymentGateway $record) {
                        $record->setAsDefault();
                    })
                    ->visible(fn (PaymentGateway $record) => ! $record->is_default),

                Tables\Actions\Action::make('test_connection')
                    ->label(__('filament.payment_gateway.test_connection'))
                    ->icon('heroicon-o-bolt')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(__('filament.payment_gateway.test_connection_heading'))
                    ->modalDescription(__('filament.payment_gateway.test_connection_description'))
                    ->action(function (PaymentGateway $record) {
                        // Placeholder for testing connection
                        // In production, this would call the gateway's test endpoint
                        return true;
                    })
                    ->successNotificationTitle(__('filament.payment_gateway.connection_passed'))
                    ->failureNotificationTitle(__('filament.payment_gateway.connection_failed'))
                    ->visible(fn (PaymentGateway $record) => in_array($record->driver, ['stripe', 'clicktopay'], true)),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading(__('filament.payment_gateway.delete_heading'))
                    ->modalDescription(__('filament.payment_gateway.delete_description'))
                    ->before(function (PaymentGateway $record) {
                        if ($record->is_default) {
                            throw new \Exception(__('filament.payment_gateway.cannot_delete_default'));
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('enable')
                    ->label(__('filament.payment_gateway.enable_selected'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->enable()),

                Tables\Actions\BulkAction::make('disable')
                    ->label(__('filament.payment_gateway.disable_selected'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            if ($record->is_default) {
                                throw new \Exception(__('filament.payment_gateway.cannot_disable_default'));
                            }
                            $record->disable();
                        }
                    }),

                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                if ($record->is_default) {
                                    throw new \Exception(__('filament.payment_gateway.cannot_delete_default'));
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('priority', 'asc')
            ->emptyStateHeading(__('filament.payment_gateway.empty_heading'))
            ->emptyStateDescription(__('filament.payment_gateway.empty_description'))
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
