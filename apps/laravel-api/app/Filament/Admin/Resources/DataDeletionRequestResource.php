<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DataDeletionRequestResource\Pages;
use App\Models\DataDeletionRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DataDeletionRequestResource extends Resource
{
    protected static ?string $model = DataDeletionRequest::class;

    protected static ?string $navigationIcon = null;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.compliance');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.data_deletion_requests');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.data_deletion.request_details'))
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label(__('filament.data_deletion.email'))
                            ->email()
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label(__('filament.data_deletion.status'))
                            ->options([
                                DataDeletionRequest::STATUS_PENDING => __('filament.data_deletion.status_pending'),
                                DataDeletionRequest::STATUS_PROCESSING => __('filament.data_deletion.status_processing'),
                                DataDeletionRequest::STATUS_COMPLETED => __('filament.data_deletion.status_completed'),
                                DataDeletionRequest::STATUS_REJECTED => __('filament.data_deletion.status_rejected'),
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->label(__('filament.data_deletion.user_reason'))
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label(__('filament.data_deletion.admin_notes'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('filament.data_deletion.processing_information'))
                    ->schema([
                        Forms\Components\DateTimePicker::make('requested_at')
                            ->label(__('filament.data_deletion.requested_at'))
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('processed_at')
                            ->label(__('filament.data_deletion.processed_at'))
                            ->disabled(),
                        Forms\Components\Select::make('processed_by')
                            ->label(__('filament.data_deletion.processed_by'))
                            ->relationship('processedByUser', 'name')
                            ->disabled(),
                        Forms\Components\KeyValue::make('data_deleted')
                            ->label(__('filament.data_deletion.data_deleted'))
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label(__('filament.data_deletion.email'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('filament.data_deletion.user'))
                    ->searchable()
                    ->sortable()
                    ->placeholder(__('filament.data_deletion.guest')),
                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('filament.data_deletion.status'))
                    ->colors([
                        'warning' => DataDeletionRequest::STATUS_PENDING,
                        'info' => DataDeletionRequest::STATUS_PROCESSING,
                        'success' => DataDeletionRequest::STATUS_COMPLETED,
                        'danger' => DataDeletionRequest::STATUS_REJECTED,
                    ]),
                Tables\Columns\TextColumn::make('reason')
                    ->label(__('filament.data_deletion.reason'))
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->reason),
                Tables\Columns\TextColumn::make('requested_at')
                    ->label(__('filament.data_deletion.requested_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_at')
                    ->label(__('filament.data_deletion.processed_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('filament.data_deletion.not_processed')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament.data_deletion.status'))
                    ->options([
                        DataDeletionRequest::STATUS_PENDING => __('filament.data_deletion.status_pending'),
                        DataDeletionRequest::STATUS_PROCESSING => __('filament.data_deletion.status_processing'),
                        DataDeletionRequest::STATUS_COMPLETED => __('filament.data_deletion.status_completed'),
                        DataDeletionRequest::STATUS_REJECTED => __('filament.data_deletion.status_rejected'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('process')
                    ->label(__('filament.data_deletion.process'))
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->visible(fn ($record) => $record->status === DataDeletionRequest::STATUS_PENDING)
                    ->action(fn ($record) => $record->markAsProcessing()),
                Tables\Actions\Action::make('complete')
                    ->label(__('filament.data_deletion.complete'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === DataDeletionRequest::STATUS_PROCESSING)
                    ->requiresConfirmation()
                    ->modalHeading(__('filament.data_deletion.complete_heading'))
                    ->modalDescription(__('filament.data_deletion.complete_description'))
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label(__('filament.data_deletion.admin_notes'))
                            ->placeholder(__('filament.data_deletion.notes_placeholder')),
                    ])
                    ->action(function ($record, array $data) {
                        $record->markAsCompleted(
                            auth()->user(),
                            [],
                            $data['admin_notes'] ?? null
                        );
                    }),
                Tables\Actions\Action::make('reject')
                    ->label(__('filament.data_deletion.reject'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => in_array($record->status, [
                        DataDeletionRequest::STATUS_PENDING,
                        DataDeletionRequest::STATUS_PROCESSING,
                    ]))
                    ->requiresConfirmation()
                    ->modalHeading(__('filament.data_deletion.reject_heading'))
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label(__('filament.data_deletion.rejection_reason'))
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->markAsRejected(
                            auth()->user(),
                            $data['admin_notes']
                        );
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('requested_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDataDeletionRequests::route('/'),
            'view' => Pages\ViewDataDeletionRequest::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('status', DataDeletionRequest::STATUS_PENDING)->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
