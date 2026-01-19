<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TravelTipResource\Pages;
use App\Models\TravelTip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TravelTipResource extends Resource
{
    protected static ?string $model = TravelTip::class;

    protected static ?string $navigationIcon = null;

    protected static ?int $navigationSort = 50;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.travel_tips');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.travel_tip.tip_content'))
                    ->schema([
                        Forms\Components\Tabs::make(__('filament.travel_tip.translations'))
                            ->tabs([
                                Forms\Components\Tabs\Tab::make(__('filament.travel_tip.english'))
                                    ->schema([
                                        Forms\Components\Textarea::make('content.en')
                                            ->label(__('filament.travel_tip.content_english'))
                                            ->required()
                                            ->rows(3)
                                            ->maxLength(500),
                                    ]),
                                Forms\Components\Tabs\Tab::make(__('filament.travel_tip.french'))
                                    ->schema([
                                        Forms\Components\Textarea::make('content.fr')
                                            ->label(__('filament.travel_tip.content_french'))
                                            ->required()
                                            ->rows(3)
                                            ->maxLength(500),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make(__('filament.travel_tip.settings'))
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('filament.travel_tip.active'))
                            ->default(true)
                            ->helperText(__('filament.travel_tip.active_helper')),

                        Forms\Components\TextInput::make('display_order')
                            ->label(__('filament.travel_tip.display_order'))
                            ->numeric()
                            ->default(0)
                            ->helperText(__('filament.travel_tip.display_order_helper')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('filament.travel_tip.id'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('content')
                    ->label(__('filament.travel_tip.content_en'))
                    ->getStateUsing(fn ($record) => $record->getTranslation('content', 'en'))
                    ->limit(60)
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('filament.travel_tip.active'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_order')
                    ->label(__('filament.travel_tip.order'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament.travel_tip.updated'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('filament.travel_tip.active')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('display_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTravelTips::route('/'),
            'create' => Pages\CreateTravelTip::route('/create'),
            'edit' => Pages\EditTravelTip::route('/{record}/edit'),
        ];
    }
}
