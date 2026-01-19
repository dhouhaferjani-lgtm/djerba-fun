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
                Forms\Components\Section::make(__('filament.custom_trip.status'))
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label(__('filament.custom_trip.status'))
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
                    ->label(__('filament.custom_trip.reference'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('contact_name')
                    ->label(__('filament.custom_trip.traveler_name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact_email')
                    ->label(__('filament.custom_trip.email'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('travel_dates')
                    ->label(__('filament.custom_trip.travel_dates'))
                    ->getStateUsing(fn (CustomTripRequest $record): string => $record->travel_start_date->format('M d') . ' - ' . $record->travel_end_date->format('M d, Y'))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('travel_start_date', $direction);
                    }),

                Tables\Columns\TextColumn::make('budget_display')
                    ->label(__('filament.custom_trip.budget'))
                    ->getStateUsing(fn (CustomTripRequest $record): string => number_format($record->budget_per_person) . ' ' . $record->budget_currency . '/person')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('budget_per_person', $direction);
                    }),

                Tables\Columns\TextColumn::make('total_travelers')
                    ->label(__('filament.custom_trip.travelers'))
                    ->getStateUsing(fn (CustomTripRequest $record): string => $record->adults . ' ' . __('filament.custom_trip.adults') . ($record->children > 0 ? ', ' . $record->children . ' ' . __('filament.custom_trip.children') : ''))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament.custom_trip.status'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.custom_trip.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament.custom_trip.status'))
                    ->options(CustomTripRequestStatus::class)
                    ->multiple(),

                Tables\Filters\Filter::make('travel_dates')
                    ->label(__('filament.custom_trip.travel_dates'))
                    ->form([
                        Forms\Components\DatePicker::make('travel_from')
                            ->label(__('filament.labels.from')),
                        Forms\Components\DatePicker::make('travel_until')
                            ->label(__('filament.labels.until')),
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
                    ->label(__('filament.custom_trip.created_at'))
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label(__('filament.labels.from')),
                        Forms\Components\DatePicker::make('created_until')
                            ->label(__('filament.labels.until')),
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
                    ->label(__('filament.custom_trip.mark_contacted'))
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
                Infolists\Components\Section::make(__('filament.custom_trip.request_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('reference')
                            ->label(__('filament.custom_trip.reference'))
                            ->copyable()
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('status')
                            ->label(__('filament.custom_trip.status'))
                            ->badge(),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('filament.custom_trip.submitted'))
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('locale')
                            ->label(__('filament.custom_trip.language'))
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'en' => __('filament.custom_trip.lang_en'),
                                'fr' => __('filament.custom_trip.lang_fr'),
                                default => $state,
                            }),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make(__('filament.custom_trip.contact_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('contact_name')
                            ->label(__('filament.custom_trip.name')),

                        Infolists\Components\TextEntry::make('contact_email')
                            ->label(__('filament.custom_trip.email'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('contact_phone')
                            ->label(__('filament.custom_trip.phone'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('contact_whatsapp')
                            ->label(__('filament.custom_trip.whatsapp'))
                            ->copyable()
                            ->placeholder(__('filament.custom_trip.not_provided')),

                        Infolists\Components\TextEntry::make('contact_country')
                            ->label(__('filament.custom_trip.country')),

                        Infolists\Components\TextEntry::make('preferred_contact_method')
                            ->label(__('filament.custom_trip.preferred_contact'))
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make(__('filament.custom_trip.trip_details'))
                    ->schema([
                        Infolists\Components\TextEntry::make('travel_dates')
                            ->label(__('filament.custom_trip.travel_dates'))
                            ->getStateUsing(fn (CustomTripRequest $record): string => $record->travel_start_date->format('F d, Y') . ' to ' . $record->travel_end_date->format('F d, Y')),

                        Infolists\Components\TextEntry::make('duration_days')
                            ->label(__('filament.custom_trip.duration'))
                            ->formatStateUsing(fn (int $state): string => __('filament.custom_trip.days', ['count' => $state])),

                        Infolists\Components\TextEntry::make('dates_flexible')
                            ->label(__('filament.custom_trip.flexible_dates'))
                            ->formatStateUsing(fn (bool $state): string => $state ? __('filament.custom_trip.yes') : __('filament.custom_trip.no'))
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make(__('filament.custom_trip.travelers'))
                    ->schema([
                        Infolists\Components\TextEntry::make('adults')
                            ->label(__('filament.custom_trip.adults')),

                        Infolists\Components\TextEntry::make('children')
                            ->label(__('filament.custom_trip.children')),

                        Infolists\Components\TextEntry::make('total_travelers')
                            ->label(__('filament.custom_trip.total_travelers')),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make(__('filament.custom_trip.interests'))
                    ->schema([
                        Infolists\Components\TextEntry::make('interests')
                            ->label(__('filament.custom_trip.selected_interests'))
                            ->badge()
                            ->separator(',')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'history-culture' => __('filament.custom_trip.interest_history_culture'),
                                'desert-adventures' => __('filament.custom_trip.interest_desert_adventures'),
                                'beach-relaxation' => __('filament.custom_trip.interest_beach_relaxation'),
                                'food-gastronomy' => __('filament.custom_trip.interest_food_gastronomy'),
                                'hiking-nature' => __('filament.custom_trip.interest_hiking_nature'),
                                'photography' => __('filament.custom_trip.interest_photography'),
                                'local-festivals' => __('filament.custom_trip.interest_local_festivals'),
                                'star-wars-sites' => __('filament.custom_trip.interest_star_wars_sites'),
                                default => ucfirst(str_replace('-', ' ', $state)),
                            }),
                    ]),

                Infolists\Components\Section::make(__('filament.custom_trip.budget_style'))
                    ->schema([
                        Infolists\Components\TextEntry::make('budget_per_person')
                            ->label(__('filament.custom_trip.budget_per_person'))
                            ->formatStateUsing(fn (int $state, CustomTripRequest $record): string => number_format($state) . ' ' . $record->budget_currency),

                        Infolists\Components\TextEntry::make('estimated_total_budget')
                            ->label(__('filament.custom_trip.estimated_total'))
                            ->formatStateUsing(fn (int $state, CustomTripRequest $record): string => number_format($state) . ' ' . $record->budget_currency),

                        Infolists\Components\TextEntry::make('accommodation_style')
                            ->label(__('filament.custom_trip.accommodation_style'))
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'budget' => __('filament.custom_trip.style_budget'),
                                'mid-range' => __('filament.custom_trip.style_mid_range'),
                                'luxury' => __('filament.custom_trip.style_luxury'),
                                default => $state ?? __('filament.custom_trip.style_not_specified'),
                            })
                            ->badge(),

                        Infolists\Components\TextEntry::make('travel_pace')
                            ->label(__('filament.custom_trip.travel_pace'))
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'relaxed' => __('filament.custom_trip.pace_relaxed'),
                                'moderate' => __('filament.custom_trip.pace_moderate'),
                                'active' => __('filament.custom_trip.pace_active'),
                                default => $state ?? __('filament.custom_trip.pace_not_specified'),
                            })
                            ->badge(),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make(__('filament.custom_trip.special_requests'))
                    ->schema([
                        Infolists\Components\TextEntry::make('special_requests')
                            ->label(__('filament.custom_trip.notes'))
                            ->columnSpanFull()
                            ->placeholder(__('filament.custom_trip.no_special_requests')),
                    ])
                    ->visible(fn (CustomTripRequest $record): bool => ! empty($record->special_requests)),

                Infolists\Components\Section::make(__('filament.custom_trip.special_occasions'))
                    ->schema([
                        Infolists\Components\TextEntry::make('special_occasions')
                            ->label(__('filament.custom_trip.occasions'))
                            ->badge()
                            ->separator(',')
                            ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('-', ' ', $state))),
                    ])
                    ->visible(fn (CustomTripRequest $record): bool => ! empty($record->special_occasions)),

                Infolists\Components\Section::make(__('filament.custom_trip.metadata'))
                    ->schema([
                        Infolists\Components\TextEntry::make('ip_address')
                            ->label(__('filament.custom_trip.ip_address'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('user_agent')
                            ->label(__('filament.custom_trip.user_agent'))
                            ->limit(50),

                        Infolists\Components\TextEntry::make('newsletter_consent')
                            ->label(__('filament.custom_trip.newsletter_consent'))
                            ->formatStateUsing(fn (bool $state): string => $state ? __('filament.custom_trip.yes') : __('filament.custom_trip.no'))
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),

                        Infolists\Components\TextEntry::make('assignedAgent.display_name')
                            ->label(__('filament.custom_trip.assigned_agent'))
                            ->placeholder(__('filament.custom_trip.not_assigned')),
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
