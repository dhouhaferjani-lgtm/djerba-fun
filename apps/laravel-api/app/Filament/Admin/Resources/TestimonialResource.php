<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TestimonialResource\Pages;
use App\Models\Testimonial;
use Filament\Forms;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TestimonialResource extends Resource
{
    use Translatable;

    protected static ?string $model = Testimonial::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.content');
    }

    public static function getNavigationLabel(): string
    {
        return 'Testimonials';
    }

    public static function getModelLabel(): string
    {
        return 'Testimonial';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Testimonials';
    }

    public static function getTranslatableLocales(): array
    {
        return ['fr', 'en'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Information')
                    ->description('Details about the customer providing the testimonial')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Customer Name')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('e.g., Marie L.'),

                        Forms\Components\TextInput::make('location')
                            ->label('Location')
                            ->maxLength(100)
                            ->placeholder('e.g., Paris, France')
                            ->helperText('City and country of the customer'),

                        Forms\Components\FileUpload::make('photo')
                            ->label('Photo')
                            ->image()
                            ->directory('testimonials')
                            ->disk('public')
                            ->maxSize(2048)
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('300')
                            ->imageResizeTargetHeight('300')
                            ->getUploadedFileUsing(static function (BaseFileUpload $component, string $file): ?array {
                                $storage = $component->getDisk();
                                if (! $storage->exists($file)) {
                                    return null;
                                }

                                return [
                                    'name' => basename($file),
                                    'size' => $storage->size($file),
                                    'type' => $storage->mimeType($file),
                                    'url' => route('admin.storage.proxy', ['path' => $file]),
                                ];
                            })
                            ->helperText('Square photo recommended (300x300px). Max 2MB.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Testimonial Content')
                    ->description('The testimonial text (translatable)')
                    ->schema([
                        Forms\Components\Textarea::make('text')
                            ->label('Testimonial Text')
                            ->required()
                            ->rows(4)
                            ->maxLength(1000)
                            ->placeholder('Write the customer testimonial here...')
                            ->helperText('Maximum 1000 characters. Use the locale switcher to add translations.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Experience Details')
                    ->description('What activity did the customer experience?')
                    ->schema([
                        Forms\Components\TextInput::make('activity')
                            ->label('Activity / Experience')
                            ->maxLength(200)
                            ->placeholder('e.g., Desert Safari Tour')
                            ->helperText('The tour or activity the customer experienced'),

                        Forms\Components\Select::make('rating')
                            ->label('Rating')
                            ->options([
                                5 => '⭐⭐⭐⭐⭐ (5 stars)',
                                4 => '⭐⭐⭐⭐ (4 stars)',
                                3 => '⭐⭐⭐ (3 stars)',
                                2 => '⭐⭐ (2 stars)',
                                1 => '⭐ (1 star)',
                            ])
                            ->default(5)
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Display Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Only active testimonials are displayed on the website'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
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
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=T&background=0D642E&color=fff'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('activity')
                    ->label('Activity')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn (int $state): string => str_repeat('⭐', $state))
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\SelectFilter::make('rating')
                    ->label('Rating')
                    ->options([
                        5 => '5 stars',
                        4 => '4 stars',
                        3 => '3 stars',
                        2 => '2 stars',
                        1 => '1 star',
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestimonials::route('/'),
            'create' => Pages\CreateTestimonial::route('/create'),
            'edit' => Pages\EditTestimonial::route('/{record}/edit'),
        ];
    }
}
