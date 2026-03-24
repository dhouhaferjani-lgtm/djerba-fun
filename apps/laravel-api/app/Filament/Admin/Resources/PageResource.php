<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Statikbe\FilamentFlexibleContentBlockPages\Actions\LinkedToMenuItemBulkDeleteAction;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Table\Actions\PublishAction;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Table\Actions\ReplicateAction;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Table\Columns\PublishedColumn;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Table\Columns\TitleColumn;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Table\Filters\PublishedFilter;
use Statikbe\FilamentFlexibleContentBlocks\FilamentFlexibleBlocksConfig;

/**
 * PageResource with destination-style fixed sections.
 *
 * Allows clients to create pages with structured sections:
 * - General (title, slug, description, hero image, link)
 * - SEO (seo_title, seo_description, seo_text per locale)
 * - Content Sections (highlights, key_facts, gallery, points_of_interest)
 */
class PageResource extends Resource
{
    protected static ?string $navigationIcon = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.pages');
    }

    protected static ?string $recordRouteKeyName = 'id';

    protected static ?string $recordTitleAttribute = 'title';

    protected static int $globalSearchResultsLimit = 10;

    protected static ?bool $isGlobalSearchForcedCaseInsensitive = true;

    public static function getModel(): string
    {
        return Page::class;
    }

    public static function getLabel(): ?string
    {
        return __('filament.page.page');
    }

    public static function getPluralLabel(): ?string
    {
        return __('filament.page.pages');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['parent']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- General Section ---
                Section::make('General')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('title_en')
                            ->label('Page Title (English)')
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && method_exists($record, 'getTranslation')) {
                                    $component->state($record->getTranslation('title', 'en'));
                                }
                            })
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                $currentSlug = $get('slug_en');
                                $newSlug = Str::slug($state ?? '');
                                if (empty($currentSlug)) {
                                    $set('slug_en', $newSlug);
                                }
                            }),

                        Forms\Components\TextInput::make('title_fr')
                            ->label('Page Title (Français)')
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && method_exists($record, 'getTranslation')) {
                                    $component->state($record->getTranslation('title', 'fr'));
                                }
                            })
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                $currentSlug = $get('slug_fr');
                                $newSlug = Str::slug($state ?? '');
                                if (empty($currentSlug)) {
                                    $set('slug_fr', $newSlug);
                                }
                            }),

                        Forms\Components\TextInput::make('slug_en')
                            ->label('URL Slug (English)')
                            ->helperText('Auto-generated from English title. Used in URL path.')
                            ->rules(['regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'])
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && method_exists($record, 'getTranslation')) {
                                    $component->state($record->getTranslation('slug', 'en'));
                                }
                            }),

                        Forms\Components\TextInput::make('slug_fr')
                            ->label('URL Slug (Français)')
                            ->helperText('Auto-généré du titre français.')
                            ->rules(['regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'])
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && method_exists($record, 'getTranslation')) {
                                    $component->state($record->getTranslation('slug', 'fr'));
                                }
                            }),

                        Forms\Components\Textarea::make('description_en')
                            ->label('Short Description (English)')
                            ->rows(3)
                            ->helperText('Brief summary shown on page cards and listings'),

                        Forms\Components\Textarea::make('description_fr')
                            ->label('Short Description (Français)')
                            ->rows(3)
                            ->helperText('Résumé affiché sur les cartes et listes'),

                        SpatieMediaLibraryFileUpload::make('hero_image')
                            ->collection('hero_image')
                            ->label('Hero Image')
                            ->image()
                            ->maxSize(5120)
                            ->helperText('Recommended: 1200x675px (16:9 ratio). Max 5MB.')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('link')
                            ->label('CTA Link (optional)')
                            ->placeholder('/en/listings?location=djerba')
                            ->helperText('Link for call-to-action buttons. Leave empty if not needed.')
                            ->maxLength(500)
                            ->columnSpan(2),

                        Forms\Components\Toggle::make('is_published')
                            ->label('Published')
                            ->helperText('Turn on to make this page visible')
                            ->default(false)
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record) {
                                    $component->state($record->isPublished());
                                }
                            })
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // --- Hero CTA Buttons Section ---
                Section::make('Hero Call-to-Action Buttons')
                    ->icon('heroicon-o-cursor-arrow-rays')
                    ->description('Buttons displayed on the hero banner. Max 2 buttons recommended.')
                    ->schema([
                        Forms\Components\Repeater::make('hero_call_to_actions')
                            ->label('CTA Buttons')
                            ->addActionLabel('Add CTA Button')
                            ->schema([
                                Forms\Components\Select::make('cta_model')
                                    ->label('Link Type')
                                    ->options(['url' => 'URL'])
                                    ->default('url')
                                    ->required(),

                                Forms\Components\TextInput::make('url')
                                    ->label('URL')
                                    ->placeholder('/djerba/experience-name')
                                    ->required(),

                                Forms\Components\Select::make('button_style')
                                    ->label('Button Style')
                                    ->options([
                                        'primary' => 'Primary (Green)',
                                        'secondary' => 'Secondary (Outline)',
                                    ])
                                    ->default('primary')
                                    ->required(),

                                Forms\Components\TextInput::make('button_label.en')
                                    ->label('Button Label (English)')
                                    ->placeholder('Book Now')
                                    ->required(),

                                Forms\Components\TextInput::make('button_label.fr')
                                    ->label('Button Label (Français)')
                                    ->placeholder('Réserver')
                                    ->required(),

                                Forms\Components\Toggle::make('button_open_new_window')
                                    ->label('Open in new tab')
                                    ->default(false),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['button_label']['en'] ?? 'New Button')
                            ->defaultItems(0)
                            ->maxItems(3)
                            ->reorderable(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // --- SEO Section ---
                Section::make('SEO')
                    ->icon('heroicon-o-magnifying-glass')
                    ->schema([
                        Forms\Components\TextInput::make('seo_title_en')
                            ->label('SEO Title (English)')
                            ->placeholder('Page Title | Djerba Fun')
                            ->maxLength(120)
                            ->helperText('Page <title> tag. Max 120 chars.'),

                        Forms\Components\TextInput::make('seo_title_fr')
                            ->label('SEO Title (Français)')
                            ->placeholder('Titre de la Page | Djerba Fun')
                            ->maxLength(120),

                        Forms\Components\Textarea::make('seo_description_en')
                            ->label('Meta Description (English)')
                            ->rows(2)
                            ->maxLength(300)
                            ->helperText('Shown in Google results. Max 300 chars.'),

                        Forms\Components\Textarea::make('seo_description_fr')
                            ->label('Meta Description (Français)')
                            ->rows(2)
                            ->maxLength(300),

                        Forms\Components\Textarea::make('seo_text_en')
                            ->label('Long Description (English)')
                            ->rows(6)
                            ->helperText('Detailed paragraph for the "About" section on the page.'),

                        Forms\Components\Textarea::make('seo_text_fr')
                            ->label('Long Description (Français)')
                            ->rows(6)
                            ->helperText('Paragraphe détaillé pour la section "À propos".'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                // --- Content Sections ---
                Section::make('Content Sections')
                    ->description('Click each button to manage content in a popup editor.')
                    ->icon('heroicon-o-rectangle-group')
                    ->schema([
                        Forms\Components\Actions::make([
                            // --- Highlights modal ---
                            Action::make('edit_highlights')
                                ->label(fn (Get $get): string => 'Highlights ('.count($get('highlights') ?? []).')')
                                ->icon('heroicon-o-sparkles')
                                ->color('warning')
                                ->modalHeading('Manage Highlights')
                                ->modalDescription('Key features displayed in the "What awaits you" section.')
                                ->modalWidth('5xl')
                                ->modalSubmitActionLabel('Save Highlights')
                                ->fillForm(fn (Get $get): array => [
                                    'highlights' => $get('highlights') ?? [],
                                ])
                                ->form([
                                    Forms\Components\Repeater::make('highlights')
                                        ->label('Highlights')
                                        ->addActionLabel('Add highlight')
                                        ->schema([
                                            Forms\Components\Select::make('icon')
                                                ->label('Icon')
                                                ->options(self::getIconOptions())
                                                ->searchable()
                                                ->required(),

                                            Forms\Components\TextInput::make('title_en')
                                                ->label('Title (English)')
                                                ->required(),

                                            Forms\Components\TextInput::make('title_fr')
                                                ->label('Title (Français)')
                                                ->required(),

                                            Forms\Components\Textarea::make('description_en')
                                                ->label('Description (English)')
                                                ->rows(3)
                                                ->required(),

                                            Forms\Components\Textarea::make('description_fr')
                                                ->label('Description (Français)')
                                                ->rows(3)
                                                ->required(),
                                        ])
                                        ->columns(2)
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['title_en'] ?? null)
                                        ->defaultItems(0)
                                        ->maxItems(12)
                                        ->reorderable(),
                                ])
                                ->action(function (array $data, Set $set): void {
                                    $set('highlights', array_values($data['highlights'] ?? []));
                                }),

                            // --- Key Facts modal ---
                            Action::make('edit_key_facts')
                                ->label(fn (Get $get): string => 'Key Facts ('.count($get('key_facts') ?? []).')')
                                ->icon('heroicon-o-chart-bar')
                                ->color('success')
                                ->modalHeading('Manage Key Facts')
                                ->modalDescription('Quick stats shown in the info bar.')
                                ->modalWidth('5xl')
                                ->modalSubmitActionLabel('Save Key Facts')
                                ->fillForm(fn (Get $get): array => [
                                    'key_facts' => $get('key_facts') ?? [],
                                ])
                                ->form([
                                    Forms\Components\Repeater::make('key_facts')
                                        ->label('Key Facts')
                                        ->addActionLabel('Add key fact')
                                        ->schema([
                                            Forms\Components\Select::make('icon')
                                                ->label('Icon')
                                                ->options(self::getIconOptions())
                                                ->searchable()
                                                ->required(),

                                            Forms\Components\TextInput::make('label_en')
                                                ->label('Label (English)')
                                                ->placeholder('Area')
                                                ->required(),

                                            Forms\Components\TextInput::make('label_fr')
                                                ->label('Label (Français)')
                                                ->placeholder('Superficie')
                                                ->required(),

                                            Forms\Components\TextInput::make('value')
                                                ->label('Value')
                                                ->placeholder('514 km²')
                                                ->required(),
                                        ])
                                        ->columns(2)
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => ($state['label_en'] ?? '').': '.($state['value'] ?? ''))
                                        ->defaultItems(0)
                                        ->maxItems(8)
                                        ->reorderable(),
                                ])
                                ->action(function (array $data, Set $set): void {
                                    $set('key_facts', array_values($data['key_facts'] ?? []));
                                }),

                            // --- Gallery modal ---
                            Action::make('edit_gallery')
                                ->label(fn (Get $get): string => 'Gallery ('.count($get('gallery') ?? []).')')
                                ->icon('heroicon-o-photo')
                                ->color('info')
                                ->modalHeading('Manage Photo Gallery')
                                ->modalDescription('Upload images via the uploader, then create entries below.')
                                ->modalWidth('5xl')
                                ->modalSubmitActionLabel('Save Gallery')
                                ->fillForm(fn (Get $get): array => [
                                    'gallery' => $get('gallery') ?? [],
                                ])
                                ->form([
                                    Forms\Components\FileUpload::make('_gallery_uploader')
                                        ->label('Upload Gallery Images')
                                        ->multiple()
                                        ->directory('pages/gallery')
                                        ->disk('public')
                                        ->image()
                                        ->maxSize(5120)
                                        ->dehydrated(false)
                                        ->helperText('Upload images here. Then create gallery entries below with file paths.'),

                                    Forms\Components\Repeater::make('gallery')
                                        ->label('Gallery Entries')
                                        ->addActionLabel('Add gallery image')
                                        ->schema([
                                            Forms\Components\TextInput::make('image')
                                                ->label('Image Path')
                                                ->required()
                                                ->placeholder('pages/gallery/photo.jpg')
                                                ->helperText('File path relative to storage'),

                                            Forms\Components\TextInput::make('alt_en')
                                                ->label('Alt Text (English)')
                                                ->required()
                                                ->helperText('Accessibility & SEO description'),

                                            Forms\Components\TextInput::make('alt_fr')
                                                ->label('Alt Text (Français)')
                                                ->required(),

                                            Forms\Components\TextInput::make('caption_en')
                                                ->label('Caption (English)'),

                                            Forms\Components\TextInput::make('caption_fr')
                                                ->label('Caption (Français)'),
                                        ])
                                        ->columns(2)
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['caption_en'] ?? $state['alt_en'] ?? null)
                                        ->defaultItems(0)
                                        ->maxItems(10)
                                        ->reorderable(),
                                ])
                                ->action(function (array $data, Set $set): void {
                                    $set('gallery', array_values($data['gallery'] ?? []));
                                }),

                            // --- Points of Interest modal ---
                            Action::make('edit_points_of_interest')
                                ->label(fn (Get $get): string => 'Must-See Places ('.count($get('points_of_interest') ?? []).')')
                                ->icon('heroicon-o-map-pin')
                                ->color('danger')
                                ->modalHeading('Manage Must-See Places')
                                ->modalDescription('Points of interest displayed on the page.')
                                ->modalWidth('5xl')
                                ->modalSubmitActionLabel('Save Places')
                                ->fillForm(fn (Get $get): array => [
                                    'points_of_interest' => $get('points_of_interest') ?? [],
                                ])
                                ->form([
                                    Forms\Components\Repeater::make('points_of_interest')
                                        ->label('Must-See Places / Points of Interest')
                                        ->addActionLabel('Add place')
                                        ->schema([
                                            Forms\Components\TextInput::make('name_en')
                                                ->label('Place Name (English)')
                                                ->required(),

                                            Forms\Components\TextInput::make('name_fr')
                                                ->label('Place Name (Français)')
                                                ->required(),

                                            Forms\Components\Textarea::make('description_en')
                                                ->label('Description (English)')
                                                ->rows(3)
                                                ->required(),

                                            Forms\Components\Textarea::make('description_fr')
                                                ->label('Description (Français)')
                                                ->rows(3)
                                                ->required(),
                                        ])
                                        ->columns(2)
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['name_en'] ?? null)
                                        ->defaultItems(0)
                                        ->maxItems(8)
                                        ->reorderable(),
                                ])
                                ->action(function (array $data, Set $set): void {
                                    $set('points_of_interest', array_values($data['points_of_interest'] ?? []));
                                }),
                        ])->columnSpanFull(),

                        // Hidden fields to store the JSON data
                        Forms\Components\Hidden::make('highlights')
                            ->dehydrateStateUsing(fn ($state) => $state)
                            ->afterStateHydrated(fn ($component, $state) => $component->state($state ?? [])),
                        Forms\Components\Hidden::make('key_facts')
                            ->dehydrateStateUsing(fn ($state) => $state)
                            ->afterStateHydrated(fn ($component, $state) => $component->state($state ?? [])),
                        Forms\Components\Hidden::make('gallery')
                            ->dehydrateStateUsing(fn ($state) => $state)
                            ->afterStateHydrated(fn ($component, $state) => $component->state($state ?? [])),
                        Forms\Components\Hidden::make('points_of_interest')
                            ->dehydrateStateUsing(fn ($state) => $state)
                            ->afterStateHydrated(fn ($component, $state) => $component->state($state ?? [])),
                    ])
                    ->collapsible(),

                // --- Advanced Section ---
                Section::make('Advanced')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Unique Code')
                            ->helperText('Used for programmatic page lookup (e.g., HOME, ABOUT)')
                            ->maxLength(50),

                        Forms\Components\DateTimePicker::make('publishing_begins_at')
                            ->label('Publish From')
                            ->helperText('Page visible from this date'),

                        Forms\Components\DateTimePicker::make('publishing_ends_at')
                            ->label('Publish Until')
                            ->helperText('Page hidden after this date'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TitleColumn::create()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('filament.page.created'))
                    ->dateTime(FilamentFlexibleBlocksConfig::getPublishingDateFormatting())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('filament.page.updated'))
                    ->dateTime(FilamentFlexibleBlocksConfig::getPublishingDateFormatting())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('code')
                    ->label(__('filament.page.code'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                PublishedColumn::create()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                PublishedFilter::create(),
            ])
            ->actions([
                EditAction::make(),
                PublishAction::make(),
                ReplicateAction::make()
                    ->visible(false) // Disabled for now
                    ->successRedirectUrl(fn (ReplicateAction $action) => PageResource::getUrl('edit', ['record' => $action->getReplica()])),
            ])
            ->bulkActions([
                LinkedToMenuItemBulkDeleteAction::make(),
            ])
            ->recordUrl(
                fn ($record): string => static::getUrl('edit', ['record' => $record])
            )
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['menuItem']);
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record:id}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'title',
            'description_en',
            'description_fr',
            'seo_title_en',
            'seo_title_fr',
            'seo_description_en',
            'seo_description_fr',
            'code',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return method_exists($record, 'getTranslation')
            ? $record->getTranslation('title', app()->getLocale())
            : $record->getAttribute('title');
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Page $record */
        $published = __('filament.page.draft');

        if ($record->isPublished()) {
            $published = __('filament.page.published');
        }

        return [
            __('filament.page.intro') => Str::limit(strip_tags($record->description_en ?? ''), 50),
            __('filament.page.status') => $published,
        ];
    }

    /**
     * Get available icon options for highlights and key facts.
     * Matches the icons used in PlatformSettingsPage destinations.
     */
    protected static function getIconOptions(): array
    {
        return [
            'waves' => 'Waves (water/beach)',
            'landmark' => 'Landmark (heritage/monument)',
            'mountain' => 'Mountain (hiking/terrain)',
            'compass' => 'Compass (exploration)',
            'users' => 'Users (people/community)',
            'eye' => 'Eye (viewpoint/observation)',
            'moon' => 'Moon (night/stargazing)',
            'tree-palm' => 'Palm Tree (oasis/tropical)',
            'sparkles' => 'Sparkles (special/magic)',
            'map' => 'Map (geography)',
            'tent' => 'Tent (camping)',
            'palette' => 'Palette (art/culture)',
            'shopping-bag' => 'Shopping Bag (markets)',
            'bird' => 'Bird (wildlife)',
            'home' => 'Home (village/dwelling)',
            'film' => 'Film (cinema/Star Wars)',
            'droplets' => 'Droplets (water/springs)',
            'footprints' => 'Footprints (trekking)',
            'layers' => 'Layers (geology)',
            'map-pin' => 'Map Pin (location)',
            'calendar' => 'Calendar (season/events)',
            'ruler' => 'Ruler (measurements)',
            'star' => 'Star (rating/highlight)',
            'utensils-crossed' => 'Utensils (food/gastronomy)',
            'info' => 'Info (information)',
        ];
    }
}
