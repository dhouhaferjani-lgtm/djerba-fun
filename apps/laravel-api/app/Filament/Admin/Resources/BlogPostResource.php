<?php

namespace App\Filament\Admin\Resources;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\BlogPostResource\Pages;
use App\Models\BlogPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use AmidEsfahani\FilamentTinyEditor\TinyEditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class BlogPostResource extends Resource
{
    use Translatable;

    protected static ?string $model = BlogPost::class;

    protected static ?string $navigationIcon = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.blog_posts');
    }

    public static function getTranslatableLocales(): array
    {
        return ['en', 'fr'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.sections.content'))
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('filament.labels.title'))
                            ->required()
                            ->maxLength(255)
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state, ?string $old, $record) {
                                // Don't auto-update for existing posts (preserve SEO)
                                if ($record !== null && $record->exists) {
                                    return;
                                }

                                $currentSlug = $get('slug');
                                $newSlug = Str::slug($state ?? '');
                                $oldAutoSlug = $old ? Str::slug($old) : '';

                                // Update slug if:
                                // 1. It's empty, OR
                                // 2. It matches what would have been auto-generated from previous title
                                //    (meaning user hasn't manually edited it)
                                if (empty($currentSlug) || $currentSlug === $oldAutoSlug) {
                                    $set('slug', $newSlug);
                                }
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->label(__('filament.labels.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText(__('filament.helpers.slug_auto_generated')),

                        Forms\Components\Textarea::make('excerpt')
                            ->label(__('filament.labels.excerpt'))
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText(__('filament.helpers.excerpt_auto_generated'))
                            ->columnSpanFull(),

                        TinyEditor::make('content')
                            ->label(__('filament.labels.content'))
                            ->required()
                            ->profile('default')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('blog-attachments')
                            ->fileAttachmentsVisibility('public')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('filament.sections.metadata'))
                    ->schema([
                        Forms\Components\Select::make('author_id')
                            ->label(__('filament.labels.author'))
                            ->relationship(
                                'author',
                                'display_name',
                                fn (Builder $query) => $query->whereIn('role', [
                                    UserRole::ADMIN->value,
                                    UserRole::VENDOR->value,
                                ])
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => auth()->id()),

                        Forms\Components\Select::make('blog_category_id')
                            ->label(__('filament.labels.category'))
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\ColorPicker::make('color')
                                    ->default('#0D642E'),
                            ]),

                        Forms\Components\TagsInput::make('tags')
                            ->label(__('filament.labels.tags'))
                            ->placeholder(__('filament.helpers.add_tags'))
                            ->separator(','),
                    ])
                    ->columns(3),

                Forms\Components\Section::make(__('filament.sections.media'))
                    ->schema([
                        Forms\Components\FileUpload::make('featured_image')
                            ->label(__('filament.labels.featured_image'))
                            ->image()
                            ->disk('public')
                            ->directory('blog-images')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(10240),
                    ]),

                Forms\Components\Section::make(__('filament.sections.publishing'))
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label(__('filament.labels.status'))
                            ->options([
                                'draft' => __('filament.options.draft'),
                                'published' => __('filament.options.published'),
                                'scheduled' => __('filament.options.scheduled'),
                            ])
                            ->required()
                            ->default('draft')
                            ->live()
                            ->afterStateUpdated(function (string $state, Forms\Set $set, Forms\Get $get) {
                                // Auto-fill published_at when status becomes 'published' and field is empty
                                if ($state === 'published' && empty($get('published_at'))) {
                                    $set('published_at', now());
                                }
                            }),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label(__('filament.labels.publish_date'))
                            ->seconds(false)
                            ->visible(fn (Forms\Get $get) => in_array($get('status'), ['published', 'scheduled'])),

                        Forms\Components\Toggle::make('is_featured')
                            ->label(__('filament.labels.feature_on_homepage'))
                            ->helperText(__('filament.helpers.show_on_homepage')),
                    ])
                    ->columns(3),

                Forms\Components\Section::make(__('filament.sections.seo'))
                    ->schema([
                        Forms\Components\TextInput::make('seo_title')
                            ->label(__('filament.labels.seo_title'))
                            ->maxLength(60)
                            ->helperText(__('filament.helpers.seo_title_max')),

                        Forms\Components\Textarea::make('seo_description')
                            ->label(__('filament.labels.seo_description'))
                            ->rows(2)
                            ->maxLength(160)
                            ->helperText(__('filament.helpers.seo_description_max')),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label(__('filament.labels.image'))
                    ->square()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('filament.labels.title'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('author.display_name')
                    ->label(__('filament.labels.author'))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('filament.labels.category'))
                    ->badge()
                    ->color(fn ($record) => $record->category?->color)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament.labels.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'success',
                        'scheduled' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label(__('filament.labels.featured'))
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('views_count')
                    ->label(__('filament.labels.views'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label(__('filament.labels.published'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.labels.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => __('filament.options.draft'),
                        'published' => __('filament.options.published'),
                        'scheduled' => __('filament.options.scheduled'),
                    ]),

                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label(__('filament.labels.featured')),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('published_at', 'desc');
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
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
