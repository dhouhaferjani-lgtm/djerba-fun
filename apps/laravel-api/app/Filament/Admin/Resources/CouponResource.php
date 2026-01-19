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

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.coupons');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.sections.basic_information'))
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label(__('filament.labels.code'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('code', strtoupper($state)))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('name')
                            ->label(__('filament.labels.name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('filament.labels.active'))
                            ->default(true),

                        Forms\Components\Textarea::make('description')
                            ->label(__('filament.labels.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make(__('filament.sections.discount_settings'))
                    ->schema([
                        Forms\Components\Select::make('discount_type')
                            ->label(__('filament.labels.discount_type'))
                            ->options([
                                DiscountType::PERCENTAGE->value => __('filament.options.percentage'),
                                DiscountType::FIXED_AMOUNT->value => __('filament.options.fixed_amount'),
                            ])
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('discount_value')
                            ->label(__('filament.labels.discount_value'))
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix(fn (Forms\Get $get) => $get('discount_type') === DiscountType::PERCENTAGE->value ? '%' : 'CAD'),

                        Forms\Components\TextInput::make('minimum_order')
                            ->label(__('filament.labels.minimum_order'))
                            ->numeric()
                            ->minValue(0)
                            ->suffix('CAD')
                            ->helperText(__('filament.helpers.minimum_order_helper')),

                        Forms\Components\TextInput::make('maximum_discount')
                            ->label(__('filament.labels.maximum_discount'))
                            ->numeric()
                            ->minValue(0)
                            ->suffix('CAD')
                            ->helperText(__('filament.helpers.maximum_discount_helper'))
                            ->visible(fn (Forms\Get $get) => $get('discount_type') === DiscountType::PERCENTAGE->value),
                    ])->columns(2),

                Forms\Components\Section::make(__('filament.sections.validity_usage'))
                    ->schema([
                        Forms\Components\DateTimePicker::make('valid_from')
                            ->label(__('filament.labels.valid_from'))
                            ->required()
                            ->default(now()),

                        Forms\Components\DateTimePicker::make('valid_until')
                            ->label(__('filament.labels.valid_until'))
                            ->required()
                            ->after('valid_from'),

                        Forms\Components\TextInput::make('usage_limit')
                            ->label(__('filament.labels.usage_limit'))
                            ->numeric()
                            ->minValue(1)
                            ->helperText(__('filament.helpers.usage_limit_helper')),

                        Forms\Components\TextInput::make('usage_count')
                            ->label(__('filament.labels.usage_count'))
                            ->numeric()
                            ->disabled()
                            ->default(0)
                            ->helperText(__('filament.helpers.usage_count_helper')),
                    ])->columns(2),

                Forms\Components\Section::make(__('filament.sections.restrictions'))
                    ->schema([
                        Forms\Components\TagsInput::make('listing_ids')
                            ->label(__('filament.labels.listing_ids'))
                            ->helperText(__('filament.helpers.listing_ids_helper'))
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('user_ids')
                            ->label(__('filament.labels.user_ids'))
                            ->helperText(__('filament.helpers.user_ids_helper'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('filament.labels.code'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament.labels.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('discount_type')
                    ->label(__('filament.labels.discount_type'))
                    ->badge()
                    ->formatStateUsing(fn (DiscountType $state) => $state->label()),

                Tables\Columns\TextColumn::make('discount_value')
                    ->label(__('filament.labels.discount_value'))
                    ->formatStateUsing(function ($state, $record) {
                        return $record->discount_type === DiscountType::PERCENTAGE
                            ? $state . '%'
                            : '$' . number_format((float) $state, 2);
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('filament.labels.active'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('usage_count')
                    ->label(__('filament.labels.used'))
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $record->usage_limit
                        ? "{$state} / {$record->usage_limit}"
                        : $state),

                Tables\Columns\TextColumn::make('valid_from')
                    ->label(__('filament.labels.valid_from'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label(__('filament.labels.valid_until'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('filament.labels.active')),

                Tables\Filters\SelectFilter::make('discount_type')
                    ->label(__('filament.labels.discount_type'))
                    ->options([
                        DiscountType::PERCENTAGE->value => __('filament.options.percentage'),
                        DiscountType::FIXED_AMOUNT->value => __('filament.options.fixed_amount'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading(__('filament.modals.delete_coupon'))
                    ->modalDescription(__('filament.modals.delete_coupon_description')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
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
