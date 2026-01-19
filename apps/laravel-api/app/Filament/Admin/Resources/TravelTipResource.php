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
                Forms\Components\Section::make('Tip Content')
                    ->schema([
                        Forms\Components\Tabs::make('Translations')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('English')
                                    ->schema([
                                        Forms\Components\Textarea::make('content.en')
                                            ->label('Content (English)')
                                            ->required()
                                            ->rows(3)
                                            ->maxLength(500),
                                    ]),
                                Forms\Components\Tabs\Tab::make('French')
                                    ->schema([
                                        Forms\Components\Textarea::make('content.fr')
                                            ->label('Content (French)')
                                            ->required()
                                            ->rows(3)
                                            ->maxLength(500),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Only active tips will be shown on the website'),

                        Forms\Components\TextInput::make('display_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('content')
                    ->label('Content (EN)')
                    ->getStateUsing(fn ($record) => $record->getTranslation('content', 'en'))
                    ->limit(60)
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
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
