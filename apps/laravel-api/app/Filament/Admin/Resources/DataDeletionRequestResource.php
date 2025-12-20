<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DataDeletionRequestResource\Pages;
use App\Models\DataDeletionRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DataDeletionRequestResource extends Resource
{
    protected static ?string $model = DataDeletionRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-trash';

    protected static ?string $navigationGroup = 'Compliance';

    protected static ?string $navigationLabel = 'Deletion Requests';

    protected static ?int $navigationSort = 101;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Details')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                DataDeletionRequest::STATUS_PENDING => 'Pending',
                                DataDeletionRequest::STATUS_PROCESSING => 'Processing',
                                DataDeletionRequest::STATUS_COMPLETED => 'Completed',
                                DataDeletionRequest::STATUS_REJECTED => 'Rejected',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->label('User\'s Reason')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Processing Information')
                    ->schema([
                        Forms\Components\DateTimePicker::make('requested_at')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('processed_at')
                            ->disabled(),
                        Forms\Components\Select::make('processed_by')
                            ->relationship('processedByUser', 'name')
                            ->disabled(),
                        Forms\Components\KeyValue::make('data_deleted')
                            ->label('Data Deleted Summary')
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
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Guest'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => DataDeletionRequest::STATUS_PENDING,
                        'info' => DataDeletionRequest::STATUS_PROCESSING,
                        'success' => DataDeletionRequest::STATUS_COMPLETED,
                        'danger' => DataDeletionRequest::STATUS_REJECTED,
                    ]),
                Tables\Columns\TextColumn::make('reason')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->reason),
                Tables\Columns\TextColumn::make('requested_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not processed'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        DataDeletionRequest::STATUS_PENDING => 'Pending',
                        DataDeletionRequest::STATUS_PROCESSING => 'Processing',
                        DataDeletionRequest::STATUS_COMPLETED => 'Completed',
                        DataDeletionRequest::STATUS_REJECTED => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('process')
                    ->label('Process')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->visible(fn ($record) => $record->status === DataDeletionRequest::STATUS_PENDING)
                    ->action(fn ($record) => $record->markAsProcessing()),
                Tables\Actions\Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === DataDeletionRequest::STATUS_PROCESSING)
                    ->requiresConfirmation()
                    ->modalHeading('Complete Deletion Request')
                    ->modalDescription('This will mark the request as completed. Make sure you have deleted all user data.')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Notes')
                            ->placeholder('Describe what data was deleted...'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->markAsCompleted(
                            auth()->user(),
                            [],
                            $data['admin_notes'] ?? null
                        );
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => in_array($record->status, [
                        DataDeletionRequest::STATUS_PENDING,
                        DataDeletionRequest::STATUS_PROCESSING,
                    ]))
                    ->requiresConfirmation()
                    ->modalHeading('Reject Deletion Request')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Reason for Rejection')
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
