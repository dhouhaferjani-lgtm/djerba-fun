<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\CustomTripRequestStatus;
use App\Filament\Admin\Resources\CustomTripRequestResource\Pages;
use App\Models\CustomTripRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomTripRequestResource extends Resource
{
    protected static ?string $model = CustomTripRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.custom_trip_requests');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', CustomTripRequestStatus::PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options(CustomTripRequestStatus::class)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Traveler Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact_email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('travel_dates')
                    ->label('Travel Dates')
                    ->getStateUsing(fn (CustomTripRequest $record): string => $record->travel_start_date->format('M d') . ' - ' . $record->travel_end_date->format('M d, Y'))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('travel_start_date', $direction);
                    }),

                Tables\Columns\TextColumn::make('budget_display')
                    ->label('Budget')
                    ->getStateUsing(fn (CustomTripRequest $record): string => number_format($record->budget_per_person) . ' ' . $record->budget_currency . '/person')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('budget_per_person', $direction);
                    }),

                Tables\Columns\TextColumn::make('total_travelers')
                    ->label('Travelers')
                    ->getStateUsing(fn (CustomTripRequest $record): string => $record->adults . ' adults' . ($record->children > 0 ? ', ' . $record->children . ' children' : ''))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(CustomTripRequestStatus::class)
                    ->multiple(),

                Tables\Filters\Filter::make('travel_dates')
                    ->form([
                        Forms\Components\DatePicker::make('travel_from')
                            ->label('Travel From'),
                        Forms\Components\DatePicker::make('travel_until')
                            ->label('Travel Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['travel_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('travel_start_date', '>=', $date),
                            )
                            ->when(
                                $data['travel_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('travel_end_date', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('mark_contacted')
                    ->label('Mark Contacted')
                    ->icon('heroicon-o-phone')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(fn (CustomTripRequest $record) => $record->markAsContacted())
                    ->visible(fn (CustomTripRequest $record) => $record->status === CustomTripRequestStatus::PENDING),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Request Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference')
                            ->label('Reference')
                            ->copyable()
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('status')
                            ->badge(),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('locale')
                            ->label('Language')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'en' => 'English',
                                'fr' => 'French',
                                default => $state,
                            }),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Contact Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('contact_name')
                            ->label('Name'),

                        Infolists\Components\TextEntry::make('contact_email')
                            ->label('Email')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('contact_phone')
                            ->label('Phone')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('contact_whatsapp')
                            ->label('WhatsApp')
                            ->copyable()
                            ->placeholder('Not provided'),

                        Infolists\Components\TextEntry::make('contact_country')
                            ->label('Country'),

                        Infolists\Components\TextEntry::make('preferred_contact_method')
                            ->label('Preferred Contact')
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Trip Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('travel_dates')
                            ->label('Travel Dates')
                            ->getStateUsing(fn (CustomTripRequest $record): string => $record->travel_start_date->format('F d, Y') . ' to ' . $record->travel_end_date->format('F d, Y')),

                        Infolists\Components\TextEntry::make('duration_days')
                            ->label('Duration')
                            ->formatStateUsing(fn (int $state): string => $state . ' days'),

                        Infolists\Components\TextEntry::make('dates_flexible')
                            ->label('Flexible Dates')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Travelers')
                    ->schema([
                        Infolists\Components\TextEntry::make('adults')
                            ->label('Adults'),

                        Infolists\Components\TextEntry::make('children')
                            ->label('Children'),

                        Infolists\Components\TextEntry::make('total_travelers')
                            ->label('Total Travelers'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Interests')
                    ->schema([
                        Infolists\Components\TextEntry::make('interests')
                            ->label('Selected Interests')
                            ->badge()
                            ->separator(',')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'history-culture' => 'History & Culture',
                                'desert-adventures' => 'Desert Adventures',
                                'beach-relaxation' => 'Beach & Relaxation',
                                'food-gastronomy' => 'Food & Gastronomy',
                                'hiking-nature' => 'Hiking & Nature',
                                'photography' => 'Photography',
                                'local-festivals' => 'Local Festivals',
                                'star-wars-sites' => 'Star Wars Sites',
                                default => ucfirst(str_replace('-', ' ', $state)),
                            }),
                    ]),

                Infolists\Components\Section::make('Budget & Style')
                    ->schema([
                        Infolists\Components\TextEntry::make('budget_per_person')
                            ->label('Budget per Person')
                            ->formatStateUsing(fn (int $state, CustomTripRequest $record): string => number_format($state) . ' ' . $record->budget_currency),

                        Infolists\Components\TextEntry::make('estimated_total_budget')
                            ->label('Estimated Total Budget')
                            ->formatStateUsing(fn (int $state, CustomTripRequest $record): string => number_format($state) . ' ' . $record->budget_currency),

                        Infolists\Components\TextEntry::make('accommodation_style')
                            ->label('Accommodation Style')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'budget' => 'Budget',
                                'mid-range' => 'Mid-Range',
                                'luxury' => 'Luxury',
                                default => $state ?? 'Not specified',
                            })
                            ->badge(),

                        Infolists\Components\TextEntry::make('travel_pace')
                            ->label('Travel Pace')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'relaxed' => 'Relaxed',
                                'moderate' => 'Moderate',
                                'active' => 'Active',
                                default => $state ?? 'Not specified',
                            })
                            ->badge(),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Special Requests')
                    ->schema([
                        Infolists\Components\TextEntry::make('special_requests')
                            ->label('Notes')
                            ->columnSpanFull()
                            ->placeholder('No special requests'),
                    ])
                    ->visible(fn (CustomTripRequest $record): bool => ! empty($record->special_requests)),

                Infolists\Components\Section::make('Special Occasions')
                    ->schema([
                        Infolists\Components\TextEntry::make('special_occasions')
                            ->label('Occasions')
                            ->badge()
                            ->separator(',')
                            ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('-', ' ', $state))),
                    ])
                    ->visible(fn (CustomTripRequest $record): bool => ! empty($record->special_occasions)),

                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\TextEntry::make('ip_address')
                            ->label('IP Address')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->limit(50),

                        Infolists\Components\TextEntry::make('newsletter_consent')
                            ->label('Newsletter Consent')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),

                        Infolists\Components\TextEntry::make('assignedAgent.display_name')
                            ->label('Assigned Agent')
                            ->placeholder('Not assigned'),
                    ])
                    ->columns(4)
                    ->collapsed(),
            ]);
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
            'index' => Pages\ListCustomTripRequests::route('/'),
            'view' => Pages\ViewCustomTripRequest::route('/{record}'),
        ];
    }
}
