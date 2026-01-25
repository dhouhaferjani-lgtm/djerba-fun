<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\EmailLogStatus;
use App\Enums\EmailType;
use App\Filament\Admin\Resources\EmailLogResource\Pages;
use App\Models\EmailLog;
use App\Models\User;
use App\Services\EmailLogService;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmailLogResource extends Resource
{
    protected static ?string $model = EmailLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.system', default: 'System');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.email_logs', default: 'Email Logs');
    }

    public static function getModelLabel(): string
    {
        return 'Email';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Emails';
    }

    /**
     * Admin sees ALL emails (no vendor scoping).
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['booking', 'listing', 'vendor']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return true; // Admin can delete logs
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Recipient Information')
                    ->description('Contact details of the traveler')
                    ->schema([
                        Infolists\Components\TextEntry::make('recipient_email')
                            ->label('Email')
                            ->copyable()
                            ->icon('heroicon-o-envelope'),
                        Infolists\Components\TextEntry::make('recipient_name')
                            ->label('Name')
                            ->placeholder('Not provided')
                            ->icon('heroicon-o-user'),
                        Infolists\Components\TextEntry::make('recipient_phone')
                            ->label('Phone')
                            ->placeholder('Not provided')
                            ->copyable()
                            ->icon('heroicon-o-phone'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Email Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('email_type')
                            ->label('Type')
                            ->badge()
                            ->color(fn (EmailType $state) => $state->color())
                            ->formatStateUsing(fn (EmailType $state) => $state->label()),
                        Infolists\Components\TextEntry::make('subject')
                            ->label('Subject'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (EmailLogStatus $state) => $state->color())
                            ->formatStateUsing(fn (EmailLogStatus $state) => $state->label()),
                        Infolists\Components\TextEntry::make('booking.booking_number')
                            ->label('Booking')
                            ->url(
                                fn ($record) => $record->booking_id
                                ? route('filament.admin.resources.bookings.view', ['record' => $record->booking_id])
                                : null
                            )
                            ->placeholder('N/A'),
                        Infolists\Components\TextEntry::make('vendor.display_name')
                            ->label('Vendor')
                            ->placeholder('N/A'),
                        Infolists\Components\TextEntry::make('mailgun_message_id')
                            ->label('Mailgun ID')
                            ->placeholder('N/A')
                            ->copyable(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Delivery Timeline')
                    ->schema([
                        Infolists\Components\TextEntry::make('queued_at')
                            ->label('Queued')
                            ->dateTime('M d, Y H:i:s')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('sent_at')
                            ->label('Sent')
                            ->dateTime('M d, Y H:i:s')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('delivered_at')
                            ->label('Delivered')
                            ->dateTime('M d, Y H:i:s')
                            ->placeholder('Not yet'),
                        Infolists\Components\TextEntry::make('opened_at')
                            ->label('Opened')
                            ->dateTime('M d, Y H:i:s')
                            ->placeholder('Not yet'),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Error Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('error_message')
                            ->label('Error Details')
                            ->columnSpanFull()
                            ->color('danger'),
                        Infolists\Components\TextEntry::make('bounced_at')
                            ->label('Bounced At')
                            ->dateTime('M d, Y H:i:s')
                            ->visible(fn ($record) => $record->bounced_at !== null),
                        Infolists\Components\TextEntry::make('failed_at')
                            ->label('Failed At')
                            ->dateTime('M d, Y H:i:s')
                            ->visible(fn ($record) => $record->failed_at !== null),
                    ])
                    ->visible(fn ($record) => $record->error_message !== null)
                    ->icon('heroicon-o-exclamation-triangle')
                    ->iconColor('danger'),

                Infolists\Components\Section::make('Email Content')
                    ->schema([
                        Infolists\Components\ViewEntry::make('html_content')
                            ->label('')
                            ->view('filament.infolists.components.email-preview')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('recipient_email')
                    ->label('Recipient')
                    ->description(fn ($record) => $record->recipient_name ?: null)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (EmailType $state) => $state->color())
                    ->formatStateUsing(fn (EmailType $state) => $state->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (EmailLogStatus $state) => $state->color())
                    ->formatStateUsing(fn (EmailLogStatus $state) => $state->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('vendor.display_name')
                    ->label('Vendor')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('booking.booking_number')
                    ->label('Booking')
                    ->url(
                        fn ($record) => $record->booking_id
                        ? route('filament.admin.resources.bookings.view', ['record' => $record->booking_id])
                        : null
                    )
                    ->placeholder('N/A')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->placeholder('Pending'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(EmailLogStatus::cases())->mapWithKeys(
                        fn ($status) => [$status->value => $status->label()]
                    )),

                Tables\Filters\SelectFilter::make('email_type')
                    ->label('Type')
                    ->options(collect(EmailType::cases())->mapWithKeys(
                        fn ($type) => [$type->value => $type->label()]
                    )),

                Tables\Filters\SelectFilter::make('vendor_id')
                    ->label('Vendor')
                    ->options(
                        fn () => User::query()
                            ->where('role', 'vendor')
                            ->orderBy('display_name')
                            ->pluck('display_name', 'id')
                    )
                    ->searchable(),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),

                Tables\Filters\Filter::make('failed_only')
                    ->label('Failed/Bounced Only')
                    ->query(fn (Builder $query) => $query->whereIn('status', [
                        EmailLogStatus::FAILED->value,
                        EmailLogStatus::BOUNCED->value,
                    ]))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('resend')
                    ->label('Resend')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Resend Email')
                    ->modalDescription('This will queue a new email to the same recipient. The original failed email will remain in the log.')
                    ->modalSubmitActionLabel('Resend Email')
                    ->action(function (EmailLog $record) {
                        try {
                            $service = app(EmailLogService::class);
                            $service->resend($record);

                            Notification::make()
                                ->success()
                                ->title('Email Queued')
                                ->body('A new email has been queued for delivery.')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Resend Failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    })
                    ->visible(fn (EmailLog $record) => $record->canBeResent()),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->emptyStateHeading('No emails logged yet')
            ->emptyStateDescription('Emails sent through the system will appear here.')
            ->emptyStateIcon('heroicon-o-envelope');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailLogs::route('/'),
            'view' => Pages\ViewEmailLog::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->whereIn('status', [
                EmailLogStatus::FAILED->value,
                EmailLogStatus::BOUNCED->value,
            ])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
