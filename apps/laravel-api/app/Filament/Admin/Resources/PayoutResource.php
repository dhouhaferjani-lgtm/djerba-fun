<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\PayoutMethod;
use App\Enums\PayoutStatus;
use App\Filament\Admin\Resources\PayoutResource\Pages;
use App\Models\Payout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static ?string $navigationIcon = null;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.operations');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.payouts');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.sections.payout_information'))
                    ->schema([
                        Forms\Components\Select::make('vendor_id')
                            ->relationship('vendor', 'display_name')
                            ->label(__('filament.labels.vendor'))
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('status')
                            ->label(__('filament.labels.status'))
                            ->options([
                                PayoutStatus::PENDING->value => __('filament.options.pending'),
                                PayoutStatus::PROCESSING->value => __('filament.options.processing'),
                                PayoutStatus::COMPLETED->value => __('filament.options.completed'),
                                PayoutStatus::FAILED->value => __('filament.options.failed'),
                            ])
                            ->required()
                            ->default(PayoutStatus::PENDING->value),

                        Forms\Components\TextInput::make('amount')
                            ->label(__('filament.labels.amount'))
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0),

                        Forms\Components\TextInput::make('currency')
                            ->label(__('filament.labels.currency'))
                            ->required()
                            ->default('CAD')
                            ->maxLength(3),

                        Forms\Components\Select::make('payout_method')
                            ->label(__('filament.labels.payout_method'))
                            ->options([
                                PayoutMethod::BANK_TRANSFER->value => __('filament.options.bank_transfer'),
                                PayoutMethod::PAYPAL->value => __('filament.options.paypal'),
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('reference')
                            ->label(__('filament.labels.reference'))
                            ->maxLength(255)
                            ->helperText(__('filament.helpers.transaction_reference_helper')),
                    ])->columns(2),

                Forms\Components\Section::make(__('filament.sections.additional_information'))
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label(__('filament.labels.notes'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('processed_at')
                            ->label(__('filament.labels.processed_at'))
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vendor.display_name')
                    ->label(__('filament.labels.vendor'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('filament.labels.amount'))
                    ->money('CAD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament.labels.status'))
                    ->badge()
                    ->formatStateUsing(fn (PayoutStatus $state) => $state->label())
                    ->color(fn (PayoutStatus $state) => $state->color()),

                Tables\Columns\TextColumn::make('payout_method')
                    ->label(__('filament.labels.payout_method'))
                    ->formatStateUsing(fn (PayoutMethod $state) => $state->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label(__('filament.labels.reference'))
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label(__('filament.labels.processed_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament.labels.status'))
                    ->options([
                        PayoutStatus::PENDING->value => __('filament.options.pending'),
                        PayoutStatus::PROCESSING->value => __('filament.options.processing'),
                        PayoutStatus::COMPLETED->value => __('filament.options.completed'),
                        PayoutStatus::FAILED->value => __('filament.options.failed'),
                    ]),

                Tables\Filters\SelectFilter::make('payout_method')
                    ->label(__('filament.labels.payout_method'))
                    ->options([
                        PayoutMethod::BANK_TRANSFER->value => __('filament.options.bank_transfer'),
                        PayoutMethod::PAYPAL->value => __('filament.options.paypal'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label(__('filament.actions.approve'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Payout $record) => $record->status === PayoutStatus::PENDING)
                        ->action(function (Payout $record) {
                            $record->markAsProcessing();

                            // Notify vendor
                            try {
                                $vendor = $record->vendor;
                                if ($vendor) {
                                    $vendor->notifyNow(
                                        Notification::make()
                                            ->title('Payout Being Processed')
                                            ->icon('heroicon-o-banknotes')
                                            ->body("Your payout of {$record->amount} {$record->currency} is being processed.")
                                            ->toDatabase()
                                    );
                                }
                            } catch (\Throwable $e) {
                                \Log::error('Failed to send payout processing notification', ['error' => $e->getMessage()]);
                            }

                            Notification::make()
                                ->success()
                                ->title(__('filament.notifications.payout_approved'))
                                ->body(__('filament.notifications.payout_approved_body'))
                                ->send();
                        }),

                    Tables\Actions\Action::make('complete')
                        ->label(__('filament.actions.complete'))
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\TextInput::make('reference')
                                ->required()
                                ->label(__('filament.labels.transaction_reference'))
                                ->helperText(__('filament.helpers.enter_transaction_reference')),
                        ])
                        ->visible(fn (Payout $record) => $record->status === PayoutStatus::PROCESSING)
                        ->action(function (Payout $record, array $data) {
                            $record->markAsCompleted($data['reference']);

                            // Notify vendor
                            try {
                                $vendor = $record->vendor;
                                if ($vendor) {
                                    $vendor->notifyNow(
                                        Notification::make()
                                            ->title('Payout Completed')
                                            ->icon('heroicon-o-banknotes')
                                            ->body("Your payout of {$record->amount} {$record->currency} has been completed. Reference: {$data['reference']}")
                                            ->success()
                                            ->toDatabase()
                                    );
                                }
                            } catch (\Throwable $e) {
                                \Log::error('Failed to send payout completed notification', ['error' => $e->getMessage()]);
                            }

                            Notification::make()
                                ->success()
                                ->title(__('filament.notifications.payout_completed'))
                                ->body(__('filament.notifications.payout_completed_body'))
                                ->send();
                        }),

                    Tables\Actions\Action::make('fail')
                        ->label(__('filament.actions.fail'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->required()
                                ->label(__('filament.labels.failure_reason'))
                                ->rows(3),
                        ])
                        ->visible(fn (Payout $record) => in_array($record->status, [PayoutStatus::PENDING, PayoutStatus::PROCESSING], true))
                        ->action(function (Payout $record, array $data) {
                            $record->markAsFailed($data['reason']);

                            // Notify vendor
                            try {
                                $vendor = $record->vendor;
                                if ($vendor) {
                                    $vendor->notifyNow(
                                        Notification::make()
                                            ->title('Payout Failed')
                                            ->icon('heroicon-o-banknotes')
                                            ->body("Your payout of {$record->amount} {$record->currency} has failed. Reason: {$data['reason']}")
                                            ->danger()
                                            ->toDatabase()
                                    );
                                }
                            } catch (\Throwable $e) {
                                \Log::error('Failed to send payout failed notification', ['error' => $e->getMessage()]);
                            }

                            Notification::make()
                                ->danger()
                                ->title(__('filament.notifications.payout_failed'))
                                ->body(__('filament.notifications.payout_failed_body'))
                                ->send();
                        }),

                    Tables\Actions\EditAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayouts::route('/'),
            'create' => Pages\CreatePayout::route('/create'),
            'edit' => Pages\EditPayout::route('/{record}/edit'),
        ];
    }
}
