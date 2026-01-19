<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BlogCategoryResource\Pages;
use App\Models\BlogCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlogCategoryResource extends Resource
{
    protected static ?string $model = BlogCategory::class;

    protected static ?string $navigationIcon = null;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.blog_categories');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('filament.blog_category.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->label(__('filament.blog_category.slug'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('filament.blog_category.description'))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('color')
                    ->label(__('filament.blog_category.color'))
                    ->required()
                    ->maxLength(7)
                    ->default('#0D642E'),
                Forms\Components\TextInput::make('sort_order')
                    ->label(__('filament.blog_category.sort_order'))
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament.blog_category.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label(__('filament.blog_category.slug'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('color')
                    ->label(__('filament.blog_category.color'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('filament.blog_category.sort_order'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.blog_category.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament.blog_category.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListBlogCategories::route('/'),
            'create' => Pages\CreateBlogCategory::route('/create'),
            'edit' => Pages\EditBlogCategory::route('/{record}/edit'),
        ];
    }
}
