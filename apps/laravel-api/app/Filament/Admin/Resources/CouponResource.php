<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\DiscountType;
use App\Filament\Admin\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('code', strtoupper($state)))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Discount Settings')
                    ->schema([
                        Forms\Components\Select::make('discount_type')
                            ->options([
                                DiscountType::PERCENTAGE->value => 'Percentage',
                                DiscountType::FIXED_AMOUNT->value => 'Fixed Amount',
                            ])
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('discount_value')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix(fn (Forms\Get $get) => $get('discount_type') === DiscountType::PERCENTAGE->value ? '%' : 'CAD'),

                        Forms\Components\TextInput::make('minimum_order')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('CAD')
                            ->helperText('Minimum order amount required to use this coupon'),

                        Forms\Components\TextInput::make('maximum_discount')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('CAD')
                            ->helperText('Maximum discount amount (for percentage discounts)')
                            ->visible(fn (Forms\Get $get) => $get('discount_type') === DiscountType::PERCENTAGE->value),
                    ])->columns(2),

                Forms\Components\Section::make('Validity & Usage')
                    ->schema([
                        Forms\Components\DateTimePicker::make('valid_from')
                            ->required()
                            ->default(now()),

                        Forms\Components\DateTimePicker::make('valid_until')
                            ->required()
                            ->after('valid_from'),

                        Forms\Components\TextInput::make('usage_limit')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave empty for unlimited uses'),

                        Forms\Components\TextInput::make('usage_count')
                            ->numeric()
                            ->disabled()
                            ->default(0)
                            ->helperText('Number of times this coupon has been used'),
                    ])->columns(2),

                Forms\Components\Section::make('Restrictions')
                    ->schema([
                        Forms\Components\TagsInput::make('listing_ids')
                            ->helperText('Leave empty to apply to all listings. Enter listing UUIDs to restrict.')
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('user_ids')
                            ->helperText('Leave empty to apply to all users. Enter user UUIDs to restrict.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('discount_type')
                    ->badge()
                    ->formatStateUsing(fn (DiscountType $state) => $state->label()),

                Tables\Columns\TextColumn::make('discount_value')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->discount_type === DiscountType::PERCENTAGE
                            ? $state . '%'
                            : '$' . number_format($state, 2);
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('usage_count')
                    ->label('Used')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $record->usage_limit
                        ? "{$state} / {$record->usage_limit}"
                        : $state),

                Tables\Columns\TextColumn::make('valid_from')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('valid_until')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),

                Tables\Filters\SelectFilter::make('discount_type')
                    ->options([
                        DiscountType::PERCENTAGE->value => 'Percentage',
                        DiscountType::FIXED_AMOUNT->value => 'Fixed Amount',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
