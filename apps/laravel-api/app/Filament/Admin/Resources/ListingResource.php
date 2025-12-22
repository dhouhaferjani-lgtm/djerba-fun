<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Filament\Admin\Resources\ListingResource\Pages;
use App\Models\Listing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;

    protected static ?string $navigationIcon = null;

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Listing Information')
                    ->schema([
                        Forms\Components\TextInput::make('title.en')
                            ->label('Title (English)')
                            ->disabled(),

                        Forms\Components\TextInput::make('title.fr')
                            ->label('Title (French)')
                            ->disabled(),

                        Forms\Components\Select::make('service_type')
                            ->options(ServiceType::class)
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->options([
                                ListingStatus::DRAFT->value => ListingStatus::DRAFT->label(),
                                ListingStatus::PENDING_REVIEW->value => ListingStatus::PENDING_REVIEW->label(),
                                ListingStatus::PUBLISHED->value => ListingStatus::PUBLISHED->label(),
                                ListingStatus::ARCHIVED->value => ListingStatus::ARCHIVED->label(),
                                ListingStatus::REJECTED->value => ListingStatus::REJECTED->label(),
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('slug')
                            ->disabled(),

                        Forms\Components\Select::make('vendor_id')
                            ->relationship('vendor', 'display_name')
                            ->disabled()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Location')
                    ->schema([
                        Forms\Components\Select::make('location_id')
                            ->relationship(
                                name: 'location',
                                modifyQueryUsing: fn (Builder $query) => $query->orderBy('slug'),
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', app()->getLocale()))
                            ->searchable(['slug'])
                            ->preload(),
                    ]),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('summary.en')
                            ->label('Summary (English)')
                            ->disabled()
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('description.en')
                            ->label('Description (English)')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Pricing & Capacity')
                    ->schema([
                        Forms\Components\TextInput::make('pricing.base')
                            ->label('Base Price (cents)')
                            ->disabled(),

                        Forms\Components\TextInput::make('pricing.currency')
                            ->label('Currency')
                            ->disabled(),

                        Forms\Components\TextInput::make('min_group_size')
                            ->disabled(),

                        Forms\Components\TextInput::make('max_group_size')
                            ->disabled(),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Moderation')
                    ->schema([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->helperText('If rejecting, provide a reason for the vendor.')
                            ->rows(3)
                            ->visible(fn ($record) => $record?->status === ListingStatus::REJECTED)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->formatStateUsing(fn ($record) => $record->getTranslation('title', app()->getLocale()))
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->getTranslation('title', app()->getLocale()))
                    ->searchable(false),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('vendor.display_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('service_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (ServiceType $state): string => match ($state) {
                        ServiceType::TOUR => 'info',
                        ServiceType::EVENT => 'success',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (ListingStatus $state): string => $state->color()),

                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->formatStateUsing(fn ($record) => $record->location?->getTranslation('name', app()->getLocale()))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('pricing.base')
                    ->label('Price')
                    ->formatStateUsing(
                        fn ($state, $record) => $state ? number_format((float) $state / 100, 2) . ' ' . ($record->pricing['currency'] ?? 'EUR') : '-'
                    )
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 1) : '-')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        ListingStatus::DRAFT->value => ListingStatus::DRAFT->label(),
                        ListingStatus::PENDING_REVIEW->value => ListingStatus::PENDING_REVIEW->label(),
                        ListingStatus::PUBLISHED->value => ListingStatus::PUBLISHED->label(),
                        ListingStatus::ARCHIVED->value => ListingStatus::ARCHIVED->label(),
                        ListingStatus::REJECTED->value => ListingStatus::REJECTED->label(),
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('service_type')
                    ->options([
                        ServiceType::TOUR->value => 'Tours',
                        ServiceType::EVENT->value => 'Events',
                    ]),

                Tables\Filters\Filter::make('pending_review')
                    ->label('Pending Review')
                    ->query(fn (Builder $query): Builder => $query->where('status', ListingStatus::PENDING_REVIEW))
                    ->toggle(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label('Approve & Publish')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Listing')
                        ->modalDescription('This will publish the listing and make it visible to travelers.')
                        ->action(function (Listing $record) {
                            $record->update([
                                'status' => ListingStatus::PUBLISHED,
                                'published_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Listing Approved')
                                ->body('The listing has been published.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Listing $record) => $record->status === ListingStatus::PENDING_REVIEW),

                    Tables\Actions\Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Rejection Reason')
                                ->required()
                                ->rows(3)
                                ->helperText('This will be shared with the vendor.'),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Reject Listing')
                        ->modalDescription('The vendor will be notified of the rejection.')
                        ->action(function (Listing $record, array $data) {
                            $record->update([
                                'status' => ListingStatus::REJECTED,
                            ]);

                            // TODO: Send notification to vendor with rejection reason

                            Notification::make()
                                ->title('Listing Rejected')
                                ->body('The listing has been rejected.')
                                ->warning()
                                ->send();
                        })
                        ->visible(fn (Listing $record) => $record->status === ListingStatus::PENDING_REVIEW),

                    Tables\Actions\Action::make('archive')
                        ->label('Archive')
                        ->icon('heroicon-o-archive-box')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (Listing $record) {
                            $record->archive();

                            Notification::make()
                                ->title('Listing Archived')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Listing $record) => $record->status === ListingStatus::PUBLISHED),

                    Tables\Actions\Action::make('republish')
                        ->label('Re-publish')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Listing $record) {
                            $record->publish();

                            Notification::make()
                                ->title('Listing Re-published')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Listing $record) => $record->status === ListingStatus::ARCHIVED),

                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),

                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;

                            foreach ($records as $record) {
                                if ($record->status === ListingStatus::PENDING_REVIEW) {
                                    $record->publish();
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("$count listings approved")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_archive')
                        ->label('Archive Selected')
                        ->icon('heroicon-o-archive-box')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;

                            foreach ($records as $record) {
                                if ($record->status === ListingStatus::PUBLISHED) {
                                    $record->archive();
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("$count listings archived")
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
            'index' => Pages\ListListings::route('/'),
            'create' => Pages\CreateListing::route('/create'),
            'view' => Pages\ViewListing::route('/{record}'),
            'edit' => Pages\EditListing::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', ListingStatus::PENDING_REVIEW)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pending review';
    }
}
