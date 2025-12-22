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

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payout Information')
                    ->schema([
                        Forms\Components\Select::make('vendor_id')
                            ->relationship('vendor', 'display_name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('status')
                            ->options([
                                PayoutStatus::PENDING->value => 'Pending',
                                PayoutStatus::PROCESSING->value => 'Processing',
                                PayoutStatus::COMPLETED->value => 'Completed',
                                PayoutStatus::FAILED->value => 'Failed',
                            ])
                            ->required()
                            ->default(PayoutStatus::PENDING->value),

                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0),

                        Forms\Components\TextInput::make('currency')
                            ->required()
                            ->default('CAD')
                            ->maxLength(3),

                        Forms\Components\Select::make('payout_method')
                            ->options([
                                PayoutMethod::BANK_TRANSFER->value => 'Bank Transfer',
                                PayoutMethod::PAYPAL->value => 'PayPal',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('reference')
                            ->maxLength(255)
                            ->helperText('Transaction reference number'),
                    ])->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('processed_at')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vendor.display_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money('CAD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (PayoutStatus $state) => $state->label())
                    ->color(fn (PayoutStatus $state) => $state->color()),

                Tables\Columns\TextColumn::make('payout_method')
                    ->formatStateUsing(fn (PayoutMethod $state) => $state->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('processed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        PayoutStatus::PENDING->value => 'Pending',
                        PayoutStatus::PROCESSING->value => 'Processing',
                        PayoutStatus::COMPLETED->value => 'Completed',
                        PayoutStatus::FAILED->value => 'Failed',
                    ]),

                Tables\Filters\SelectFilter::make('payout_method')
                    ->options([
                        PayoutMethod::BANK_TRANSFER->value => 'Bank Transfer',
                        PayoutMethod::PAYPAL->value => 'PayPal',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Payout $record) => $record->status === PayoutStatus::PENDING)
                        ->action(function (Payout $record) {
                            $record->markAsProcessing();
                            Notification::make()
                                ->success()
                                ->title('Payout approved')
                                ->body('The payout is now being processed.')
                                ->send();
                        }),

                    Tables\Actions\Action::make('complete')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\TextInput::make('reference')
                                ->required()
                                ->label('Transaction Reference')
                                ->helperText('Enter the transaction reference number'),
                        ])
                        ->visible(fn (Payout $record) => $record->status === PayoutStatus::PROCESSING)
                        ->action(function (Payout $record, array $data) {
                            $record->markAsCompleted($data['reference']);
                            Notification::make()
                                ->success()
                                ->title('Payout completed')
                                ->body('The payout has been marked as completed.')
                                ->send();
                        }),

                    Tables\Actions\Action::make('fail')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->required()
                                ->label('Failure Reason')
                                ->rows(3),
                        ])
                        ->visible(fn (Payout $record) => in_array($record->status, [PayoutStatus::PENDING, PayoutStatus::PROCESSING], true))
                        ->action(function (Payout $record, array $data) {
                            $record->markAsFailed($data['reason']);
                            Notification::make()
                                ->danger()
                                ->title('Payout failed')
                                ->body('The payout has been marked as failed.')
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
