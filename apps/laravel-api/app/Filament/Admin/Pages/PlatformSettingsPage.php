<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Models\PlatformSettings;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
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
                            ->placeholder('https://goadventure.com'),
                        Forms\Components\TextInput::make('api_url')
                            ->label('API URL')
                            ->url()
                            ->placeholder('https://api.goadventure.com'),
                        Forms\Components\TextInput::make('frontend_url')
                            ->label('Frontend URL')
                            ->url()
                            ->placeholder('https://goadventure.com'),
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
                            ->label('Logo (Light Mode)')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Used on light backgrounds'),
                        Forms\Components\SpatieMediaLibraryFileUpload::make('logo_dark')
                            ->collection('logo_dark')
                            ->label('Logo (Dark Mode)')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Used on dark backgrounds'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Favicons & Icons')
                    ->description('Upload favicon and app icons')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('favicon')
                            ->collection('favicon')
                            ->label('Favicon')
                            ->image()
                            ->maxSize(512)
                            ->helperText('Recommended: 32x32 or 16x16 PNG/ICO'),
                        Forms\Components\SpatieMediaLibraryFileUpload::make('apple_touch_icon')
                            ->collection('apple_touch_icon')
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
                            ->label('OG Image')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Recommended: 1200x630 PNG/JPG'),
                    ]),

                Forms\Components\Section::make('Hero Banner')
                    ->description('Main banner image displayed on the homepage hero section')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('hero_banner')
                            ->collection('hero_banner')
                            ->label('Hero Banner Image')
                            ->image()
                            ->maxSize(10240)
                            ->helperText('Recommended: 1920x1080 or larger. Max 10MB. JPG/PNG/WebP.'),
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
                            ->label('Pillar 1: Sustainable Travel')
                            ->image()
                            ->maxSize(5120)
                            ->helperText('Recommended: 1080x1080 square image'),
                        Forms\Components\SpatieMediaLibraryFileUpload::make('brand_pillar_2')
                            ->collection('brand_pillar_2')
                            ->label('Pillar 2: Authentic Experiences')
                            ->image()
                            ->maxSize(5120)
                            ->helperText('Recommended: 1080x1080 square image'),
                        Forms\Components\SpatieMediaLibraryFileUpload::make('brand_pillar_3')
                            ->collection('brand_pillar_3')
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
                            ->placeholder('https://goadventure.com/events/festival-2025')
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
                            ->label('Event Banner Image')
                            ->image()
                            ->maxSize(5120)
                            ->helperText('Recommended: 1200x600 or 16:9 aspect ratio. Max 5MB.'),
                    ]),
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
                            ->placeholder('support@goadventure.com'),
                        Forms\Components\TextInput::make('general_email')
                            ->label('General Inquiries Email')
                            ->email()
                            ->placeholder('hello@goadventure.com'),
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
                            ->placeholder('https://facebook.com/goadventure'),
                        Forms\Components\TextInput::make('social_instagram')
                            ->label('Instagram')
                            ->url()
                            ->placeholder('https://instagram.com/goadventure'),
                        Forms\Components\TextInput::make('social_twitter')
                            ->label('Twitter/X')
                            ->url()
                            ->placeholder('https://twitter.com/goadventure'),
                        Forms\Components\TextInput::make('social_linkedin')
                            ->label('LinkedIn')
                            ->url()
                            ->placeholder('https://linkedin.com/company/goadventure'),
                        Forms\Components\TextInput::make('social_youtube')
                            ->label('YouTube')
                            ->url()
                            ->placeholder('https://youtube.com/@goadventure'),
                        Forms\Components\TextInput::make('social_tiktok')
                            ->label('TikTok')
                            ->url()
                            ->placeholder('https://tiktok.com/@goadventure'),
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
                            ->placeholder('Go Adventure'),
                        Forms\Components\TextInput::make('email_from_address')
                            ->label('From Email')
                            ->email()
                            ->placeholder('noreply@goadventure.com'),
                        Forms\Components\TextInput::make('email_reply_to')
                            ->label('Reply-To Email')
                            ->email()
                            ->placeholder('support@goadventure.com'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Email Footer Links')
                    ->description('Links included in email footers')
                    ->schema([
                        Forms\Components\TextInput::make('email_terms_url')
                            ->label('Terms URL')
                            ->url()
                            ->placeholder('https://goadventure.com/terms'),
                        Forms\Components\TextInput::make('email_privacy_url')
                            ->label('Privacy URL')
                            ->url()
                            ->placeholder('https://goadventure.com/privacy'),
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
                        Forms\Components\Select::make('default_payment_gateway')
                            ->label('Default Gateway')
                            ->options([
                                'mock' => 'Mock (Development)',
                                'stripe' => 'Stripe',
                                'offline' => 'Offline/Bank Transfer',
                            ])
                            ->default('mock'),
                        Forms\Components\CheckboxList::make('enabled_payment_methods')
                            ->label('Enabled Payment Methods')
                            ->options([
                                'card' => 'Credit/Debit Card',
                                'bank_transfer' => 'Bank Transfer',
                                'cash' => 'Cash on Delivery',
                            ])
                            ->columns(3),
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
                            ->placeholder('goadventure.com'),
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

            // Validate and get form state
            $data = $this->form->getState();

            // Sanitize KeyValue fields to prevent array-as-value errors
            $data = $this->sanitizeKeyValueFields($data);

            // Fill model with scalar data and save
            $this->settings->fill($data);
            $this->settings->save();

            // CRITICAL: Explicitly save relationships (media uploads)
            // This must happen AFTER model save so media has a model_id to associate with
            // SpatieMediaLibraryFileUpload stores uploads in temp storage during form interaction
            // This call moves them to permanent storage and creates media table records
            $this->form->model($this->settings)->saveRelationships();

            // Clear cache so API returns fresh data
            PlatformSettings::clearCache();

            // Refresh model with new media
            $this->settings->refresh();
            $this->settings->load('media');

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
}
