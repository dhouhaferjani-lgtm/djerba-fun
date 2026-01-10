<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Models\PlatformSettings;
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

    protected ?PlatformSettings $record = null;

    public function mount(): void
    {
        $this->record = PlatformSettings::instance();
        $this->form->fill($this->record->toArray());
    }

    public function getRecord(): PlatformSettings
    {
        return $this->record ??= PlatformSettings::instance();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->tabs([
                        $this->platformIdentityTab(),
                        $this->logoBrandingTab(),
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
                            ->model(fn () => $this->getRecord())
                            ->label('Logo (Light Mode)')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Used on light backgrounds'),
                        Forms\Components\SpatieMediaLibraryFileUpload::make('logo_dark')
                            ->collection('logo_dark')
                            ->model(fn () => $this->getRecord())
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
                            ->model(fn () => $this->getRecord())
                            ->label('Favicon')
                            ->image()
                            ->maxSize(512)
                            ->helperText('Recommended: 32x32 or 16x16 PNG/ICO'),
                        Forms\Components\SpatieMediaLibraryFileUpload::make('apple_touch_icon')
                            ->collection('apple_touch_icon')
                            ->model(fn () => $this->getRecord())
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
                            ->model(fn () => $this->getRecord())
                            ->label('OG Image')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Recommended: 1200x630 PNG/JPG'),
                    ]),

                Forms\Components\Section::make('Hero Banner')
                    ->description('Homepage hero section background image')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('hero_banner')
                            ->collection('hero_banner')
                            ->model(fn () => $this->getRecord())
                            ->label('Hero Banner Image')
                            ->image()
                            ->maxSize(10240)
                            ->helperText('Recommended: 1920x1080 or larger, max 10MB'),
                    ]),

                Forms\Components\Section::make('Brand Pillar Images')
                    ->description('Three images displayed in the Marketing Mosaic section below the hero banner. These represent your brand values.')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('brand_pillar_1')
                            ->collection('brand_pillar_1')
                            ->model(fn () => $this->getRecord())
                            ->label('Pillar 1: Sustainable Travel')
                            ->image()
                            ->maxSize(5120)
                            ->helperText('First card image. Recommended: 800x800 square, max 5MB'),
                        Forms\Components\SpatieMediaLibraryFileUpload::make('brand_pillar_2')
                            ->collection('brand_pillar_2')
                            ->model(fn () => $this->getRecord())
                            ->label('Pillar 2: Authentic Experiences')
                            ->image()
                            ->maxSize(5120)
                            ->helperText('Second card image. Recommended: 800x800 square, max 5MB'),
                        Forms\Components\SpatieMediaLibraryFileUpload::make('brand_pillar_3')
                            ->collection('brand_pillar_3')
                            ->model(fn () => $this->getRecord())
                            ->label('Pillar 3: Epic Adventures')
                            ->image()
                            ->maxSize(5120)
                            ->helperText('Third card image. Recommended: 800x800 square, max 5MB'),
                    ])
                    ->columns(3),
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

                Forms\Components\Section::make('Payment Gateway Configuration')
                    ->description('Select and configure payment gateways available to customers')
                    ->schema([
                        Forms\Components\Select::make('default_payment_gateway')
                            ->label('Default Payment Gateway')
                            ->options([
                                'mock' => 'Mock (Development)',
                                'stripe' => 'Stripe',
                                'clicktopay' => 'Click to Pay (Tunisia)',
                                'bank_transfer' => 'Bank Transfer',
                                'offline' => 'Offline/Manual',
                            ])
                            ->default('mock')
                            ->live()
                            ->helperText('The primary payment gateway used for transactions'),
                        Forms\Components\CheckboxList::make('enabled_payment_methods')
                            ->label('Enabled Payment Methods')
                            ->options([
                                'card' => 'Credit/Debit Card',
                                'bank_transfer' => 'Bank Transfer',
                                'cash' => 'Cash on Delivery',
                                'wallet' => 'Digital Wallet',
                            ])
                            ->columns(4)
                            ->helperText('Payment methods available to customers at checkout'),
                    ])
                    ->columns(1),

                // Mock Gateway (Development)
                Forms\Components\Section::make('Mock Gateway (Development Only)')
                    ->description('Test payment gateway for development - always succeeds after 2 seconds')
                    ->schema([
                        Forms\Components\Placeholder::make('mock_info')
                            ->label('')
                            ->content('Mock gateway is for testing only. All payments automatically succeed after a 2-second delay. Do not use in production.')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('mock_gateway_enabled')
                            ->label('Enable Mock Gateway')
                            ->helperText('Only enable in development/staging environments')
                            ->default(app()->environment('local')),
                    ])
                    ->collapsed()
                    ->collapsible(),

                // Stripe Gateway
                Forms\Components\Section::make('Stripe Payment Gateway')
                    ->description('Configure Stripe for card payments')
                    ->schema([
                        Forms\Components\Placeholder::make('stripe_info')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div class="text-sm text-gray-600 dark:text-gray-400">' .
                                'Stripe is a leading payment processor supporting cards, wallets, and local payment methods. ' .
                                '<a href="https://dashboard.stripe.com" target="_blank" class="text-primary-600 hover:underline">Get your API keys from Stripe Dashboard</a>' .
                                '</div>'
                            ))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('stripe_publishable_key')
                            ->label('Publishable Key')
                            ->placeholder('pk_live_...')
                            ->helperText('Public key used in frontend (starts with pk_)'),

                        Forms\Components\TextInput::make('stripe_secret_key')
                            ->label('Secret Key')
                            ->placeholder('sk_live_...')
                            ->password()
                            ->revealable()
                            ->helperText('Secret key for backend API calls (starts with sk_)'),

                        Forms\Components\TextInput::make('stripe_webhook_secret')
                            ->label('Webhook Signing Secret')
                            ->placeholder('whsec_...')
                            ->password()
                            ->revealable()
                            ->helperText('Used to verify webhook authenticity (starts with whsec_)'),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('test_stripe')
                                ->label('Test Connection')
                                ->icon('heroicon-o-bolt')
                                ->color('primary')
                                ->action(function () {
                                    // TODO: Implement Stripe connection test
                                    \Filament\Notifications\Notification::make()
                                        ->title('Test not yet implemented')
                                        ->body('Stripe connection testing will be available once the Stripe gateway is fully integrated.')
                                        ->warning()
                                        ->send();
                                }),
                        ])
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->collapsible(),

                // Click to Pay Gateway
                Forms\Components\Section::make('Click to Pay (Tunisia)')
                    ->description('Configure Click to Pay for Tunisian payment processing')
                    ->schema([
                        Forms\Components\Placeholder::make('clicktopay_info')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div class="text-sm text-gray-600 dark:text-gray-400">' .
                                'Click to Pay is Tunisia\'s local payment processor supporting cards and mobile payments. ' .
                                'Contact your Click to Pay account manager for API credentials.' .
                                '</div>'
                            ))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('clicktopay_merchant_id')
                            ->label('Merchant ID')
                            ->placeholder('MERCHANT123')
                            ->helperText('Your Click to Pay merchant identifier'),

                        Forms\Components\TextInput::make('clicktopay_api_key')
                            ->label('API Key')
                            ->placeholder('ctp_api_...')
                            ->password()
                            ->revealable()
                            ->helperText('API key for authentication'),

                        Forms\Components\TextInput::make('clicktopay_secret_key')
                            ->label('Secret Key')
                            ->placeholder('ctp_secret_...')
                            ->password()
                            ->revealable()
                            ->helperText('Secret key for signing requests'),

                        Forms\Components\Toggle::make('clicktopay_test_mode')
                            ->label('Test Mode')
                            ->helperText('Use Click to Pay test environment')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('test_clicktopay')
                                ->label('Test Connection')
                                ->icon('heroicon-o-bolt')
                                ->color('primary')
                                ->action(function () {
                                    // TODO: Implement Click to Pay connection test
                                    \Filament\Notifications\Notification::make()
                                        ->title('Test not yet implemented')
                                        ->body('Click to Pay connection testing will be available once API credentials are configured.')
                                        ->warning()
                                        ->send();
                                }),
                        ])
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->collapsible(),

                // Bank Transfer Gateway
                Forms\Components\Section::make('Bank Transfer')
                    ->description('Configure bank transfer details for manual payments')
                    ->schema([
                        Forms\Components\Placeholder::make('bank_transfer_info')
                            ->label('')
                            ->content('Customers will see these bank details at checkout and must transfer funds manually. Vendors must confirm payment receipt.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('bank_transfer_bank_name')
                            ->label('Bank Name')
                            ->placeholder('Banque de Tunisie')
                            ->helperText('Name of your bank'),

                        Forms\Components\TextInput::make('bank_transfer_account_holder')
                            ->label('Account Holder Name')
                            ->placeholder('Go Adventure SARL')
                            ->helperText('Legal name on the bank account'),

                        Forms\Components\TextInput::make('bank_transfer_account_number')
                            ->label('Account Number')
                            ->placeholder('1234567890')
                            ->helperText('Local account number'),

                        Forms\Components\TextInput::make('bank_transfer_iban')
                            ->label('IBAN')
                            ->placeholder('TN59 1000 1234 5678 9012 3456')
                            ->helperText('International Bank Account Number'),

                        Forms\Components\TextInput::make('bank_transfer_swift_bic')
                            ->label('SWIFT/BIC Code')
                            ->placeholder('BCTNTNTT')
                            ->helperText('Bank identifier code for international transfers'),

                        Forms\Components\Textarea::make('bank_transfer_instructions')
                            ->label('Payment Instructions')
                            ->rows(4)
                            ->placeholder('Please include your booking number in the transfer reference.')
                            ->helperText('Additional instructions shown to customers')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->collapsible(),

                // Offline/Manual Payments
                Forms\Components\Section::make('Offline/Manual Payments')
                    ->description('Allow vendors to manually confirm payments (cash, check, etc.)')
                    ->schema([
                        Forms\Components\Placeholder::make('offline_info')
                            ->label('')
                            ->content('When enabled, bookings can be created with pending payment status and vendors can manually mark them as paid. Use for cash payments, checks, or other offline methods.')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('offline_payments_enabled')
                            ->label('Enable Offline Payments')
                            ->helperText('Allow vendors to accept and manually confirm payments')
                            ->default(true)
                            ->inline(false),
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
                            ->prefix('د.ت')
                            ->default(10.00),
                        Forms\Components\TextInput::make('max_booking_amount')
                            ->label('Maximum Booking Amount')
                            ->numeric()
                            ->prefix('د.ت')
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
                            ->prefix('د.ت')
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

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            $settings = $this->getRecord();

            // Filter out media fields - they are handled automatically by SpatieMediaLibraryFileUpload
            $mediaFields = ['logo_light', 'logo_dark', 'favicon', 'apple_touch_icon', 'og_image', 'hero_banner', 'brand_pillar_1', 'brand_pillar_2', 'brand_pillar_3'];
            $filteredData = array_filter($data, fn ($key) => !in_array($key, $mediaFields), ARRAY_FILTER_USE_KEY);

            $settings->fill($filteredData);
            $settings->save();

            Notification::make()
                ->title('Settings saved successfully')
                ->success()
                ->send();
        } catch (Halt $exception) {
            return;
        }
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }
}
