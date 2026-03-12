<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Models\PlatformSettings;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;

class PlatformSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = null; // Group already has icon

    protected static string $view = 'filament.admin.pages.platform-settings';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Platform Settings';

    protected static ?string $title = 'Platform Settings';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'platform-settings';

    public ?array $data = [];

    /**
     * The settings model instance.
     *
     * CRITICAL: Must be public for Livewire hydration between requests.
     * Protected properties are not serialized, causing $settings to be null on save().
     */
    public ?PlatformSettings $settings = null;

    public function mount(): void
    {
        // Get or create the settings record - MUST exist for media uploads
        $this->settings = PlatformSettings::first();

        if (! $this->settings) {
            $this->settings = PlatformSettings::create([]);
        }

        // Ensure model is persisted (has ID) before form hydration
        // SpatieMediaLibraryFileUpload needs model_id to associate media
        if (! $this->settings->exists) {
            $this->settings->save();
        }

        // Load media relationship for display in form
        $this->settings->load('media');

        // Get model attributes and sanitize KeyValue fields
        $attributes = $this->settings->attributesToArray();
        $attributes = $this->sanitizeKeyValueFieldsForForm($attributes);

        // Fill form with sanitized model attributes
        $this->form->fill($attributes);
    }

    /**
     * Sanitize KeyValue fields when loading from database for form display.
     * Converts any nested arrays to strings to prevent form errors.
     */
    protected function sanitizeKeyValueFieldsForForm(array $data): array
    {
        $keyValueFields = ['business_hours', 'default_cancellation_policy'];

        foreach ($keyValueFields as $field) {
            if (isset($data[$field])) {
                if (is_array($data[$field])) {
                    $data[$field] = array_map(function ($value) {
                        if (is_array($value)) {
                            return json_encode($value);
                        }
                        return $value === null ? '' : (string) $value;
                    }, $data[$field]);
                } else {
                    // If it's not an array at all, reset to empty array
                    $data[$field] = [];
                }
            }
        }

        return $data;
    }

    /**
     * Get the model for form media uploads.
     * Required for SpatieMediaLibraryFileUpload components.
     */
    public function getFormModel(): PlatformSettings
    {
        // Defensive: handle case where property was null after Livewire hydration
        if ($this->settings === null) {
            $this->settings = PlatformSettings::first();

            if (! $this->settings) {
                $this->settings = PlatformSettings::create([]);
            }

            $this->settings->load('media');
        }

        return $this->settings;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->tabs([
                        $this->platformIdentityTab(),
                        $this->logoBrandingTab(),
                        $this->eventOfYearTab(),
                        $this->featuredDestinationsTab(),
                        $this->testimonialsTab(),
                        // CMS Section Tabs
                        $this->experienceCategoriesTab(),
                        $this->blogSectionTab(),
                        $this->featuredPackagesTab(),
                        $this->customExperienceTab(),
                        $this->newsletterTab(),
                        $this->aboutPageTab(),
                        // Other Settings
                        $this->seoMetadataTab(),
                        $this->contactInformationTab(),
                        $this->physicalAddressTab(),
                        $this->socialMediaTab(),
                        $this->emailSettingsTab(),
                        $this->paymentCommerceTab(),
                        $this->bookingSettingsTab(),
                        $this->localizationTab(),
                        $this->featureFlagsTab(),
                        $this->analyticsTrackingTab(),
                        $this->legalComplianceTab(),
                        $this->vendorSettingsTab(),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ])
            ->model($this->getFormModel()) // Explicitly bind model for media uploads
            ->statePath('data');
    }

    protected function platformIdentityTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Platform Identity')
            ->icon('heroicon-o-identification')
            ->schema([
                Forms\Components\Section::make('Platform Name & Branding')
                    ->description('Configure the platform identity in multiple languages')
                    ->schema([
                        Forms\Components\Tabs::make('Translations')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('English')
                                    ->schema([
                                        Forms\Components\TextInput::make('platform_name.en')
                                            ->label('Platform Name')
                                            ->required()
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('tagline.en')
                                            ->label('Tagline')
                                            ->maxLength(150),
                                        Forms\Components\Textarea::make('description.en')
                                            ->label('Description')
                                            ->rows(3)
                                            ->maxLength(500),
                                    ]),
                                Forms\Components\Tabs\Tab::make('French')
                                    ->schema([
                                        Forms\Components\TextInput::make('platform_name.fr')
                                            ->label('Nom de la plateforme')
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('tagline.fr')
                                            ->label('Slogan')
                                            ->maxLength(150),
                                        Forms\Components\Textarea::make('description.fr')
                                            ->label('Description')
                                            ->rows(3)
                                            ->maxLength(500),
                                    ]),
                                Forms\Components\Tabs\Tab::make('Arabic')
                                    ->schema([
                                        Forms\Components\TextInput::make('platform_name.ar')
                                            ->label('اسم المنصة')
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('tagline.ar')
                                            ->label('الشعار')
                                            ->maxLength(150),
                                        Forms\Components\Textarea::make('description.ar')
                                            ->label('الوصف')
                                            ->rows(3)
                                            ->maxLength(500),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('URLs')
                    ->description('Configure platform URLs')
                    ->schema([
                        Forms\Components\TextInput::make('primary_domain')
                            ->label('Primary Domain')
                            ->url()
                            ->placeholder('https://evasiondjerba.com'),
                        Forms\Components\TextInput::make('api_url')
                            ->label('API URL')
                            ->url()
                            ->placeholder('https://api.evasiondjerba.com'),
                        Forms\Components\TextInput::make('frontend_url')
                            ->label('Frontend URL')
                            ->url()
                            ->placeholder('https://evasiondjerba.com'),
                    ])
                    ->columns(3),
            ]);
    }

    protected function logoBrandingTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Logo & Branding')
            ->icon('heroicon-o-photo')
            ->schema([
                Forms\Components\Section::make('Logos')
                    ->description('Upload platform logos. Recommended: SVG or PNG with transparent background.')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('logo_light')
                            ->collection('logo_light')
                            ->model(fn () => $this->getFormModel())
                            ->label('Logo (Light Mode)')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([null])
                            ->maxSize(2048)
                            ->helperText('Used on light backgrounds. Click edit icon to crop/zoom.'),
                        Forms\Components\SpatieMediaLibraryFileUpload::make('logo_dark')
                            ->collection('logo_dark')
                            ->model(fn () => $this->getFormModel())
                            ->label('Logo (Dark Mode)')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([null])
                            ->maxSize(2048)
                            ->helperText('Used on dark backgrounds. Click edit icon to crop/zoom.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Favicons & Icons')
                    ->description('Upload favicon and app icons')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('favicon')
                            ->collection('favicon')
                            ->model(fn () => $this->getFormModel())
                            ->label('Favicon')
                            ->image()
                            ->maxSize(512)
                            ->helperText('Recommended: 32x32 or 16x16 PNG/ICO'),
                        Forms\Components\SpatieMediaLibraryFileUpload::make('apple_touch_icon')
                            ->collection('apple_touch_icon')
                            ->model(fn () => $this->getFormModel())
                            ->label('Apple Touch Icon')
                            ->image()
                            ->maxSize(1024)
                            ->helperText('180x180 PNG for iOS home screen'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Open Graph Image')
                    ->description('Default image for social sharing')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('og_image')
                            ->collection('og_image')
                            ->model(fn () => $this->getFormModel())
                            ->label('OG Image')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Recommended: 1200x630 PNG/JPG'),
                    ]),

                Forms\Components\Section::make('Hero Banner')
                    ->description('Main banner displayed on the homepage hero section. Supports images (JPG/PNG/WebP) and videos (MP4/WebM). Videos will autoplay muted on desktop, with the image shown on mobile.')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('hero_banner')
                            ->collection('hero_banner')
                            ->model(fn () => $this->getFormModel())
                            ->label('Hero Banner (Image or Video)')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'video/mp4', 'video/webm'])
                            ->maxSize(20480)
                            ->helperText('Image: 1920x1080+ recommended (max 20MB). Video: MP4/WebM, 720p+, max 20MB. Short looping videos (5-10s) work best.'),
                    ]),

                Forms\Components\Section::make('Hero Section Text')
                    ->description('Text displayed on the homepage hero section. The first word of the title will be displayed in green, the rest in white. Both English and French are required.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                // English Column
                                Forms\Components\Section::make('English')
                                    ->schema([
                                        Forms\Components\TextInput::make('hero_title.en')
                                            ->label('Title')
                                            ->required()
                                            ->maxLength(150)
                                            ->placeholder("Vivez l'Aventure au Cœur du Sahara")
                                            ->helperText('First word will be green, rest will be white'),
                                        Forms\Components\Textarea::make('hero_subtitle.en')
                                            ->label('Subtitle')
                                            ->required()
                                            ->rows(2)
                                            ->maxLength(300)
                                            ->placeholder('Discover authentic Tunisian adventures...'),
                                    ]),

                                // French Column
                                Forms\Components\Section::make('Français')
                                    ->schema([
                                        Forms\Components\TextInput::make('hero_title.fr')
                                            ->label('Titre')
                                            ->required()
                                            ->maxLength(150)
                                            ->placeholder("Vivez l'Aventure au Cœur du Sahara")
                                            ->helperText('Le premier mot sera en vert, le reste en blanc'),
                                        Forms\Components\Textarea::make('hero_subtitle.fr')
                                            ->label('Sous-titre')
                                            ->required()
                                            ->rows(2)
                                            ->maxLength(300)
                                            ->placeholder('Des expériences inoubliables...'),
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Brand Pillar Images')
                    ->description('Three square images displayed in the marketing mosaic section below the hero')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('brand_pillar_1')
                            ->collection('brand_pillar_1')
                            ->model(fn () => $this->getFormModel())
                            ->label('Pillar 1: Sustainable Travel')
                            ->image()
                            ->maxSize(5120)
                            ->helperText('Recommended: 1080x1080 square image'),
                        Forms\Components\SpatieMediaLibraryFileUpload::make('brand_pillar_2')
                            ->collection('brand_pillar_2')
                            ->model(fn () => $this->getFormModel())
                            ->label('Pillar 2: Authentic Experiences')
                            ->image()
                            ->maxSize(5120)
                            ->helperText('Recommended: 1080x1080 square image'),
                        Forms\Components\SpatieMediaLibraryFileUpload::make('brand_pillar_3')
                            ->collection('brand_pillar_3')
                            ->model(fn () => $this->getFormModel())
                            ->label('Pillar 3: Epic Adventures')
                            ->image()
                            ->maxSize(5120)
                            ->helperText('Recommended: 1080x1080 square image'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Brand Pillar Text')
                    ->description('Text displayed on each brand pillar card. Both English and French are required.')
                    ->schema([
                        // Pillar 1: Sustainable/Tourisme Responsable
                        Forms\Components\Fieldset::make('Pillar 1: Sustainable Travel / Tourisme Responsable')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('pillar_1_title.en')
                                            ->label('Title (English)')
                                            ->required()
                                            ->maxLength(50)
                                            ->placeholder('Sustainable Travel'),
                                        Forms\Components\TextInput::make('pillar_1_title.fr')
                                            ->label('Titre (Français)')
                                            ->required()
                                            ->maxLength(50)
                                            ->placeholder('Tourisme Responsable'),
                                    ]),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('pillar_1_description.en')
                                            ->label('Description (English)')
                                            ->required()
                                            ->maxLength(150)
                                            ->placeholder('Eco-conscious adventures that protect our planet'),
                                        Forms\Components\TextInput::make('pillar_1_description.fr')
                                            ->label('Description (Français)')
                                            ->required()
                                            ->maxLength(150)
                                            ->placeholder('Des aventures qui préservent notre planète'),
                                    ]),
                            ]),

                        // Pillar 2: Authentic/Authenticité Garantie
                        Forms\Components\Fieldset::make('Pillar 2: Authentic Experiences / Authenticité Garantie')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('pillar_2_title.en')
                                            ->label('Title (English)')
                                            ->required()
                                            ->maxLength(50)
                                            ->placeholder('Authentic Experiences'),
                                        Forms\Components\TextInput::make('pillar_2_title.fr')
                                            ->label('Titre (Français)')
                                            ->required()
                                            ->maxLength(50)
                                            ->placeholder('Authenticité Garantie'),
                                    ]),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('pillar_2_description.en')
                                            ->label('Description (English)')
                                            ->required()
                                            ->maxLength(150)
                                            ->placeholder('Connect with local cultures and traditions'),
                                        Forms\Components\TextInput::make('pillar_2_description.fr')
                                            ->label('Description (Français)')
                                            ->required()
                                            ->maxLength(150)
                                            ->placeholder('Immersion dans les cultures et traditions locales'),
                                    ]),
                            ]),

                        // Pillar 3: Adventure/Sensations Fortes
                        Forms\Components\Fieldset::make('Pillar 3: Epic Adventures / Sensations Fortes')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('pillar_3_title.en')
                                            ->label('Title (English)')
                                            ->required()
                                            ->maxLength(50)
                                            ->placeholder('Epic Adventures'),
                                        Forms\Components\TextInput::make('pillar_3_title.fr')
                                            ->label('Titre (Français)')
                                            ->required()
                                            ->maxLength(50)
                                            ->placeholder('Sensations Fortes'),
                                    ]),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('pillar_3_description.en')
                                            ->label('Description (English)')
                                            ->required()
                                            ->maxLength(150)
                                            ->placeholder('Unforgettable journeys in breathtaking landscapes'),
                                        Forms\Components\TextInput::make('pillar_3_description.fr')
                                            ->label('Description (Français)')
                                            ->required()
                                            ->maxLength(150)
                                            ->placeholder('Des moments inoubliables dans des paysages grandioses'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    protected function eventOfYearTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Event of the Year')
            ->icon('heroicon-o-star')
            ->schema([
                Forms\Components\Section::make('Event Settings')
                    ->description('Configure the featured "Event of the Year" banner displayed on the homepage')
                    ->schema([
                        Forms\Components\Toggle::make('event_of_year_enabled')
                            ->label('Enable Event of the Year')
                            ->helperText('Show or hide the event banner on the homepage')
                            ->default(true)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('event_of_year_link')
                            ->label('Event URL')
                            ->url()
                            ->placeholder('https://evasiondjerba.com/events/festival-2025')
                            ->helperText('Link for "Learn More" or "Register Now" button'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Event Content')
                    ->description('Title and description in multiple languages')
                    ->schema([
                        Forms\Components\Tabs::make('Event Translations')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('English')
                                    ->schema([
                                        Forms\Components\TextInput::make('event_of_year_tag.en')
                                            ->label('Tag / Badge Text')
                                            ->placeholder('Event of the Year')
                                            ->maxLength(50)
                                            ->helperText('Small tag displayed above the title'),
                                        Forms\Components\TextInput::make('event_of_year_title.en')
                                            ->label('Event Title')
                                            ->required()
                                            ->maxLength(150)
                                            ->placeholder('Sahara Festival 2025'),
                                        Forms\Components\Textarea::make('event_of_year_description.en')
                                            ->label('Event Description')
                                            ->rows(3)
                                            ->maxLength(500)
                                            ->placeholder('Join us for an unforgettable celebration of Saharan culture...'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('French')
                                    ->schema([
                                        Forms\Components\TextInput::make('event_of_year_tag.fr')
                                            ->label('Tag / Badge')
                                            ->placeholder('Événement de l\'Année')
                                            ->maxLength(50)
                                            ->helperText('Petit badge affiché au-dessus du titre'),
                                        Forms\Components\TextInput::make('event_of_year_title.fr')
                                            ->label('Titre de l\'événement')
                                            ->maxLength(150)
                                            ->placeholder('Festival du Sahara 2025'),
                                        Forms\Components\Textarea::make('event_of_year_description.fr')
                                            ->label('Description de l\'événement')
                                            ->rows(3)
                                            ->maxLength(500),
                                    ]),
                                Forms\Components\Tabs\Tab::make('Arabic')
                                    ->schema([
                                        Forms\Components\TextInput::make('event_of_year_tag.ar')
                                            ->label('شارة / علامة')
                                            ->maxLength(50)
                                            ->helperText('علامة صغيرة تظهر فوق العنوان'),
                                        Forms\Components\TextInput::make('event_of_year_title.ar')
                                            ->label('عنوان الحدث')
                                            ->maxLength(150),
                                        Forms\Components\Textarea::make('event_of_year_description.ar')
                                            ->label('وصف الحدث')
                                            ->rows(3)
                                            ->maxLength(500),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Event Image')
                    ->description('Featured image for the event banner')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('event_of_year_image')
                            ->collection('event_of_year_image')
                            ->model(fn () => $this->getFormModel())
                            ->label('Event Banner Image')
                            ->image()
                            ->maxSize(5120)
                            ->helperText('Recommended: 1200x600 or 16:9 aspect ratio. Max 5MB.'),
                    ]),
            ]);
    }

    protected function featuredDestinationsTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Destinations')
            ->icon('heroicon-o-map')
            ->schema([
                Forms\Components\Section::make('Featured Destinations')
                    ->description('Manage destinations displayed on the homepage and their informative content pages. Each destination has its own SEO, highlights, key facts, gallery, and must-see places.')
                    ->schema([
                        Forms\Components\Repeater::make('featured_destinations')
                            ->label('Destinations')
                            ->schema([
                                // --- General ---
                                Forms\Components\Section::make('General')
                                    ->icon('heroicon-o-information-circle')
                                    ->schema([
                                        Forms\Components\Hidden::make('_auto_slug')
                                            ->dehydrated(false),

                                        Forms\Components\TextInput::make('name')
                                            ->label('Display Name')
                                            ->required()
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                                $currentSlug = $get('id');
                                                $newSlug = \Illuminate\Support\Str::slug($state ?? '');
                                                $autoSlug = $get('_auto_slug');
                                                if (empty($currentSlug) || $currentSlug === $autoSlug) {
                                                    $set('id', $newSlug);
                                                    $set('_auto_slug', $newSlug);
                                                }
                                            }),

                                        Forms\Components\TextInput::make('id')
                                            ->label('Slug/ID')
                                            ->required()
                                            ->helperText('Auto-generated from name. Used in URL, e.g., houmet-souk')
                                            ->rules(['regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/']),

                                        Forms\Components\TextInput::make('description_en')
                                            ->label('Short Description (English)')
                                            ->required()
                                            ->helperText('Shown on homepage card'),

                                        Forms\Components\TextInput::make('description_fr')
                                            ->label('Short Description (Français)')
                                            ->required()
                                            ->helperText('Affiché sur la carte de la page d\'accueil'),

                                        Forms\Components\FileUpload::make('image')
                                            ->label('Hero Image')
                                            ->required()
                                            ->image()
                                            ->directory('destinations')
                                            ->disk('public')
                                            ->maxSize(5120)
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
                                            ->helperText('Recommended: 1200x675px (16:9 ratio). Max 5MB.'),

                                        Forms\Components\TextInput::make('link')
                                            ->label('Adventures Link (optional)')
                                            ->placeholder('/en/listings?location=desert')
                                            ->helperText('Link for the "Explore adventures" CTA button. Leave empty to link to homepage.')
                                            ->maxLength(500),
                                    ])
                                    ->columns(2)
                                    ->collapsible(),

                                // --- SEO ---
                                Forms\Components\Section::make('SEO')
                                    ->icon('heroicon-o-magnifying-glass')
                                    ->schema([
                                        Forms\Components\TextInput::make('seo_title_en')
                                            ->label('SEO Title (English)')
                                            ->placeholder('Djerba, Tunisia — UNESCO Island, Beaches & Culture | Evasion Djerba')
                                            ->maxLength(120)
                                            ->helperText('Page <title> tag. Max 120 chars.'),

                                        Forms\Components\TextInput::make('seo_title_fr')
                                            ->label('SEO Title (Français)')
                                            ->placeholder('Djerba, Tunisie — Île UNESCO, Plages & Culture | Evasion Djerba')
                                            ->maxLength(120),

                                        Forms\Components\Textarea::make('seo_description_en')
                                            ->label('SEO Meta Description (English)')
                                            ->rows(2)
                                            ->maxLength(300)
                                            ->helperText('Shown in Google results. Max 300 chars.'),

                                        Forms\Components\Textarea::make('seo_description_fr')
                                            ->label('SEO Meta Description (Français)')
                                            ->rows(2)
                                            ->maxLength(300),

                                        Forms\Components\Textarea::make('seo_text_en')
                                            ->label('Long SEO Description (English)')
                                            ->rows(6)
                                            ->helperText('Detailed paragraph displayed on the destination page "About this destination" section.'),

                                        Forms\Components\Textarea::make('seo_text_fr')
                                            ->label('Long SEO Description (Français)')
                                            ->rows(6)
                                            ->helperText('Paragraphe détaillé affiché dans la section "À propos de cette destination".'),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->collapsed(),

                                // --- Content Sections (managed via modals to avoid nested Repeater bugs) ---
                                Forms\Components\Section::make('Content Sections')
                                    ->description('Click each button to manage content in a popup editor.')
                                    ->schema([
                                        Forms\Components\Actions::make([
                                            // --- Highlights modal ---
                                            Forms\Components\Actions\Action::make('edit_highlights')
                                                ->label(fn (Get $get): string => 'Highlights (' . count($get('highlights') ?? []) . ')')
                                                ->icon('heroicon-o-sparkles')
                                                ->color('warning')
                                                ->modalHeading('Manage Highlights')
                                                ->modalDescription('These appear in the "What awaits you" section on the destination page.')
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
                                            Forms\Components\Actions\Action::make('edit_key_facts')
                                                ->label(fn (Get $get): string => 'Key Facts (' . count($get('key_facts') ?? []) . ')')
                                                ->icon('heroicon-o-chart-bar')
                                                ->color('success')
                                                ->modalHeading('Manage Key Facts')
                                                ->modalDescription('Quick stats shown in the info bar on the destination page.')
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
                                                        ->itemLabel(fn (array $state): ?string => ($state['label_en'] ?? '') . ': ' . ($state['value'] ?? ''))
                                                        ->defaultItems(0)
                                                        ->maxItems(8)
                                                        ->reorderable(),
                                                ])
                                                ->action(function (array $data, Set $set): void {
                                                    $set('key_facts', array_values($data['key_facts'] ?? []));
                                                }),

                                            // --- Gallery modal ---
                                            Forms\Components\Actions\Action::make('edit_gallery')
                                                ->label(fn (Get $get): string => 'Gallery (' . count($get('gallery') ?? []) . ')')
                                                ->icon('heroicon-o-photo')
                                                ->color('info')
                                                ->modalHeading('Manage Photo Gallery')
                                                ->modalDescription('Upload images via the uploader, then create entries with the file paths below.')
                                                ->modalWidth('5xl')
                                                ->modalSubmitActionLabel('Save Gallery')
                                                ->fillForm(fn (Get $get): array => [
                                                    'gallery' => $get('gallery') ?? [],
                                                ])
                                                ->form([
                                                    Forms\Components\FileUpload::make('_gallery_uploader')
                                                        ->label('Upload Gallery Images')
                                                        ->multiple()
                                                        ->directory('destinations/gallery')
                                                        ->disk('public')
                                                        ->image()
                                                        ->maxSize(5120)
                                                        ->dehydrated(false)
                                                        ->helperText('Upload images here. Then create gallery entries below and type the file path (e.g., destinations/gallery/filename.jpg).'),

                                                    Forms\Components\Repeater::make('gallery')
                                                        ->label('Gallery Entries')
                                                        ->addActionLabel('Add gallery image')
                                                        ->schema([
                                                            Forms\Components\TextInput::make('image')
                                                                ->label('Image Path')
                                                                ->required()
                                                                ->placeholder('destinations/gallery/photo.jpg')
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

                                            // --- Must-See Places modal ---
                                            Forms\Components\Actions\Action::make('edit_points_of_interest')
                                                ->label(fn (Get $get): string => 'Must-See Places (' . count($get('points_of_interest') ?? []) . ')')
                                                ->icon('heroicon-o-map-pin')
                                                ->color('danger')
                                                ->modalHeading('Manage Must-See Places')
                                                ->modalDescription('Points of interest displayed on the destination page.')
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
                                        ])->fullWidth(),
                                    ])
                                    ->collapsible(),
                            ])
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->defaultItems(0)
                            ->maxItems(6),
                    ]),
            ]);
    }

    /**
     * Get available icon options for destination content.
     * These map to Lucide icon names used on the frontend.
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

    protected function testimonialsTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Testimonials')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->schema([
                Forms\Components\Section::make('Customer Testimonials')
                    ->description('Manage testimonials displayed in the "Ils ont vécu l\'aventure avec nous" section on the homepage. Maximum 10 testimonials.')
                    ->schema([
                        Forms\Components\Repeater::make('testimonials')
                            ->label('Testimonials')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Customer Name')
                                    ->required()
                                    ->maxLength(100),

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
                                    ->helperText('Square photo recommended. Max 2MB.'),

                                Forms\Components\Textarea::make('text_fr')
                                    ->label('Testimonial (Français)')
                                    ->required()
                                    ->rows(3)
                                    ->maxLength(1000),

                                Forms\Components\Textarea::make('text_en')
                                    ->label('Testimonial (English)')
                                    ->required()
                                    ->rows(3)
                                    ->maxLength(1000),
                            ])
                            ->columns(2)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->defaultItems(0)
                            ->maxItems(10),
                    ]),
            ]);
    }

    // =========================================================================
    // CMS SECTION TABS
    // =========================================================================

    protected function experienceCategoriesTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Experience Categories')
            ->icon('heroicon-o-squares-2x2')
            ->schema([
                Forms\Components\Section::make('Experience Categories Section')
                    ->description('Configure the "Explore Our Experiences" section on the homepage. This section displays activity type categories in a bento grid layout.')
                    ->schema([
                        Forms\Components\Toggle::make('experience_categories_enabled')
                            ->label('Enable Section')
                            ->helperText('Show or hide the experience categories section on the homepage')
                            ->default(true)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Section::make('English')
                                    ->schema([
                                        Forms\Components\TextInput::make('experience_categories_title.en')
                                            ->label('Title')
                                            ->placeholder('Explore Our Experiences')
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('experience_categories_subtitle.en')
                                            ->label('Subtitle')
                                            ->placeholder('Find your perfect adventure')
                                            ->maxLength(200),
                                    ]),
                                Forms\Components\Section::make('Français')
                                    ->schema([
                                        Forms\Components\TextInput::make('experience_categories_title.fr')
                                            ->label('Titre')
                                            ->placeholder('Explorez Nos Expériences')
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('experience_categories_subtitle.fr')
                                            ->label('Sous-titre')
                                            ->placeholder('Trouvez votre aventure parfaite')
                                            ->maxLength(200),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    protected function blogSectionTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Blog Section')
            ->icon('heroicon-o-newspaper')
            ->schema([
                Forms\Components\Section::make('Blog Section')
                    ->description('Configure the blog section displayed on the homepage. Shows featured blog posts.')
                    ->schema([
                        Forms\Components\Toggle::make('blog_section_enabled')
                            ->label('Enable Section')
                            ->helperText('Show or hide the blog section on the homepage')
                            ->default(true),

                        Forms\Components\TextInput::make('blog_section_post_limit')
                            ->label('Number of Posts')
                            ->numeric()
                            ->default(3)
                            ->minValue(1)
                            ->maxValue(12)
                            ->helperText('How many blog posts to display'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Section::make('English')
                                    ->schema([
                                        Forms\Components\TextInput::make('blog_section_title.en')
                                            ->label('Title')
                                            ->placeholder('Latest from the Blog')
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('blog_section_subtitle.en')
                                            ->label('Subtitle')
                                            ->placeholder('Travel tips, stories, and inspiration')
                                            ->maxLength(200),
                                    ]),
                                Forms\Components\Section::make('Français')
                                    ->schema([
                                        Forms\Components\TextInput::make('blog_section_title.fr')
                                            ->label('Titre')
                                            ->placeholder('Dernières actualités')
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('blog_section_subtitle.fr')
                                            ->label('Sous-titre')
                                            ->placeholder('Conseils, récits et inspiration')
                                            ->maxLength(200),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    protected function featuredPackagesTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Featured Packages')
            ->icon('heroicon-o-gift')
            ->schema([
                Forms\Components\Section::make('Featured Packages Section')
                    ->description('Configure the "Upcoming Adventures" section displaying featured listings on the homepage.')
                    ->schema([
                        Forms\Components\Toggle::make('featured_packages_enabled')
                            ->label('Enable Section')
                            ->helperText('Show or hide the featured packages section')
                            ->default(true),

                        Forms\Components\TextInput::make('featured_packages_limit')
                            ->label('Number of Packages')
                            ->numeric()
                            ->default(3)
                            ->minValue(1)
                            ->maxValue(12)
                            ->helperText('How many featured listings to display'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Section::make('English')
                                    ->schema([
                                        Forms\Components\TextInput::make('featured_packages_title.en')
                                            ->label('Title')
                                            ->placeholder('Upcoming Adventures')
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('featured_packages_subtitle.en')
                                            ->label('Subtitle')
                                            ->placeholder('Book your next trip')
                                            ->maxLength(200),
                                    ]),
                                Forms\Components\Section::make('Français')
                                    ->schema([
                                        Forms\Components\TextInput::make('featured_packages_title.fr')
                                            ->label('Titre')
                                            ->placeholder('Aventures à venir')
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('featured_packages_subtitle.fr')
                                            ->label('Sous-titre')
                                            ->placeholder('Réservez votre prochain voyage')
                                            ->maxLength(200),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    protected function customExperienceTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Custom Experience CTA')
            ->icon('heroicon-o-sparkles')
            ->schema([
                Forms\Components\Section::make('Custom Experience CTA Section')
                    ->description('Configure the call-to-action section for custom trip requests. This section encourages users to contact you for personalized experiences.')
                    ->schema([
                        Forms\Components\Toggle::make('custom_experience_enabled')
                            ->label('Enable Section')
                            ->helperText('Show or hide the custom experience CTA section')
                            ->default(true),

                        Forms\Components\TextInput::make('custom_experience_link')
                            ->label('Button Link')
                            ->placeholder('/custom-trip')
                            ->helperText('URL the button links to (e.g., /custom-trip or /contact)')
                            ->maxLength(500),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Section::make('English')
                                    ->schema([
                                        Forms\Components\TextInput::make('custom_experience_title.en')
                                            ->label('Title')
                                            ->placeholder('Create Your Perfect Adventure')
                                            ->maxLength(150),
                                        Forms\Components\Textarea::make('custom_experience_description.en')
                                            ->label('Description')
                                            ->placeholder('Tell us your dream trip and we\'ll make it happen')
                                            ->rows(2)
                                            ->maxLength(500),
                                        Forms\Components\TextInput::make('custom_experience_button_text.en')
                                            ->label('Button Text')
                                            ->placeholder('Get Started')
                                            ->maxLength(50),
                                    ]),
                                Forms\Components\Section::make('Français')
                                    ->schema([
                                        Forms\Components\TextInput::make('custom_experience_title.fr')
                                            ->label('Titre')
                                            ->placeholder('Créez Votre Aventure Sur Mesure')
                                            ->maxLength(150),
                                        Forms\Components\Textarea::make('custom_experience_description.fr')
                                            ->label('Description')
                                            ->placeholder('Décrivez-nous votre voyage de rêve')
                                            ->rows(2)
                                            ->maxLength(500),
                                        Forms\Components\TextInput::make('custom_experience_button_text.fr')
                                            ->label('Texte du bouton')
                                            ->placeholder('Commencer')
                                            ->maxLength(50),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    protected function newsletterTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Newsletter')
            ->icon('heroicon-o-envelope')
            ->schema([
                Forms\Components\Section::make('Newsletter Section')
                    ->description('Configure the newsletter signup section displayed on the homepage or footer.')
                    ->schema([
                        Forms\Components\Toggle::make('newsletter_enabled')
                            ->label('Enable Section')
                            ->helperText('Show or hide the newsletter signup section')
                            ->default(true),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Section::make('English')
                                    ->schema([
                                        Forms\Components\TextInput::make('newsletter_title.en')
                                            ->label('Title')
                                            ->placeholder('Stay Updated')
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('newsletter_subtitle.en')
                                            ->label('Subtitle')
                                            ->placeholder('Get the latest deals and travel tips')
                                            ->maxLength(200),
                                        Forms\Components\TextInput::make('newsletter_button_text.en')
                                            ->label('Button Text')
                                            ->placeholder('Subscribe')
                                            ->maxLength(50),
                                    ]),
                                Forms\Components\Section::make('Français')
                                    ->schema([
                                        Forms\Components\TextInput::make('newsletter_title.fr')
                                            ->label('Titre')
                                            ->placeholder('Restez Informé')
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('newsletter_subtitle.fr')
                                            ->label('Sous-titre')
                                            ->placeholder('Recevez les dernières offres')
                                            ->maxLength(200),
                                        Forms\Components\TextInput::make('newsletter_button_text.fr')
                                            ->label('Texte du bouton')
                                            ->placeholder('S\'abonner')
                                            ->maxLength(50),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    protected function aboutPageTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('About Page')
            ->icon('heroicon-o-user-group')
            ->schema([
                // Hero Section
                Forms\Components\Section::make('Hero Section')
                    ->description('Configure the hero banner at the top of the About page')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('about_hero_image')
                            ->collection('about_hero_image')
                            ->model(fn () => $this->getFormModel())
                            ->label('Hero Background Image')
                            ->image()
                            ->maxSize(5120)
                            ->helperText('Recommended: 1920x600 or similar wide format. Max 5MB.'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Section::make('English')
                                    ->schema([
                                        Forms\Components\TextInput::make('about_hero_tagline.en')
                                            ->label('Tagline')
                                            ->placeholder('Adventure awaits')
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('about_hero_title.en')
                                            ->label('Title')
                                            ->placeholder('About Djerba Fun')
                                            ->maxLength(150),
                                        Forms\Components\Textarea::make('about_hero_subtitle.en')
                                            ->label('Subtitle')
                                            ->placeholder('Our story and mission')
                                            ->rows(2)
                                            ->maxLength(500),
                                    ]),
                                Forms\Components\Section::make('Français')
                                    ->schema([
                                        Forms\Components\TextInput::make('about_hero_tagline.fr')
                                            ->label('Accroche')
                                            ->placeholder('L\'aventure vous attend')
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('about_hero_title.fr')
                                            ->label('Titre')
                                            ->placeholder('À Propos de Djerba Fun')
                                            ->maxLength(150),
                                        Forms\Components\Textarea::make('about_hero_subtitle.fr')
                                            ->label('Sous-titre')
                                            ->placeholder('Notre histoire et notre mission')
                                            ->rows(2)
                                            ->maxLength(500),
                                    ]),
                            ]),
                    ])
                    ->collapsible(),

                // Story Section ("L'Aventurier")
                Forms\Components\Section::make('Story Section')
                    ->description('The "Our Story" / "L\'Aventurier" section with heading and paragraphs')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Section::make('English')
                                    ->schema([
                                        Forms\Components\TextInput::make('about_story_heading.en')
                                            ->label('Heading')
                                            ->placeholder('The Adventurer')
                                            ->maxLength(150),
                                        Forms\Components\Textarea::make('about_story_intro.en')
                                            ->label('Introduction')
                                            ->rows(3)
                                            ->maxLength(1000),
                                        Forms\Components\Textarea::make('about_story_text_1.en')
                                            ->label('Paragraph 1')
                                            ->rows(3)
                                            ->maxLength(1000),
                                        Forms\Components\Textarea::make('about_story_text_2.en')
                                            ->label('Paragraph 2')
                                            ->rows(3)
                                            ->maxLength(1000),
                                    ]),
                                Forms\Components\Section::make('Français')
                                    ->schema([
                                        Forms\Components\TextInput::make('about_story_heading.fr')
                                            ->label('Titre')
                                            ->placeholder('L\'Aventurier')
                                            ->maxLength(150),
                                        Forms\Components\Textarea::make('about_story_intro.fr')
                                            ->label('Introduction')
                                            ->rows(3)
                                            ->maxLength(1000),
                                        Forms\Components\Textarea::make('about_story_text_1.fr')
                                            ->label('Paragraphe 1')
                                            ->rows(3)
                                            ->maxLength(1000),
                                        Forms\Components\Textarea::make('about_story_text_2.fr')
                                            ->label('Paragraphe 2')
                                            ->rows(3)
                                            ->maxLength(1000),
                                    ]),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // Founder Section
                Forms\Components\Section::make('Founder Section')
                    ->description('Information about the company founder')
                    ->schema([
                        Forms\Components\TextInput::make('about_founder_name')
                            ->label('Founder Name')
                            ->placeholder('Seif Ben Helel')
                            ->maxLength(100),

                        Forms\Components\SpatieMediaLibraryFileUpload::make('about_founder_photo')
                            ->collection('about_founder_photo')
                            ->model(fn () => $this->getFormModel())
                            ->label('Founder Photo')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Square photo recommended. Max 2MB.'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Section::make('English')
                                    ->schema([
                                        Forms\Components\Textarea::make('about_founder_story.en')
                                            ->label('Story')
                                            ->rows(4)
                                            ->maxLength(2000),
                                        Forms\Components\Textarea::make('about_founder_quote.en')
                                            ->label('Quote')
                                            ->rows(2)
                                            ->maxLength(500)
                                            ->helperText('A memorable quote from the founder'),
                                    ]),
                                Forms\Components\Section::make('Français')
                                    ->schema([
                                        Forms\Components\Textarea::make('about_founder_story.fr')
                                            ->label('Histoire')
                                            ->rows(4)
                                            ->maxLength(2000),
                                        Forms\Components\Textarea::make('about_founder_quote.fr')
                                            ->label('Citation')
                                            ->rows(2)
                                            ->maxLength(500),
                                    ]),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // Team Section
                Forms\Components\Section::make('Team Section')
                    ->description('Brief description of your team')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Section::make('English')
                                    ->schema([
                                        Forms\Components\TextInput::make('about_team_title.en')
                                            ->label('Title')
                                            ->placeholder('Our Team')
                                            ->maxLength(100),
                                        Forms\Components\Textarea::make('about_team_description.en')
                                            ->label('Description')
                                            ->rows(3)
                                            ->maxLength(1000),
                                    ]),
                                Forms\Components\Section::make('Français')
                                    ->schema([
                                        Forms\Components\TextInput::make('about_team_title.fr')
                                            ->label('Titre')
                                            ->placeholder('Notre Équipe')
                                            ->maxLength(100),
                                        Forms\Components\Textarea::make('about_team_description.fr')
                                            ->label('Description')
                                            ->rows(3)
                                            ->maxLength(1000),
                                    ]),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // Initiatives Text Section (the lime green box)
                Forms\Components\Section::make('Initiatives Text Section')
                    ->description('The green box with community initiatives text and bullet points (displayed next to Team section)')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Section::make('English')
                                    ->schema([
                                        Forms\Components\TextInput::make('about_initiatives_title.en')
                                            ->label('Title')
                                            ->placeholder('Community Initiatives')
                                            ->maxLength(100),
                                        Forms\Components\Textarea::make('about_initiatives_description.en')
                                            ->label('Description')
                                            ->rows(2)
                                            ->maxLength(500)
                                            ->placeholder('We believe in giving back to the communities that make our adventures possible.'),
                                    ]),
                                Forms\Components\Section::make('Français')
                                    ->schema([
                                        Forms\Components\TextInput::make('about_initiatives_title.fr')
                                            ->label('Titre')
                                            ->placeholder('Initiatives Solidaires')
                                            ->maxLength(100),
                                        Forms\Components\Textarea::make('about_initiatives_description.fr')
                                            ->label('Description')
                                            ->rows(2)
                                            ->maxLength(500),
                                    ]),
                            ]),
                        Forms\Components\Repeater::make('about_initiatives_bullets')
                            ->label('Bullet Points')
                            ->schema([
                                Forms\Components\TextInput::make('text_en')
                                    ->label('English')
                                    ->required()
                                    ->maxLength(200),
                                Forms\Components\TextInput::make('text_fr')
                                    ->label('Français')
                                    ->required()
                                    ->maxLength(200),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['text_en'] ?? null)
                            ->defaultItems(0)
                            ->maxItems(6)
                            ->reorderable()
                            ->helperText('Add bullet points that describe your community initiatives'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // Impact Text
                Forms\Components\Section::make('Impact Banner')
                    ->description('The 1% impact banner text')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('about_impact_text.en')
                                    ->label('English')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->placeholder('1% of our revenue goes to local community initiatives...'),
                                Forms\Components\Textarea::make('about_impact_text.fr')
                                    ->label('Français')
                                    ->rows(2)
                                    ->maxLength(500),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // Commitments
                Forms\Components\Section::make('Our Commitments')
                    ->description('Values and commitments displayed as icons with text')
                    ->schema([
                        Forms\Components\Repeater::make('about_commitments')
                            ->label('Commitments')
                            ->schema([
                                Forms\Components\Select::make('icon')
                                    ->label('Icon')
                                    ->options([
                                        'sustainable' => 'Sustainable / Leaf',
                                        'active' => 'Active / Running',
                                        'immersion' => 'Immersion / Globe',
                                        'passion' => 'Passion / Heart',
                                        'quality' => 'Quality / Star',
                                        'safety' => 'Safety / Shield',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('title_en')
                                    ->label('Title (English)')
                                    ->required()
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('title_fr')
                                    ->label('Title (Français)')
                                    ->required()
                                    ->maxLength(100),
                                Forms\Components\Textarea::make('description_en')
                                    ->label('Description (English)')
                                    ->rows(2)
                                    ->maxLength(500),
                                Forms\Components\Textarea::make('description_fr')
                                    ->label('Description (Français)')
                                    ->rows(2)
                                    ->maxLength(500),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title_en'] ?? null)
                            ->defaultItems(0)
                            ->maxItems(6)
                            ->reorderable(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // Partners
                Forms\Components\Section::make('Partners')
                    ->description('Partner logos displayed on the About page')
                    ->schema([
                        Forms\Components\Repeater::make('about_partners')
                            ->label('Partners')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Partner Name')
                                    ->required()
                                    ->maxLength(100),
                                Forms\Components\FileUpload::make('logo')
                                    ->label('Logo')
                                    ->image()
                                    ->directory('about/partners')
                                    ->disk('public')
                                    ->maxSize(1024)
                                    ->helperText('Square or horizontal logo. Max 1MB.'),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->defaultItems(0)
                            ->maxItems(12)
                            ->reorderable(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // Initiatives
                Forms\Components\Section::make('Initiatives')
                    ->description('Initiative images displayed in a grid on the About page')
                    ->schema([
                        Forms\Components\Repeater::make('about_initiatives')
                            ->label('Initiatives')
                            ->schema([
                                Forms\Components\FileUpload::make('image')
                                    ->label('Image')
                                    ->image()
                                    ->directory('about/initiatives')
                                    ->disk('public')
                                    ->maxSize(5120)
                                    ->required()
                                    ->helperText('Landscape format recommended. Max 5MB.'),
                                Forms\Components\TextInput::make('alt_en')
                                    ->label('Alt Text (English)')
                                    ->required()
                                    ->maxLength(200),
                                Forms\Components\TextInput::make('alt_fr')
                                    ->label('Alt Text (Français)')
                                    ->required()
                                    ->maxLength(200),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['alt_en'] ?? null)
                            ->defaultItems(0)
                            ->maxItems(6)
                            ->reorderable(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    protected function seoMetadataTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('SEO & Metadata')
            ->icon('heroicon-o-magnifying-glass')
            ->schema([
                Forms\Components\Section::make('Default Meta Tags')
                    ->description('Default SEO values used when pages don\'t specify their own')
                    ->schema([
                        Forms\Components\Tabs::make('SEO Translations')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('English')
                                    ->schema([
                                        Forms\Components\TextInput::make('meta_title.en')
                                            ->label('Meta Title')
                                            ->maxLength(60)
                                            ->helperText('Recommended: 50-60 characters'),
                                        Forms\Components\Textarea::make('meta_description.en')
                                            ->label('Meta Description')
                                            ->rows(2)
                                            ->maxLength(160)
                                            ->helperText('Recommended: 150-160 characters'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('French')
                                    ->schema([
                                        Forms\Components\TextInput::make('meta_title.fr')
                                            ->label('Titre Meta')
                                            ->maxLength(60),
                                        Forms\Components\Textarea::make('meta_description.fr')
                                            ->label('Description Meta')
                                            ->rows(2)
                                            ->maxLength(160),
                                    ]),
                                Forms\Components\Tabs\Tab::make('Arabic')
                                    ->schema([
                                        Forms\Components\TextInput::make('meta_title.ar')
                                            ->label('عنوان الميتا')
                                            ->maxLength(60),
                                        Forms\Components\Textarea::make('meta_description.ar')
                                            ->label('وصف الميتا')
                                            ->rows(2)
                                            ->maxLength(160),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Additional SEO')
                    ->schema([
                        Forms\Components\TagsInput::make('keywords')
                            ->label('Keywords')
                            ->placeholder('Add keyword')
                            ->helperText('Press Enter to add each keyword'),
                        Forms\Components\TextInput::make('author')
                            ->label('Author')
                            ->maxLength(100),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Schema.org')
                    ->description('Structured data for search engines')
                    ->schema([
                        Forms\Components\Select::make('organization_type')
                            ->label('Organization Type')
                            ->options([
                                'TravelAgency' => 'Travel Agency',
                                'LocalBusiness' => 'Local Business',
                                'Organization' => 'Organization',
                                'TouristInformationCenter' => 'Tourist Information Center',
                            ])
                            ->default('TravelAgency'),
                        Forms\Components\TextInput::make('founded_year')
                            ->label('Founded Year')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(date('Y')),
                    ])
                    ->columns(2),
            ]);
    }

    protected function contactInformationTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Contact')
            ->icon('heroicon-o-phone')
            ->schema([
                Forms\Components\Section::make('Contact Information')
                    ->description('Primary contact details')
                    ->schema([
                        Forms\Components\TextInput::make('support_email')
                            ->label('Support Email')
                            ->email()
                            ->placeholder('support@evasiondjerba.com'),
                        Forms\Components\TextInput::make('general_email')
                            ->label('General Inquiries Email')
                            ->email()
                            ->placeholder('hello@evasiondjerba.com'),
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->tel()
                            ->placeholder('+216 XX XXX XXX'),
                        Forms\Components\TextInput::make('whatsapp_number')
                            ->label('WhatsApp Number')
                            ->tel()
                            ->placeholder('+216 XX XXX XXX'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Business Hours')
                    ->description('Operating hours (optional)')
                    ->schema([
                        Forms\Components\KeyValue::make('business_hours')
                            ->label('Business Hours')
                            ->keyLabel('Day')
                            ->valueLabel('Hours')
                            ->addActionLabel('Add Day')
                            ->helperText('e.g., Monday: 9:00 AM - 6:00 PM'),
                    ])
                    ->collapsed(),
            ]);
    }

    protected function physicalAddressTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Address')
            ->icon('heroicon-o-map-pin')
            ->schema([
                Forms\Components\Section::make('Physical Address')
                    ->description('Business address for footer, contact page, and schema.org')
                    ->schema([
                        Forms\Components\TextInput::make('address_street')
                            ->label('Street Address')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('address_city')
                            ->label('City'),
                        Forms\Components\TextInput::make('address_region')
                            ->label('State/Region'),
                        Forms\Components\TextInput::make('address_postal_code')
                            ->label('Postal Code'),
                        Forms\Components\Select::make('address_country')
                            ->label('Country')
                            ->options([
                                'TN' => 'Tunisia',
                                'FR' => 'France',
                                'MA' => 'Morocco',
                                'DZ' => 'Algeria',
                                'US' => 'United States',
                                'GB' => 'United Kingdom',
                                'DE' => 'Germany',
                            ])
                            ->searchable()
                            ->default('TN'),
                        Forms\Components\TextInput::make('google_maps_url')
                            ->label('Google Maps URL')
                            ->url()
                            ->columnSpanFull()
                            ->placeholder('https://maps.google.com/...'),
                    ])
                    ->columns(2),
            ]);
    }

    protected function socialMediaTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Social Media')
            ->icon('heroicon-o-share')
            ->schema([
                Forms\Components\Section::make('Social Media Profiles')
                    ->description('Links displayed in footer and used in schema.org sameAs')
                    ->schema([
                        Forms\Components\TextInput::make('social_facebook')
                            ->label('Facebook')
                            ->url()
                            ->placeholder('https://facebook.com/evasiondjerba'),
                        Forms\Components\TextInput::make('social_instagram')
                            ->label('Instagram')
                            ->url()
                            ->placeholder('https://instagram.com/evasiondjerba'),
                        Forms\Components\TextInput::make('social_twitter')
                            ->label('Twitter/X')
                            ->url()
                            ->placeholder('https://twitter.com/evasiondjerba'),
                        Forms\Components\TextInput::make('social_linkedin')
                            ->label('LinkedIn')
                            ->url()
                            ->placeholder('https://linkedin.com/company/evasiondjerba'),
                        Forms\Components\TextInput::make('social_youtube')
                            ->label('YouTube')
                            ->url()
                            ->placeholder('https://youtube.com/@evasiondjerba'),
                        Forms\Components\TextInput::make('social_tiktok')
                            ->label('TikTok')
                            ->url()
                            ->placeholder('https://tiktok.com/@evasiondjerba'),
                    ])
                    ->columns(2),
            ]);
    }

    protected function emailSettingsTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Email')
            ->icon('heroicon-o-envelope')
            ->schema([
                Forms\Components\Section::make('Email Configuration')
                    ->description('Settings for outgoing emails')
                    ->schema([
                        Forms\Components\TextInput::make('email_from_name')
                            ->label('From Name')
                            ->placeholder('Evasion Djerba'),
                        Forms\Components\TextInput::make('email_from_address')
                            ->label('From Email')
                            ->email()
                            ->placeholder('noreply@evasiondjerba.com'),
                        Forms\Components\TextInput::make('email_reply_to')
                            ->label('Reply-To Email')
                            ->email()
                            ->placeholder('support@evasiondjerba.com'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Email Footer Links')
                    ->description('Links included in email footers')
                    ->schema([
                        Forms\Components\TextInput::make('email_terms_url')
                            ->label('Terms URL')
                            ->url()
                            ->placeholder('https://evasiondjerba.com/terms'),
                        Forms\Components\TextInput::make('email_privacy_url')
                            ->label('Privacy URL')
                            ->url()
                            ->placeholder('https://evasiondjerba.com/privacy'),
                    ])
                    ->columns(2),
            ]);
    }

    protected function paymentCommerceTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Payment')
            ->icon('heroicon-o-credit-card')
            ->schema([
                Forms\Components\Section::make('Currency Settings')
                    ->schema([
                        Forms\Components\Select::make('default_currency')
                            ->label('Default Currency')
                            ->options([
                                'TND' => 'Tunisian Dinar (TND)',
                                'EUR' => 'Euro (EUR)',
                                'USD' => 'US Dollar (USD)',
                                'GBP' => 'British Pound (GBP)',
                            ])
                            ->default('TND'),
                        Forms\Components\CheckboxList::make('enabled_currencies')
                            ->label('Enabled Currencies')
                            ->options([
                                'EUR' => 'Euro (EUR)',
                                'TND' => 'Tunisian Dinar (TND)',
                                'USD' => 'US Dollar (USD)',
                                'GBP' => 'British Pound (GBP)',
                            ])
                            ->columns(2),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Exchange Rates & PPP')
                    ->description('Configure exchange rates and purchasing power parity adjustments')
                    ->schema([
                        Forms\Components\TextInput::make('eur_to_tnd_rate')
                            ->label('EUR → TND Exchange Rate')
                            ->numeric()
                            ->step(0.0001)
                            ->default(3.3000)
                            ->required()
                            ->helperText('How many TND for 1 EUR. Update this daily. Used for ClikToPay payment display to show EUR users the TND equivalent.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('exchange_rate_api_key')
                            ->label('Exchange Rate API Key')
                            ->password()
                            ->revealable()
                            ->helperText('API key for exchangeratesapi.io or similar service')
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('ppp_factor_eur')
                                    ->label('EUR PPP Factor')
                                    ->numeric()
                                    ->step(0.0001)
                                    ->default(0.8500)
                                    ->helperText('TND to EUR purchasing power adjustment (e.g., 0.85 = 15% lower)'),

                                Forms\Components\TextInput::make('ppp_factor_usd')
                                    ->label('USD PPP Factor')
                                    ->numeric()
                                    ->step(0.0001)
                                    ->default(0.8200)
                                    ->helperText('TND to USD purchasing power adjustment'),

                                Forms\Components\TextInput::make('ppp_factor_gbp')
                                    ->label('GBP PPP Factor')
                                    ->numeric()
                                    ->step(0.0001)
                                    ->default(0.8500)
                                    ->helperText('TND to GBP purchasing power adjustment'),
                            ]),

                        Forms\Components\Placeholder::make('exchange_rates_info')
                            ->label('Current Exchange Rates')
                            ->content(function () {
                                $conversionService = app(\App\Services\CurrencyConversionService::class);
                                $rates = $conversionService->getAllRates();

                                if (empty($rates)) {
                                    return 'No exchange rates available. Run: php artisan exchange-rates:update';
                                }

                                $content = '';

                                foreach ($rates as $currency => $rateInfo) {
                                    if ($rateInfo) {
                                        $lastUpdate = \Carbon\Carbon::parse($rateInfo['updated_at'])->diffForHumans();
                                        $content .= "**{$currency}**: Rate: {$rateInfo['rate']} | PPP: {$rateInfo['ppp_adjustment']} | Updated: {$lastUpdate}\n\n";
                                    }
                                }

                                return new \Illuminate\Support\HtmlString(str($content)->markdown());
                            })
                            ->columnSpanFull(),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('update_rates')
                                ->label('Update Exchange Rates Now')
                                ->icon('heroicon-o-arrow-path')
                                ->color('success')
                                ->action(function () {
                                    try {
                                        $conversionService = app(\App\Services\CurrencyConversionService::class);
                                        $updated = $conversionService->updateRatesFromAPI();

                                        \Filament\Notifications\Notification::make()
                                            ->success()
                                            ->title('Exchange rates updated successfully')
                                            ->body(count($updated) . ' currencies updated')
                                            ->send();
                                    } catch (\Exception $e) {
                                        \Filament\Notifications\Notification::make()
                                            ->danger()
                                            ->title('Failed to update exchange rates')
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                })
                                ->requiresConfirmation()
                                ->modalHeading('Update Exchange Rates')
                                ->modalDescription('This will fetch the latest exchange rates from the API. Continue?'),
                        ])
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Forms\Components\Section::make('Payment Gateway')
                    ->schema([
                        Forms\Components\CheckboxList::make('enabled_payment_methods')
                            ->label('Enabled Payment Methods')
                            ->options([
                                'offline' => 'Virement Bancaire (Bank Transfer)',
                                'cash' => 'Espèces à l\'Arrivée (Cash on Arrival)',
                                'click_to_pay' => 'Clictopay (Paiement par Carte)',
                            ])
                            ->columns(3)
                            ->helperText('Select which payment methods are available to customers on checkout.'),
                    ]),

                Forms\Components\Section::make('Bank Transfer Details')
                    ->description('Configure the bank account details shown to customers who choose bank transfer.')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('bank_transfer_bank_name')
                            ->label('Bank Name')
                            ->placeholder('e.g. Banque Nationale Agricole')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('bank_transfer_account_holder')
                            ->label('Account Holder Name')
                            ->placeholder('e.g. Evasion Djerba SARL')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('bank_transfer_iban')
                            ->label('IBAN')
                            ->placeholder('e.g. TN59 1000 6035 1835 9847 8831')
                            ->maxLength(34),
                        Forms\Components\TextInput::make('bank_transfer_swift_bic')
                            ->label('SWIFT / BIC Code')
                            ->placeholder('e.g. BNATNTTXXX')
                            ->maxLength(11),
                        Forms\Components\TextInput::make('bank_transfer_account_number')
                            ->label('Account Number (RIB)')
                            ->placeholder('e.g. 10006035183598478831')
                            ->maxLength(30),
                        Forms\Components\Textarea::make('bank_transfer_instructions')
                            ->label('Additional Instructions')
                            ->placeholder('e.g. Please include your booking reference in the transfer description.')
                            ->rows(3),
                    ]),

                Forms\Components\Section::make('Fees & Limits')
                    ->schema([
                        Forms\Components\TextInput::make('platform_commission_percent')
                            ->label('Platform Commission (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(15.00),
                        Forms\Components\TextInput::make('payment_processing_fee_percent')
                            ->label('Processing Fee (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(2.50),
                        Forms\Components\TextInput::make('min_booking_amount')
                            ->label('Minimum Booking Amount')
                            ->numeric()
                            ->prefix('TND')
                            ->default(10.00),
                        Forms\Components\TextInput::make('max_booking_amount')
                            ->label('Maximum Booking Amount')
                            ->numeric()
                            ->prefix('TND')
                            ->default(50000.00),
                    ])
                    ->columns(2),
            ]);
    }

    protected function bookingSettingsTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Booking')
            ->icon('heroicon-o-calendar')
            ->schema([
                Forms\Components\Section::make('Hold Settings')
                    ->description('Configure booking hold behavior')
                    ->schema([
                        Forms\Components\TextInput::make('hold_duration_minutes')
                            ->label('Hold Duration')
                            ->numeric()
                            ->suffix('minutes')
                            ->default(15)
                            ->helperText('How long a booking slot is held during checkout'),
                        Forms\Components\TextInput::make('hold_warning_minutes')
                            ->label('Warning Time')
                            ->numeric()
                            ->suffix('minutes')
                            ->default(3)
                            ->helperText('When to show expiration warning'),
                        Forms\Components\TextInput::make('auto_cancel_hours')
                            ->label('Auto-Cancel Period')
                            ->numeric()
                            ->suffix('hours')
                            ->default(24)
                            ->helperText('Cancel unpaid bookings after this period'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Default Cancellation Policy')
                    ->description('Default values for new listings')
                    ->schema([
                        Forms\Components\KeyValue::make('default_cancellation_policy')
                            ->label('Policy Values')
                            ->keyLabel('Setting')
                            ->valueLabel('Value')
                            ->addActionLabel('Add Setting'),
                    ])
                    ->collapsed(),
            ]);
    }

    protected function localizationTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Localization')
            ->icon('heroicon-o-language')
            ->schema([
                Forms\Components\Section::make('Language Settings')
                    ->schema([
                        Forms\Components\Select::make('default_locale')
                            ->label('Default Language')
                            ->options([
                                'en' => 'English',
                                'fr' => 'French',
                                'ar' => 'Arabic',
                            ])
                            ->default('en'),
                        Forms\Components\Select::make('fallback_locale')
                            ->label('Fallback Language')
                            ->options([
                                'en' => 'English',
                                'fr' => 'French',
                            ])
                            ->default('en'),
                        Forms\Components\CheckboxList::make('available_locales')
                            ->label('Available Languages')
                            ->options([
                                'en' => 'English',
                                'fr' => 'French',
                                'ar' => 'Arabic',
                            ])
                            ->columns(3),
                        Forms\Components\CheckboxList::make('rtl_locales')
                            ->label('RTL Languages')
                            ->options([
                                'ar' => 'Arabic',
                                'he' => 'Hebrew',
                            ])
                            ->columns(3),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Date & Time')
                    ->schema([
                        Forms\Components\Select::make('date_format')
                            ->label('Date Format')
                            ->options([
                                'd/m/Y' => 'DD/MM/YYYY (31/12/2024)',
                                'm/d/Y' => 'MM/DD/YYYY (12/31/2024)',
                                'Y-m-d' => 'YYYY-MM-DD (2024-12-31)',
                                'd M Y' => 'DD Mon YYYY (31 Dec 2024)',
                            ])
                            ->default('d/m/Y'),
                        Forms\Components\Select::make('time_format')
                            ->label('Time Format')
                            ->options([
                                '24h' => '24-hour (14:30)',
                                '12h' => '12-hour (2:30 PM)',
                            ])
                            ->default('24h'),
                        Forms\Components\Select::make('timezone')
                            ->label('Timezone')
                            ->options([
                                'Africa/Tunis' => 'Africa/Tunis (CET)',
                                'Europe/Paris' => 'Europe/Paris (CET)',
                                'UTC' => 'UTC',
                            ])
                            ->searchable()
                            ->default('Africa/Tunis'),
                        Forms\Components\Select::make('week_starts_on')
                            ->label('Week Starts On')
                            ->options([
                                'monday' => 'Monday',
                                'sunday' => 'Sunday',
                                'saturday' => 'Saturday',
                            ])
                            ->default('monday'),
                    ])
                    ->columns(2),
            ]);
    }

    protected function featureFlagsTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Features')
            ->icon('heroicon-o-puzzle-piece')
            ->schema([
                Forms\Components\Section::make('Core Features')
                    ->description('Enable or disable platform features')
                    ->schema([
                        Forms\Components\Toggle::make('enable_reviews')
                            ->label('Reviews')
                            ->helperText('Allow users to review bookings'),
                        Forms\Components\Toggle::make('enable_wishlists')
                            ->label('Wishlists')
                            ->helperText('Allow users to save listings'),
                        Forms\Components\Toggle::make('enable_gift_cards')
                            ->label('Gift Cards')
                            ->helperText('Enable gift card purchases'),
                        Forms\Components\Toggle::make('enable_loyalty_program')
                            ->label('Loyalty Program')
                            ->helperText('Points and rewards system'),
                        Forms\Components\Toggle::make('enable_partner_api')
                            ->label('Partner API')
                            ->helperText('API access for travel partners'),
                        Forms\Components\Toggle::make('enable_blog')
                            ->label('Blog')
                            ->helperText('Blog/content section'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Booking Features')
                    ->schema([
                        Forms\Components\Toggle::make('enable_instant_booking')
                            ->label('Instant Booking')
                            ->helperText('Book without vendor approval'),
                        Forms\Components\Toggle::make('enable_request_to_book')
                            ->label('Request to Book')
                            ->helperText('Require vendor approval'),
                        Forms\Components\Toggle::make('enable_group_bookings')
                            ->label('Group Bookings')
                            ->helperText('Large group bookings'),
                        Forms\Components\Toggle::make('enable_custom_packages')
                            ->label('Custom Packages')
                            ->helperText('Multi-listing packages'),
                    ])
                    ->columns(2),
            ]);
    }

    protected function analyticsTrackingTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Analytics')
            ->icon('heroicon-o-chart-bar')
            ->schema([
                Forms\Components\Section::make('Google Services')
                    ->schema([
                        Forms\Components\TextInput::make('ga4_measurement_id')
                            ->label('Google Analytics 4 ID')
                            ->placeholder('G-XXXXXXXXXX'),
                        Forms\Components\TextInput::make('gtm_container_id')
                            ->label('Google Tag Manager ID')
                            ->placeholder('GTM-XXXXXXX'),
                        Forms\Components\TextInput::make('google_search_console_verification')
                            ->label('Search Console Verification')
                            ->placeholder('verification code'),
                        Forms\Components\TextInput::make('google_maps_api_key')
                            ->label('Google Maps API Key')
                            ->password()
                            ->helperText('Encrypted at rest'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Other Analytics')
                    ->schema([
                        Forms\Components\TextInput::make('facebook_pixel_id')
                            ->label('Facebook Pixel ID'),
                        Forms\Components\TextInput::make('hotjar_site_id')
                            ->label('Hotjar Site ID'),
                        Forms\Components\TextInput::make('plausible_domain')
                            ->label('Plausible Domain')
                            ->placeholder('evasiondjerba.com'),
                        Forms\Components\TextInput::make('sentry_dsn')
                            ->label('Sentry DSN')
                            ->password()
                            ->helperText('Error tracking - encrypted'),
                    ])
                    ->columns(2),
            ]);
    }

    protected function legalComplianceTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Legal')
            ->icon('heroicon-o-shield-check')
            ->schema([
                Forms\Components\Section::make('Legal Pages')
                    ->description('URLs to legal documents')
                    ->schema([
                        Forms\Components\TextInput::make('terms_url')
                            ->label('Terms of Service URL')
                            ->url(),
                        Forms\Components\TextInput::make('privacy_url')
                            ->label('Privacy Policy URL')
                            ->url(),
                        Forms\Components\TextInput::make('cookie_policy_url')
                            ->label('Cookie Policy URL')
                            ->url(),
                        Forms\Components\TextInput::make('refund_policy_url')
                            ->label('Refund Policy URL')
                            ->url(),
                        Forms\Components\TextInput::make('data_deletion_policy_url')
                            ->label('Data Deletion Policy URL')
                            ->url(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Compliance Settings')
                    ->schema([
                        Forms\Components\Toggle::make('cookie_consent_enabled')
                            ->label('Cookie Consent Banner')
                            ->helperText('Show cookie consent popup'),
                        Forms\Components\Toggle::make('gdpr_mode_enabled')
                            ->label('GDPR Mode')
                            ->helperText('Enable GDPR features'),
                        Forms\Components\TextInput::make('data_retention_days')
                            ->label('Data Retention Period')
                            ->numeric()
                            ->suffix('days')
                            ->default(730)
                            ->helperText('Auto-delete old data after'),
                        Forms\Components\TextInput::make('minimum_age_requirement')
                            ->label('Minimum Age')
                            ->numeric()
                            ->suffix('years')
                            ->default(18),
                    ])
                    ->columns(2),
            ]);
    }

    protected function vendorSettingsTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Vendors')
            ->icon('heroicon-o-building-storefront')
            ->schema([
                Forms\Components\Section::make('Vendor Onboarding')
                    ->schema([
                        Forms\Components\Toggle::make('vendor_auto_approve')
                            ->label('Auto-Approve Vendors')
                            ->helperText('Skip manual approval process'),
                        Forms\Components\Toggle::make('vendor_require_kyc')
                            ->label('Require KYC')
                            ->helperText('Mandatory identity verification'),
                        Forms\Components\CheckboxList::make('vendor_kyc_document_types')
                            ->label('Required KYC Documents')
                            ->options([
                                'id_proof' => 'ID Proof (Passport/ID Card)',
                                'business_license' => 'Business License',
                                'tax_id' => 'Tax ID',
                                'address_proof' => 'Address Proof',
                            ])
                            ->columns(2),
                    ]),

                Forms\Components\Section::make('Payout Settings')
                    ->schema([
                        Forms\Components\TextInput::make('vendor_commission_rate')
                            ->label('Commission Rate')
                            ->numeric()
                            ->suffix('%')
                            ->default(15.00),
                        Forms\Components\Select::make('vendor_payout_frequency')
                            ->label('Payout Frequency')
                            ->options([
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'biweekly' => 'Bi-weekly',
                                'monthly' => 'Monthly',
                            ])
                            ->default('weekly'),
                        Forms\Components\TextInput::make('vendor_payout_minimum')
                            ->label('Minimum Payout')
                            ->numeric()
                            ->prefix('TND')
                            ->default(50.00),
                        Forms\Components\Select::make('vendor_payout_currency')
                            ->label('Payout Currency')
                            ->options([
                                'TND' => 'Tunisian Dinar (TND)',
                                'EUR' => 'Euro (EUR)',
                                'USD' => 'US Dollar (USD)',
                            ])
                            ->default('TND'),
                        Forms\Components\TextInput::make('vendor_payout_delay_days')
                            ->label('Payout Delay')
                            ->numeric()
                            ->suffix('days')
                            ->default(7)
                            ->helperText('Days after booking completion'),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Sanitize KeyValue fields to ensure values are strings, not arrays.
     * This fixes data corruption issues where nested arrays were saved.
     */
    protected function sanitizeKeyValueFields(array $data): array
    {
        $keyValueFields = ['business_hours', 'default_cancellation_policy'];

        foreach ($keyValueFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = array_map(function ($value) {
                    if (is_array($value)) {
                        return json_encode($value);
                    }
                    return (string) $value;
                }, $data[$field]);
            }
        }

        return $data;
    }

    public function save(): void
    {
        try {
            // Defensive check - ensure model exists after Livewire hydration
            // This handles the case where $settings was null due to serialization
            if ($this->settings === null) {
                $this->settings = PlatformSettings::first();

                if (! $this->settings) {
                    $this->settings = PlatformSettings::create([]);
                }
            }

            // Capture nested destination content BEFORE getState() strips non-schema keys.
            // Modal $set() calls update $this->data, but getState() only returns fields
            // defined in the repeater schema (name, id, description, image, link, seo_*).
            $nestedKeys = ['highlights', 'key_facts', 'gallery', 'points_of_interest'];
            $nestedDataBySlug = [];

            // Source 1: Livewire state — has any modal edits from this session
            foreach (($this->data['featured_destinations'] ?? []) as $rawDest) {
                if (! is_array($rawDest)) {
                    continue;
                }
                $slug = $rawDest['id'] ?? null;
                if (! $slug) {
                    continue;
                }
                foreach ($nestedKeys as $key) {
                    if (isset($rawDest[$key]) && is_array($rawDest[$key]) && ! empty($rawDest[$key])) {
                        $nestedDataBySlug[$slug][$key] = $rawDest[$key];
                    }
                }
            }

            // Source 2: Database — fallback for data not edited this session
            foreach (($this->settings->featured_destinations ?? []) as $dbDest) {
                if (! is_array($dbDest)) {
                    continue;
                }
                $slug = $dbDest['id'] ?? null;
                if (! $slug) {
                    continue;
                }
                foreach ($nestedKeys as $key) {
                    if (! isset($nestedDataBySlug[$slug][$key]) && isset($dbDest[$key])) {
                        $nestedDataBySlug[$slug][$key] = $dbDest[$key];
                    }
                }
            }

            // NOW get form state (may strip non-schema keys from $this->data)
            $data = $this->form->getState();

            // Sanitize KeyValue fields to prevent array-as-value errors
            $data = $this->sanitizeKeyValueFields($data);

            // Filter out null values to preserve existing database values
            // This prevents NOT NULL constraint violations when form fields are empty
            // Fields like organization_type, address_country, etc. have defaults but NOT nullable
            $data = $this->filterNullValues($data);

            // Merge captured nested data back into form state
            if (isset($data['featured_destinations'])) {
                foreach ($data['featured_destinations'] as &$dest) {
                    $slug = $dest['id'] ?? null;
                    if (! $slug || ! isset($nestedDataBySlug[$slug])) {
                        continue;
                    }
                    foreach ($nestedKeys as $key) {
                        if (isset($nestedDataBySlug[$slug][$key])) {
                            $dest[$key] = $nestedDataBySlug[$slug][$key];
                        }
                    }
                }
                unset($dest);
            }

            // Fill model with scalar data and save
            $this->settings->fill($data);
            $this->settings->save();

            // CRITICAL FIX: Save relationships (media uploads) BEFORE refreshing the model
            // The SpatieMediaLibraryFileUpload component holds references to temporary files
            // in the form state that are bound to the CURRENT model instance ($this->settings).
            // Calling fresh() before saveRelationships() breaks this binding because:
            // 1. fresh() creates a NEW model instance from the database
            // 2. The form's internal upload state was bound to the ORIGINAL instance
            // 3. saveRelationships() can't find the uploads because the binding is lost
            //
            // By calling saveRelationships() FIRST, while the original model instance
            // is still bound, the uploads are properly moved from temp to permanent storage
            // and associated with the model via Spatie Media Library.
            $this->form->saveRelationships();

            // NOW it's safe to refresh the model to get the latest data including
            // any media that was just attached by saveRelationships()
            $this->settings->refresh();
            $this->settings->load('media');

            // Clear cache so API returns fresh data
            PlatformSettings::clearCache();

            Notification::make()
                ->title('Settings saved successfully')
                ->success()
                ->send();

        } catch (Halt $exception) {
            return;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error saving settings')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('form')
                ->keyBindings(['mod+s']),
        ];
    }

    /**
     * Filter out null values from data array to preserve existing database values.
     *
     * This prevents NOT NULL constraint violations when form fields return null.
     * Fields like organization_type, address_country, default_currency have database
     * defaults but are NOT nullable - passing null would cause SQL errors.
     *
     * Note: This intentionally keeps empty strings and false booleans, only filtering null.
     */
    protected function filterNullValues(array $data): array
    {
        return array_filter($data, function ($value) {
            // Keep all non-null values including:
            // - Empty strings (explicit empty)
            // - False booleans
            // - Zero values
            // - Empty arrays
            return $value !== null;
        });
    }
}
