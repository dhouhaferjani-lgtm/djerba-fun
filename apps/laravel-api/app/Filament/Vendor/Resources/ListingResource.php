<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources;

use AmidEsfahani\FilamentTinyEditor\TinyEditor;
use App\Enums\DifficultyLevel;
use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Filament\Vendor\Resources\ListingResource\Pages;
use App\Filament\Vendor\Resources\ListingResource\RelationManagers;
use App\Models\Listing;
use App\Models\Location;
use App\Models\Tag;
use App\Services\GpxParserService;
use Filament\Forms;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListingResource extends Resource
{
    use Translatable;

    protected static ?string $model = Listing::class;

    protected static ?string $navigationIcon = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.my_listings');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.listings');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('vendor_id', auth()->id());
    }

    /**
     * Resolve the record route binding, accepting both slug and ID.
     * This allows notification links with IDs to work alongside slug-based URLs.
     */
    public static function resolveRecordRouteBinding(int|string $key): ?\Illuminate\Database\Eloquent\Model
    {
        // Build query with vendor ownership filter
        $query = static::getEloquentQuery();

        // First try by slug (the model's route key)
        $record = (clone $query)->where('slug', $key)->first();

        // If not found by slug, try by ID (for backward compatibility)
        if (! $record && is_numeric($key)) {
            $record = (clone $query)->where('id', $key)->first();
        }

        return $record;
    }

    public static function getTranslatableLocales(): array
    {
        return ['en', 'fr'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    // Step 1: Basic Information
                    Forms\Components\Wizard\Step::make('Basic Information')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\Select::make('service_type')
                                ->label('Type')
                                ->options([
                                    ServiceType::TOUR->value => ServiceType::TOUR->label(),
                                    ServiceType::NAUTICAL->value => ServiceType::NAUTICAL->label(),
                                    ServiceType::ACCOMMODATION->value => 'Accommodation (Multi-Day)',
                                    ServiceType::EVENT->value => ServiceType::EVENT->label(),
                                ])
                                ->required() // Only truly required field for drafts
                                ->live()
                                ->afterStateUpdated(fn (Set $set) => $set('event_type', null)),

                            Forms\Components\Select::make('location_id')
                                ->label('Location')
                                ->options(fn () => Location::all()->mapWithKeys(fn ($loc) => [
                                    $loc->id => $loc->getTranslation('name', app()->getLocale()),
                                ]))
                                ->searchable()
                                ->preload()
                                ->helperText('Required for publishing'),

                            Forms\Components\TextInput::make('title.en')
                                ->label(__('filament.labels.title_english'))
                                ->maxLength(200)
                                ->live(debounce: 500)
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $state, $record) {
                                    // Don't auto-update slug for existing listings (preserve SEO)
                                    if ($record !== null && $record->exists && $record->slug) {
                                        return;
                                    }

                                    // Don't override if French title already generated the slug
                                    $frenchTitle = $get('title.fr');

                                    if (! empty($frenchTitle) && ! empty($get('_auto_slug'))) {
                                        return;
                                    }

                                    $currentSlug = $get('slug');
                                    $newSlug = Str::slug($state ?? '');

                                    // Check if user manually edited the slug
                                    $autoSlug = $get('_auto_slug');

                                    // If slug is empty, or matches the auto-generated slug, update it
                                    if (empty($currentSlug) || $currentSlug === $autoSlug) {
                                        $set('slug', $newSlug);
                                        $set('_auto_slug', $newSlug);
                                    }
                                })
                                ->helperText(__('filament.helpers.title_required'))
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('title.fr')
                                ->label(__('filament.labels.title_french'))
                                ->maxLength(200)
                                ->live(debounce: 500)
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $state, $record) {
                                    // Auto-generate slug from French if English is empty
                                    if ($record !== null && $record->exists && $record->slug) {
                                        return;
                                    }

                                    $englishTitle = $get('title.en');

                                    if (! empty($englishTitle)) {
                                        return; // Don't override if English title exists
                                    }

                                    $currentSlug = $get('slug');
                                    $newSlug = Str::slug($state ?? '');
                                    $autoSlug = $get('_auto_slug');

                                    if (empty($currentSlug) || $currentSlug === $autoSlug) {
                                        $set('slug', $newSlug);
                                        $set('_auto_slug', $newSlug);
                                    }
                                })
                                ->helperText(__('filament.helpers.title_required'))
                                ->columnSpanFull(),

                            Forms\Components\Hidden::make('_auto_slug')
                                ->dehydrated(false),

                            Forms\Components\TextInput::make('slug')
                                ->label('URL Slug')
                                ->unique(Listing::class, 'slug', ignoreRecord: true)
                                ->maxLength(200)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                    // When user manually edits slug, clear the auto_slug tracker
                                    // so we know not to auto-update anymore
                                    $autoSlug = $get('_auto_slug');

                                    if ($state !== $autoSlug) {
                                        $set('_auto_slug', null);
                                    }
                                })
                                ->helperText('Auto-generated from title. Edit to customize.'),

                            Forms\Components\Textarea::make('summary.en')
                                ->label(__('filament.labels.summary_english'))
                                ->rows(2)
                                ->maxLength(500)
                                ->helperText(__('filament.helpers.summary_required'))
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('summary.fr')
                                ->label(__('filament.labels.summary_french'))
                                ->rows(2)
                                ->maxLength(500)
                                ->helperText(__('filament.helpers.summary_required'))
                                ->columnSpanFull(),

                            TinyEditor::make('description.en')
                                ->label(__('filament.labels.description_english'))
                                ->profile('default')
                                ->fileAttachmentsDisk('public')
                                ->fileAttachmentsDirectory('listing-attachments')
                                ->fileAttachmentsVisibility('public')
                                ->helperText(__('filament.helpers.description_optional'))
                                ->columnSpanFull(),

                            TinyEditor::make('description.fr')
                                ->label(__('filament.labels.description_french'))
                                ->profile('default')
                                ->fileAttachmentsDisk('public')
                                ->fileAttachmentsDirectory('listing-attachments')
                                ->fileAttachmentsVisibility('public')
                                ->helperText(__('filament.helpers.description_optional'))
                                ->columnSpanFull(),
                        ]),

                    // Step 2: Media & Gallery
                    Forms\Components\Wizard\Step::make('Media & Gallery')
                        ->icon('heroicon-o-photo')
                        ->description('Upload up to 5 photos for your listing')
                        ->schema([
                            Forms\Components\Section::make('Gallery Photos')
                                ->description('Click each slot in the grid to upload your photos.')
                                ->schema([
                                    // Interactive bento uploader with clickable slots
                                    Forms\Components\ViewField::make('bento_uploader')
                                        ->view('filament.forms.components.bento-slot-mapper')
                                        ->dehydrated(false)
                                        ->columnSpanFull(),

                                    // Hidden field stores the layout selection (1-5 photos)
                                    Forms\Components\Hidden::make('gallery_layout')
                                        ->default(5),

                                    // Hidden field stores the uploaded images array
                                    Forms\Components\Hidden::make('gallery_images')
                                        ->default([]),
                                ]),
                        ]),

                    // Step 3: Details & Highlights
                    Forms\Components\Wizard\Step::make('Details & Highlights')
                        ->icon('heroicon-o-sparkles')
                        ->schema([
                            Forms\Components\Repeater::make('highlights')
                                ->label('Highlights')
                                ->schema([
                                    Forms\Components\TextInput::make('en')
                                        ->label('English'),
                                    Forms\Components\TextInput::make('fr')
                                        ->label('French'),
                                ])
                                ->columns(2)
                                ->minItems(0)
                                ->maxItems(10)
                                ->defaultItems(0)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['en'] ?? null),

                            Forms\Components\Repeater::make('included')
                                ->label("What's Included")
                                ->schema([
                                    Forms\Components\TextInput::make('en')
                                        ->label('English'),
                                    Forms\Components\TextInput::make('fr')
                                        ->label('French'),
                                ])
                                ->columns(2)
                                ->minItems(0)
                                ->maxItems(15)
                                ->defaultItems(0)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['en'] ?? null),

                            Forms\Components\Repeater::make('not_included')
                                ->label("What's Not Included")
                                ->schema([
                                    Forms\Components\TextInput::make('en')
                                        ->label('English'),
                                    Forms\Components\TextInput::make('fr')
                                        ->label('French'),
                                ])
                                ->columns(2)
                                ->maxItems(10)
                                ->defaultItems(0)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['en'] ?? null),

                            Forms\Components\Repeater::make('requirements')
                                ->label('Requirements')
                                ->schema([
                                    Forms\Components\TextInput::make('en')
                                        ->label('English'),
                                    Forms\Components\TextInput::make('fr')
                                        ->label('French'),
                                ])
                                ->columns(2)
                                ->maxItems(10)
                                ->defaultItems(0)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['en'] ?? null),

                            Forms\Components\Section::make('Tags')
                                ->description('Select tags that describe your listing')
                                ->schema([
                                    Forms\Components\CheckboxList::make('tags')
                                        ->label('')
                                        ->relationship('tags', 'name')
                                        ->options(function (Get $get) {
                                            $serviceType = $get('service_type');

                                            if (! $serviceType) {
                                                return [];
                                            }

                                            // Get tags applicable to this service type
                                            return Tag::query()
                                                ->active()
                                                ->forServiceType($serviceType)
                                                ->ordered()
                                                ->get()
                                                ->mapWithKeys(fn (Tag $tag) => [
                                                    $tag->id => $tag->getTranslation('name', app()->getLocale()),
                                                ]);
                                        })
                                        ->columns(3)
                                        ->gridDirection('row')
                                        ->searchable()
                                        ->bulkToggleable()
                                        ->helperText('Tags help travelers find your listing. Select all that apply.'),
                                ])
                                ->collapsible(),
                        ]),

                    // Step 3: Tour-specific or Event-specific Details
                    Forms\Components\Wizard\Step::make('Service Details')
                        ->icon('heroicon-o-cog')
                        ->schema([
                            // Tour-specific fields
                            Forms\Components\Section::make('Tour Details')
                                ->schema([
                                    Forms\Components\Select::make('activity_type_id')
                                        ->label(__('filament.labels.activity_type'))
                                        ->options(function () {
                                            try {
                                                return \App\Models\ActivityType::where('is_active', true)
                                                    ->orderBy('display_order')
                                                    ->get()
                                                    ->mapWithKeys(fn ($type) => [
                                                        $type->id => $type->getTranslation('name', app()->getLocale()),
                                                    ]);
                                            } catch (\Exception $e) {
                                                return [];
                                            }
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->helperText(__('filament.helpers.activity_type') ?? 'Select the type of activity for this tour')
                                        ->visible(fn (Get $get): bool => $get('service_type') === ServiceType::TOUR->value),

                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('duration.value')
                                                ->label('Duration Value')
                                                ->numeric()
                                                ->helperText('Required for publishing'),

                                            Forms\Components\Select::make('duration.unit')
                                                ->label('Duration Unit')
                                                ->options([
                                                    'minutes' => 'Minutes',
                                                    'hours' => 'Hours',
                                                    'days' => 'Days',
                                                ])
                                                ->default(fn (Get $get): ?string => $get('service_type') === ServiceType::ACCOMMODATION->value ? 'days' : null)
                                                ->afterStateHydrated(function (Forms\Components\Select $component, Get $get) {
                                                    if ($get('service_type') === ServiceType::ACCOMMODATION->value && empty($component->getState())) {
                                                        $component->state('days');
                                                    }
                                                }),

                                            Forms\Components\Select::make('difficulty')
                                                ->label('Difficulty Level')
                                                ->options([
                                                    DifficultyLevel::EASY->value => DifficultyLevel::EASY->label(),
                                                    DifficultyLevel::MODERATE->value => DifficultyLevel::MODERATE->label(),
                                                    DifficultyLevel::CHALLENGING->value => DifficultyLevel::CHALLENGING->label(),
                                                    DifficultyLevel::EXPERT->value => DifficultyLevel::EXPERT->label(),
                                                ]),
                                        ]),

                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('distance.value')
                                                ->label('Distance Value')
                                                ->numeric(),

                                            Forms\Components\Select::make('distance.unit')
                                                ->label('Distance Unit')
                                                ->options([
                                                    'km' => 'Kilometers',
                                                    'miles' => 'Miles',
                                                ]),
                                        ]),

                                    Forms\Components\Toggle::make('has_elevation_profile')
                                        ->label('Has Elevation Profile')
                                        ->helperText('Enable if this tour has elevation/altitude data'),

                                    // Hidden field to persist elevation profile data from GPX parsing
                                    Forms\Components\Hidden::make('elevation_profile')
                                        ->dehydrateStateUsing(fn ($state) => is_array($state) ? $state : null),
                                ])
                                // Only show tour details for Tour and Nautical - NOT for Accommodations
                                ->visible(fn (Get $get): bool => in_array($get('service_type'), [ServiceType::TOUR->value, ServiceType::NAUTICAL->value])),

                            // Accommodation-specific fields
                            Forms\Components\Section::make('Accommodation Details')
                                ->description('Property and stay settings')
                                ->schema([
                                    Forms\Components\Select::make('accommodation_type')
                                        ->label('Accommodation Type')
                                        ->options([
                                            'hotel' => 'Hotel',
                                            'guesthouse' => 'Guesthouse / Dar',
                                            'villa' => 'Villa / House',
                                            'apartment' => 'Apartment',
                                            'camping' => 'Camping',
                                            'mixed' => 'Mixed (varies by day)',
                                            'not_included' => 'Not Included',
                                        ]),

                                    Forms\Components\Grid::make(4)->schema([
                                        Forms\Components\TextInput::make('bedrooms')
                                            ->label('Bedrooms')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(20)
                                            ->suffix('rooms'),
                                        Forms\Components\TextInput::make('bathrooms')
                                            ->label('Bathrooms')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(20)
                                            ->suffix('baths'),
                                        Forms\Components\TextInput::make('max_guests')
                                            ->label('Max Guests')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(50)
                                            ->suffix('guests'),
                                        Forms\Components\TextInput::make('property_size')
                                            ->label('Property Size')
                                            ->numeric()
                                            ->minValue(1)
                                            ->suffix('m²'),
                                    ]),

                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\TimePicker::make('check_in_time')
                                            ->label('Check-in Time')
                                            ->seconds(false)
                                            ->default('15:00'),
                                        Forms\Components\TimePicker::make('check_out_time')
                                            ->label('Check-out Time')
                                            ->seconds(false)
                                            ->default('11:00'),
                                    ]),

                                    Forms\Components\Section::make('Meals Included')
                                        ->schema([
                                            Forms\Components\Grid::make(3)->schema([
                                                Forms\Components\Toggle::make('meals_included.breakfast')
                                                    ->label('Breakfast'),
                                                Forms\Components\Toggle::make('meals_included.lunch')
                                                    ->label('Lunch'),
                                                Forms\Components\Toggle::make('meals_included.dinner')
                                                    ->label('Dinner'),
                                            ]),
                                        ])
                                        ->compact(),

                                    Forms\Components\CheckboxList::make('amenities')
                                        ->label('Amenities')
                                        ->options(\App\Enums\Amenity::options())
                                        ->columns(4)
                                        ->gridDirection('row')
                                        ->searchable()
                                        ->bulkToggleable(),

                                    Forms\Components\Textarea::make('house_rules.en')
                                        ->label('House Rules (English)')
                                        ->rows(3)
                                        ->placeholder('No smoking, no parties, quiet hours after 10pm...'),
                                    Forms\Components\Textarea::make('house_rules.fr')
                                        ->label('House Rules (French)')
                                        ->rows(3)
                                        ->placeholder('Non-fumeur, pas de fêtes, heures calmes après 22h...'),
                                ])
                                ->visible(fn (Get $get): bool => $get('service_type') === ServiceType::ACCOMMODATION->value),

                            // Nautical-specific fields
                            Forms\Components\Section::make('Boat Details')
                                ->description('Vessel specifications and rental terms')
                                ->schema([
                                    Forms\Components\TextInput::make('boat_name')
                                        ->label('Boat Name')
                                        ->maxLength(100)
                                        ->placeholder('e.g., Sea Spirit'),

                                    Forms\Components\Grid::make(4)->schema([
                                        Forms\Components\TextInput::make('boat_length')
                                            ->label('Length')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(100)
                                            ->step(0.1)
                                            ->suffix('meters'),
                                        Forms\Components\TextInput::make('boat_capacity')
                                            ->label('Passenger Capacity')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(100)
                                            ->suffix('people'),
                                        Forms\Components\TextInput::make('boat_year')
                                            ->label('Year Built')
                                            ->numeric()
                                            ->minValue(1950)
                                            ->maxValue(date('Y') + 1),
                                        Forms\Components\TextInput::make('min_rental_hours')
                                            ->label('Min Rental')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(24)
                                            ->suffix('hours'),
                                    ]),

                                    Forms\Components\Section::make('Requirements & Inclusions')
                                        ->schema([
                                            Forms\Components\Grid::make(2)->schema([
                                                Forms\Components\Toggle::make('license_required')
                                                    ->label('Boating License Required')
                                                    ->live(),
                                                Forms\Components\TextInput::make('license_type')
                                                    ->label('License Type')
                                                    ->placeholder('e.g., Category C / Coastal')
                                                    ->visible(fn (Get $get): bool => $get('license_required') === true),
                                            ]),
                                            Forms\Components\Grid::make(2)->schema([
                                                Forms\Components\Toggle::make('crew_included')
                                                    ->label('Captain / Crew Included'),
                                                Forms\Components\Toggle::make('fuel_included')
                                                    ->label('Fuel Included'),
                                            ]),
                                        ])
                                        ->compact(),

                                    Forms\Components\CheckboxList::make('equipment_included')
                                        ->label('Equipment Included')
                                        ->options(\App\Enums\BoatEquipment::options())
                                        ->columns(4)
                                        ->gridDirection('row')
                                        ->searchable()
                                        ->bulkToggleable(),
                                ])
                                ->visible(fn (Get $get): bool => $get('service_type') === ServiceType::NAUTICAL->value),

                            // Event-specific fields
                            Forms\Components\Section::make('Event Details')
                                ->schema([
                                    Forms\Components\Select::make('event_type')
                                        ->label('Event Type')
                                        ->options([
                                            'festival' => 'Festival',
                                            'workshop' => 'Workshop',
                                            'concert' => 'Concert',
                                            'conference' => 'Conference',
                                            'exhibition' => 'Exhibition',
                                            'sports' => 'Sports Event',
                                            'cultural' => 'Cultural Event',
                                            'other' => 'Other',
                                        ])
                                        ->helperText('Required for publishing'),

                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\DateTimePicker::make('start_date')
                                                ->label('Start Date & Time')
                                                ->native(false)
                                                ->helperText('Required for publishing'),

                                            Forms\Components\DateTimePicker::make('end_date')
                                                ->label('End Date & Time')
                                                ->native(false)
                                                ->after('start_date'),
                                        ]),

                                    Forms\Components\Section::make('Venue')
                                        ->schema([
                                            Forms\Components\TextInput::make('venue.name')
                                                ->label('Venue Name')
                                                ->helperText('Required for publishing'),

                                            Forms\Components\TextInput::make('venue.address')
                                                ->label('Venue Address'),

                                            // Note: Google Maps picker temporarily removed (requires API key)
                                            // You can manually enter coordinates below
                                            Forms\Components\Grid::make(2)
                                                ->schema([
                                                    Forms\Components\TextInput::make('venue.coordinates.lat')
                                                        ->label('Latitude')
                                                        ->numeric()
                                                        ->placeholder('e.g., 36.8065')
                                                        ->dehydrated(),

                                                    Forms\Components\TextInput::make('venue.coordinates.lng')
                                                        ->label('Longitude')
                                                        ->numeric()
                                                        ->placeholder('e.g., 10.1815')
                                                        ->dehydrated(),
                                                ]),

                                            Forms\Components\TextInput::make('venue.capacity')
                                                ->label('Venue Capacity')
                                                ->numeric(),
                                        ])
                                        ->columns(1),
                                ])
                                ->visible(fn (Get $get): bool => $get('service_type') === ServiceType::EVENT->value),

                            // Common fields for both
                            Forms\Components\Section::make('Meeting Point')
                                ->schema([
                                    Forms\Components\TextInput::make('meeting_point.address')
                                        ->label('Meeting Point Address')
                                        ->helperText('Required for publishing'),

                                    // Note: Google Maps picker temporarily removed (requires API key)
                                    // You can manually enter coordinates below
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('meeting_point.coordinates.lat')
                                                ->label('Latitude')
                                                ->numeric()
                                                ->placeholder('e.g., 36.8065')
                                                ->dehydrated(),

                                            Forms\Components\TextInput::make('meeting_point.coordinates.lng')
                                                ->label('Longitude')
                                                ->numeric()
                                                ->placeholder('e.g., 10.1815')
                                                ->dehydrated(),
                                        ]),

                                    Forms\Components\Textarea::make('meeting_point.instructions')
                                        ->label('Meeting Instructions')
                                        ->rows(2),
                                ])
                                ->columns(1),
                        ]),

                    // Step 4: Route & Itinerary (Tours and Events)
                    Forms\Components\Wizard\Step::make('Route & Itinerary')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            // Display Settings - Available for ALL service types
                            Forms\Components\Section::make('Display Settings')
                                ->description('Control what route information is shown to travelers')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Toggle::make('show_itinerary')
                                                ->label('Show Route Map & Itinerary')
                                                ->helperText('Display interactive map with checkpoints')
                                                ->default(false)
                                                ->afterStateHydrated(function (Forms\Components\Toggle $component, $record) {
                                                    // Auto-enable when editing a listing that has itinerary data
                                                    if ($record && ! empty($record->itinerary)) {
                                                        $component->state(true);
                                                    }
                                                })
                                                ->live(),

                                            Forms\Components\Toggle::make('show_elevation_profile')
                                                ->label('Show Elevation Profile')
                                                ->helperText('Display elevation chart (requires elevation data)')
                                                ->default(false)
                                                ->afterStateHydrated(function (Forms\Components\Toggle $component, $record) {
                                                    // Auto-enable when editing a listing that has elevation data
                                                    if ($record && $record->has_elevation_profile) {
                                                        $component->state(true);
                                                    }
                                                }),
                                        ]),
                                ])
                                ->collapsed(false),

                            // Input Mode Selection - Available for all service types with show_itinerary enabled
                            Forms\Components\Section::make('Route Data Input')
                                ->description('Choose how to enter your route data')
                                ->schema([
                                    Forms\Components\Radio::make('itinerary_input_mode')
                                        ->label('Input Method')
                                        ->options([
                                            'manual' => 'Manual Entry - Add checkpoints one by one',
                                            'gpx' => 'GPX Import - Upload a GPX file from your GPS device',
                                        ])
                                        ->default(fn (Get $get) => $get('gpx_file_path') ? 'gpx' : 'manual')
                                        ->live()
                                        ->dehydrated(false) // Don't save to database
                                        ->afterStateUpdated(function (Set $set, $state, Get $get) {
                                            if ($state === 'gpx') {
                                                // GPX mode always uses markers (all points displayed)
                                                $set('map_display_type', 'markers');
                                            }

                                            if ($state === 'manual' && $get('gpx_file_path')) {
                                                // Warn user that switching to manual will clear GPX data
                                                Notification::make()
                                                    ->title('GPX data will be retained')
                                                    ->body('You can still edit checkpoints manually. The elevation profile from GPX will be kept.')
                                                    ->info()
                                                    ->send();
                                            }
                                        })
                                        ->descriptions([
                                            'manual' => 'Best for simple routes. Enter coordinates manually for each checkpoint.',
                                            'gpx' => 'Best for hiking/cycling. Upload GPX to auto-generate checkpoints, then edit as needed.',
                                        ]),

                                    // Map display type selector - only visible in manual mode
                                    Forms\Components\Select::make('map_display_type')
                                        ->label('Point Display Style')
                                        ->options([
                                            'markers' => 'Markers - Display numbered pins for each checkpoint',
                                            'circle' => 'Circle - Display 3km radius area (privacy mode)',
                                        ])
                                        ->default('markers')
                                        ->helperText('Circle mode hides exact checkpoint locations, showing only a general area')
                                        ->columnSpanFull()
                                        ->visible(fn (Get $get) => $get('itinerary_input_mode') === 'manual'),
                                ])
                                // Show when show_itinerary is enabled (for all service types)
                                ->visible(fn (Get $get) => $get('show_itinerary')),

                            // GPX Import Section (only visible in GPX mode)
                            Forms\Components\Section::make('GPX Import')
                                ->description('Upload a GPX file to automatically generate route and checkpoints')
                                ->schema([
                                    Forms\Components\FileUpload::make('gpx_upload')
                                        ->label('GPX File')
                                        ->acceptedFileTypes(['.gpx', 'application/gpx+xml', 'text/xml', 'application/xml'])
                                        ->maxSize(10240)
                                        ->disk('public')
                                        ->directory('gpx-uploads')
                                        ->visibility('public')
                                        ->helperText('Upload a GPX file from your GPS device or mapping app (Strava, Garmin, etc.)')
                                        ->dehydrated(false),

                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('parseGpx')
                                            ->label('Parse GPX & Generate Checkpoints')
                                            ->icon('heroicon-o-arrow-path')
                                            ->color('primary')
                                            ->action(function (Get $get, Set $set) {
                                                $gpxUpload = $get('gpx_upload');

                                                if (! $gpxUpload) {
                                                    Notification::make()
                                                        ->title('No GPX file uploaded')
                                                        ->body('Please upload a GPX file first.')
                                                        ->danger()
                                                        ->send();

                                                    return;
                                                }

                                                try {
                                                    $parser = app(GpxParserService::class);

                                                    // Handle different upload formats (TemporaryUploadedFile, string, or array)
                                                    $gpxFile = is_array($gpxUpload) ? reset($gpxUpload) : $gpxUpload;

                                                    if (! $gpxFile) {
                                                        throw new \Exception('Could not determine GPX file');
                                                    }

                                                    // Get the actual file path - handle TemporaryUploadedFile or string
                                                    if ($gpxFile instanceof TemporaryUploadedFile) {
                                                        $fullPath = $gpxFile->getRealPath();
                                                    } else {
                                                        // It's a string path on disk
                                                        $fullPath = Storage::disk('public')->path($gpxFile);
                                                    }

                                                    $parsed = $parser->parse($fullPath);

                                                    if (empty($parsed['trackPoints'])) {
                                                        Notification::make()
                                                            ->title('No track points found')
                                                            ->body('The GPX file does not contain any track data.')
                                                            ->danger()
                                                            ->send();

                                                        return;
                                                    }

                                                    $elevationProfile = $parser->generateElevationProfile($parsed['trackPoints']);
                                                    $itinerary = ! empty($parsed['waypoints'])
                                                        ? $parser->waypointsToItinerary($parsed['waypoints'])
                                                        : $parser->createStopsFromTrack($parsed['trackPoints'], 5);

                                                    $set('elevation_profile', $elevationProfile);
                                                    $set('itinerary', $itinerary);
                                                    $set('has_elevation_profile', ! empty($elevationProfile));
                                                    $set('show_elevation_profile', ! empty($elevationProfile));
                                                    $set('gpx_file_path', $fullPath);

                                                    Notification::make()
                                                        ->title('GPX Parsed Successfully')
                                                        ->body(sprintf(
                                                            'Created %d checkpoints with elevation data. You can edit them below.',
                                                            count($itinerary)
                                                        ))
                                                        ->success()
                                                        ->send();
                                                } catch (\Exception $e) {
                                                    Notification::make()
                                                        ->title('GPX Parsing Failed')
                                                        ->body($e->getMessage())
                                                        ->danger()
                                                        ->send();
                                                }
                                            }),
                                    ]),

                                    Forms\Components\Placeholder::make('gpx_note')
                                        ->label('')
                                        ->content(new \Illuminate\Support\HtmlString(
                                            '<div class="text-sm text-amber-600 bg-amber-50 p-3 rounded-lg mt-2">
                                                <strong>Note:</strong> After parsing, you can still edit the generated checkpoints below.
                                                The elevation profile will be automatically generated from the GPX track data.
                                            </div>'
                                        )),
                                ])
                                // GPX import available for all service types with show_itinerary enabled
                                ->visible(fn (Get $get) => $get('show_itinerary') && $get('itinerary_input_mode') === 'gpx')
                                ->collapsible(),

                            // Elevation Profile Preview (visible when we have elevation data) - NOT for accommodations
                            Forms\Components\Section::make('Elevation Profile Preview')
                                ->schema([
                                    Forms\Components\Placeholder::make('elevation_preview')
                                        ->label('')
                                        ->content(function (Get $get) {
                                            $profile = $get('elevation_profile');

                                            if (empty($profile)) {
                                                $mode = $get('itinerary_input_mode');

                                                if ($mode === 'manual') {
                                                    return new \Illuminate\Support\HtmlString(
                                                        '<div class="text-sm text-gray-500">
                                                            Enter elevation values for your checkpoints to generate an elevation profile.
                                                        </div>'
                                                    );
                                                }

                                                return 'No elevation data yet. Parse a GPX file to generate.';
                                            }

                                            return new \Illuminate\Support\HtmlString(sprintf(
                                                '<div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm p-4 bg-green-50 rounded-lg">
                                                    <div><strong class="text-gray-500">Distance:</strong><br/>%.1f km</div>
                                                    <div><strong class="text-gray-500">Ascent:</strong><br/>+%.0f m</div>
                                                    <div><strong class="text-gray-500">Descent:</strong><br/>-%.0f m</div>
                                                    <div><strong class="text-gray-500">Max Elev:</strong><br/>%.0f m</div>
                                                    <div><strong class="text-gray-500">Min Elev:</strong><br/>%.0f m</div>
                                                </div>',
                                                ($profile['totalDistance'] ?? 0) / 1000,
                                                $profile['totalAscent'] ?? 0,
                                                $profile['totalDescent'] ?? 0,
                                                $profile['maxElevation'] ?? 0,
                                                $profile['minElevation'] ?? 0
                                            ));
                                        }),
                                ])
                                // Elevation profile available for all service types
                                ->visible(fn (Get $get) => $get('show_itinerary') && $get('show_elevation_profile'))
                                ->collapsible()
                                ->collapsed(),

                            // Manual Entry Info (visible in manual mode) - NOT for accommodations
                            Forms\Components\Section::make('Manual Entry Instructions')
                                ->schema([
                                    Forms\Components\Placeholder::make('manual_instructions')
                                        ->label('')
                                        ->content(new \Illuminate\Support\HtmlString(
                                            '<div class="text-sm space-y-2">
                                                <p><strong>How to add checkpoints:</strong></p>
                                                <ul class="list-disc list-inside text-gray-600 space-y-1">
                                                    <li>Click "Add Checkpoint" below to add a new stop on your route</li>
                                                    <li>Enter the <strong>latitude</strong> and <strong>longitude</strong> for each checkpoint (you can get these from Google Maps)</li>
                                                    <li>Optionally add <strong>elevation</strong> data if you want to show an elevation profile</li>
                                                    <li>Choose a <strong>pin type</strong> to show different icons on the map</li>
                                                    <li>Drag checkpoints to reorder them</li>
                                                </ul>
                                                <p class="text-amber-600 mt-3">
                                                    <strong>Tip:</strong> To get coordinates from Google Maps, right-click on any location and click the coordinates to copy them.
                                                </p>
                                            </div>'
                                        )),
                                ])
                                // Manual entry instructions for all service types
                                ->visible(fn (Get $get) => $get('show_itinerary') && $get('itinerary_input_mode') === 'manual' && empty($get('itinerary')))
                                ->collapsible()
                                ->collapsed(),

                            // Route Checkpoints - unified for ALL service types
                            Forms\Components\Section::make('Route Checkpoints')
                                ->description('Define the stops along your route. These will be displayed on the map.')
                                ->schema([
                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('clearAllCheckpoints')
                                            ->label('Clear All Checkpoints')
                                            ->icon('heroicon-o-trash')
                                            ->color('danger')
                                            ->size('sm')
                                            ->requiresConfirmation()
                                            ->modalHeading('Clear all checkpoints?')
                                            ->modalDescription('This will remove all checkpoints and elevation data. This action cannot be undone.')
                                            ->action(function (Set $set) {
                                                $set('itinerary', []);
                                                $set('elevation_profile', null);
                                                $set('gpx_file_path', null);
                                                Notification::make()
                                                    ->title('All checkpoints cleared')
                                                    ->success()
                                                    ->send();
                                            }),

                                        Forms\Components\Actions\Action::make('generateElevationProfile')
                                            ->label('Generate Elevation Profile')
                                            ->icon('heroicon-o-chart-bar')
                                            ->color('success')
                                            ->size('sm')
                                            // Available for all service types in manual entry mode
                                            ->visible(fn (Get $get) => $get('itinerary_input_mode') === 'manual')
                                            ->action(function (Get $get, Set $set) {
                                                $itinerary = $get('itinerary') ?? [];

                                                if (empty($itinerary)) {
                                                    Notification::make()
                                                        ->title('No checkpoints')
                                                        ->body('Add some checkpoints first.')
                                                        ->warning()
                                                        ->send();

                                                    return;
                                                }

                                                // Generate elevation profile from manual checkpoints
                                                $points = [];
                                                $totalDistance = 0;
                                                $elevations = [];
                                                $prevPoint = null;

                                                foreach ($itinerary as $stop) {
                                                    if (! isset($stop['lat'], $stop['lng'])) {
                                                        continue;
                                                    }

                                                    $lat = (float) $stop['lat'];
                                                    $lng = (float) $stop['lng'];
                                                    $elevation = isset($stop['elevationMeters']) ? (float) $stop['elevationMeters'] : null;

                                                    if ($prevPoint) {
                                                        $totalDistance += self::calculateDistance(
                                                            $prevPoint['lat'],
                                                            $prevPoint['lng'],
                                                            $lat,
                                                            $lng
                                                        );
                                                    }

                                                    if ($elevation !== null) {
                                                        $elevations[] = $elevation;
                                                        $points[] = [
                                                            'distance' => round($totalDistance, 1),
                                                            'elevation' => round($elevation, 1),
                                                        ];
                                                    }

                                                    $prevPoint = ['lat' => $lat, 'lng' => $lng];
                                                }

                                                if (empty($elevations)) {
                                                    Notification::make()
                                                        ->title('No elevation data')
                                                        ->body('Add elevation values to your checkpoints to generate a profile.')
                                                        ->warning()
                                                        ->send();

                                                    return;
                                                }

                                                // Calculate ascent/descent
                                                $totalAscent = 0;
                                                $totalDescent = 0;
                                                for ($i = 1; $i < count($elevations); $i++) {
                                                    $diff = $elevations[$i] - $elevations[$i - 1];

                                                    if ($diff > 0) {
                                                        $totalAscent += $diff;
                                                    } else {
                                                        $totalDescent += abs($diff);
                                                    }
                                                }

                                                $elevationProfile = [
                                                    'points' => $points,
                                                    'totalDistance' => round($totalDistance, 1),
                                                    'totalAscent' => round($totalAscent, 1),
                                                    'totalDescent' => round($totalDescent, 1),
                                                    'maxElevation' => round(max($elevations), 1),
                                                    'minElevation' => round(min($elevations), 1),
                                                ];

                                                $set('elevation_profile', $elevationProfile);
                                                $set('has_elevation_profile', true);

                                                Notification::make()
                                                    ->title('Elevation Profile Generated')
                                                    ->body(sprintf(
                                                        'Profile created from %d checkpoints. Total distance: %.1f km',
                                                        count($points),
                                                        $totalDistance / 1000
                                                    ))
                                                    ->success()
                                                    ->send();
                                            }),
                                    ])->columnSpanFull(),

                                    Forms\Components\Repeater::make('itinerary')
                                        ->label('')
                                        ->dehydrated() // CRITICAL: Always save itinerary data even if section is hidden
                                        ->schema([
                                            Forms\Components\Hidden::make('id')
                                                ->default(fn () => (string) Str::uuid()),

                                            Forms\Components\Grid::make(4)
                                                ->schema([
                                                    Forms\Components\TextInput::make('title.en')
                                                        ->label('Title (English)')
                                                        ->maxLength(100)
                                                        ->placeholder('e.g., Start Point, Viewpoint, Lunch Stop'),

                                                    Forms\Components\TextInput::make('title.fr')
                                                        ->label('Title (French)')
                                                        ->maxLength(100)
                                                        ->placeholder('e.g., Point de départ'),

                                                    Forms\Components\Select::make('pinType')
                                                        ->label('Pin Type')
                                                        ->options([
                                                            'start' => '🚩 Start Point',
                                                            'end' => '🏁 End Point',
                                                            'waypoint' => '📍 Waypoint',
                                                            'viewpoint' => '👁️ Viewpoint',
                                                            'monument' => '🏛️ Monument/Historic',
                                                            'ruins' => '🏚️ Ruins',
                                                            'museum' => '🏛️ Museum',
                                                            'mosque' => '🕌 Mosque',
                                                            'forest' => '🌲 Forest/Nature',
                                                            'beach' => '🏖️ Beach',
                                                            'oasis' => '🌴 Oasis',
                                                            'cave' => '🕳️ Cave',
                                                            'restaurant' => '🍽️ Restaurant',
                                                            'cafe' => '☕ Café',
                                                            'market' => '🛒 Market/Souk',
                                                            'accommodation' => '🏨 Accommodation',
                                                            'camping' => '⛺ Camping',
                                                            'parking' => '🅿️ Parking',
                                                            'water' => '💧 Water Source',
                                                            'photo_spot' => '📸 Photo Spot',
                                                        ])
                                                        ->default('waypoint')
                                                        ->searchable(),

                                                    Forms\Components\TextInput::make('durationMinutes')
                                                        ->label('Time at Stop (min)')
                                                        ->numeric()
                                                        ->nullable()
                                                        ->placeholder('e.g., 30'),
                                                ]),

                                            Forms\Components\Grid::make(2)
                                                ->schema([
                                                    Forms\Components\Textarea::make('description.en')
                                                        ->label('Description (English)')
                                                        ->rows(2)
                                                        ->maxLength(500)
                                                        ->placeholder('What will visitors see or do here?'),

                                                    Forms\Components\Textarea::make('description.fr')
                                                        ->label('Description (French)')
                                                        ->rows(2)
                                                        ->maxLength(500),
                                                ]),

                                            // Coordinates grid - visible for ALL service types
                                            Forms\Components\Grid::make(3)
                                                ->schema([
                                                    Forms\Components\TextInput::make('lat')
                                                        ->label('Latitude')
                                                        ->numeric()
                                                        ->dehydrated()
                                                        ->placeholder('e.g., 36.8065'),

                                                    Forms\Components\TextInput::make('lng')
                                                        ->label('Longitude')
                                                        ->numeric()
                                                        ->dehydrated()
                                                        ->placeholder('e.g., 10.1815'),

                                                    Forms\Components\TextInput::make('elevationMeters')
                                                        ->label('Elevation (meters)')
                                                        ->numeric()
                                                        ->nullable()
                                                        ->placeholder('e.g., 450')
                                                        ->helperText('Optional - for elevation profile'),
                                                ]),

                                            Forms\Components\FileUpload::make('photos')
                                                ->label('Photos of this location')
                                                ->image()
                                                ->multiple()
                                                ->maxFiles(3)
                                                ->maxSize(5120)
                                                ->directory('listing-stops')
                                                ->reorderable()
                                                ->getUploadedFileUsing(static function (BaseFileUpload $component, string $file): ?array {
                                                    $storage = $component->getDisk();

                                                    if (! $storage->exists($file)) {
                                                        return null;
                                                    }

                                                    $diskName = $component->getDiskName() ?? 'public';

                                                    return [
                                                        'name' => basename($file),
                                                        'size' => $storage->size($file),
                                                        'type' => $storage->mimeType($file),
                                                        'url' => route('admin.storage.proxy', ['path' => $file, 'disk' => $diskName]),
                                                    ];
                                                })
                                                ->columnSpanFull(),
                                        ])
                                        ->orderColumn('order')
                                        ->reorderable()
                                        ->reorderableWithButtons()
                                        ->collapsible()
                                        ->cloneable()
                                        ->itemLabel(
                                            fn (array $state): ?string => isset($state['title']['en'])
                                                ? ($state['title']['en'] ?: 'Unnamed checkpoint')
                                                : 'New checkpoint'
                                        )
                                        ->addActionLabel('Add Checkpoint')
                                        ->defaultItems(0)
                                        ->columnSpanFull(),
                                ])
                                // For accommodations: always visible; for others: only when show_itinerary is on
                                ->visible(fn (Get $get) => $get('service_type') === ServiceType::ACCOMMODATION->value || $get('show_itinerary')),
                        ]),

                    // Step 5: Pricing & Capacity
                    Forms\Components\Wizard\Step::make('Pricing & Capacity')
                        ->icon('heroicon-o-currency-dollar')
                        ->schema([
                            Forms\Components\Section::make('Group Size')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('min_group_size')
                                                ->label('Minimum Group Size')
                                                ->numeric()
                                                ->minValue(1)
                                                ->default(1),

                                            Forms\Components\TextInput::make('max_group_size')
                                                ->label('Maximum Group Size')
                                                ->numeric()
                                                ->minValue(1)
                                                ->required()
                                                ->default(10)
                                                ->helperText('Required for publishing'),
                                        ]),

                                    Forms\Components\TextInput::make('min_advance_booking_hours')
                                        ->label('Minimum Advance Booking Time')
                                        ->helperText('How many hours in advance must customers book? (e.g., 24 = must book 24h before start time)')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->suffix('hours')
                                        ->columnSpanFull(),
                                ]),

                            // Property Pricing - ONLY for Accommodations (per-night flat rate)
                            Forms\Components\Section::make('Property Pricing')
                                ->description('Set your nightly rate for the entire property. Guests will select check-in and check-out dates.')
                                ->schema([
                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\TextInput::make('nightly_price_tnd')
                                            ->label('Price per Night (TND)')
                                            ->prefix('TND')
                                            ->numeric()
                                            ->step(0.01)
                                            ->minValue(0)
                                            ->required()
                                            ->helperText('Price in Tunisian Dinar per night'),

                                        Forms\Components\TextInput::make('nightly_price_eur')
                                            ->label('Price per Night (EUR)')
                                            ->prefix('€')
                                            ->numeric()
                                            ->step(0.01)
                                            ->minValue(0)
                                            ->required()
                                            ->helperText('Price in Euro per night'),
                                    ]),

                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\TextInput::make('minimum_nights')
                                            ->label('Minimum Stay')
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(1)
                                            ->suffix('nights')
                                            ->helperText('Minimum number of nights guests must book'),

                                        Forms\Components\TextInput::make('maximum_nights')
                                            ->label('Maximum Stay')
                                            ->numeric()
                                            ->minValue(1)
                                            ->suffix('nights')
                                            ->nullable()
                                            ->helperText('Leave empty for no maximum'),
                                    ]),

                                    // Hidden field to set pricing model
                                    Forms\Components\Hidden::make('pricing_model')
                                        ->default('per_night'),

                                    // Explanation
                                    Forms\Components\Placeholder::make('property_pricing_explanation')
                                        ->label('')
                                        ->content(new \Illuminate\Support\HtmlString(
                                            '<div class="text-sm text-gray-500 bg-amber-50 p-3 rounded-lg">
                                                <strong>How Property Pricing Works:</strong> Set a flat nightly rate for the entire property.
                                                Guests will select their check-in and check-out dates, and the total price will be calculated
                                                automatically (nightly rate × number of nights). Tunisian users see TND prices, international users see EUR prices.
                                            </div>'
                                        ))
                                        ->dehydrated(false),
                                ])
                                ->visible(fn (Get $get): bool => $get('service_type') === ServiceType::ACCOMMODATION->value),

                            // Optional unit label — overrides the public-site
                            // "par personne / per person" suffix when the listing
                            // is priced per machine / unit (e.g. "par jetski").
                            // Empty = legacy behavior (per-person suffix).
                            // Vendor-supplied, opt-in.
                            Forms\Components\Section::make('Pricing Unit Label (optional)')
                                ->description('Leave empty to display "per person". Set this when pricing is per machine, vehicle, or other unit. Examples: "par jetski", "per jetski", "par quad".')
                                ->schema([
                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\TextInput::make('pricing.unit_label.fr')
                                            ->label('Unit Label (French)')
                                            ->placeholder('par jetski')
                                            ->maxLength(60)
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('pricing.unit_label.en')
                                            ->label('Unit Label (English)')
                                            ->placeholder('per jetski')
                                            ->maxLength(60)
                                            ->columnSpan(1),
                                    ]),
                                ])
                                ->collapsed()
                                // Hide for accommodations — they use per-night
                                ->visible(fn (Get $get): bool => $get('service_type') !== ServiceType::ACCOMMODATION->value),

                            // Person Type Pricing - for Tours, Nautical, Events (NOT accommodations)
                            Forms\Components\Section::make('Person Type Pricing')
                                ->description('Configure pricing for different person types. At least one person type is required. Both TND and EUR prices must be set.')
                                ->schema([
                                    Forms\Components\Repeater::make('pricing.person_types')
                                        ->label('Person Types')
                                        ->schema([
                                            // Key + Labels (EN/FR)
                                            Forms\Components\Grid::make(3)->schema([
                                                Forms\Components\TextInput::make('key')
                                                    ->label('Key')
                                                    ->helperText('Lowercase, e.g., "adult", "child", "infant"')
                                                    ->required()
                                                    ->regex('/^[a-z_]+$/')
                                                    ->placeholder('adult')
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('label.en')
                                                    ->label('Label (English)')
                                                    ->required()
                                                    ->placeholder('Adult')
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('label.fr')
                                                    ->label('Label (French)')
                                                    ->required()
                                                    ->placeholder('Adulte')
                                                    ->columnSpan(1),
                                            ]),

                                            // Pricing (TND/EUR - vendors set each independently)
                                            Forms\Components\Grid::make(2)->schema([
                                                Forms\Components\TextInput::make('tnd_price')
                                                    ->label('Price in Tunisian Dinar')
                                                    ->prefix('TND')
                                                    ->numeric()
                                                    ->step(0.01)
                                                    ->minValue(0)
                                                    ->required()
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('eur_price')
                                                    ->label('Price in Euro')
                                                    ->prefix('€')
                                                    ->numeric()
                                                    ->step(0.01)
                                                    ->minValue(0)
                                                    ->required()
                                                    ->columnSpan(1),
                                            ]),

                                            // Age Range + Quantity Constraints
                                            Forms\Components\Grid::make(4)->schema([
                                                Forms\Components\TextInput::make('min_age')
                                                    ->label('Min Age')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->nullable()
                                                    ->placeholder('e.g., 18')
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('max_age')
                                                    ->label('Max Age')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->nullable()
                                                    ->placeholder('e.g., 65')
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('min_quantity')
                                                    ->label('Min Qty')
                                                    ->helperText('Minimum required for this type')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->default(0)
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('max_quantity')
                                                    ->label('Max Qty')
                                                    ->helperText('Maximum allowed for this type')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->nullable()
                                                    ->columnSpan(1),
                                            ]),

                                        ])
                                        ->defaultItems(1)
                                        ->minItems(1)
                                        ->maxItems(10)
                                        ->default([
                                            [
                                                'key' => 'adult',
                                                'label' => ['en' => 'Adult', 'fr' => 'Adulte'],
                                                'min_age' => 18,
                                                'min_quantity' => 1,
                                                'tnd_price' => null,
                                                'eur_price' => null,
                                            ],
                                        ])
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['label']['en'] ?? 'New Person Type')
                                        ->addActionLabel('Add Person Type')
                                        ->reorderable()
                                        ->columnSpanFull(),

                                    // Explanation
                                    Forms\Components\Placeholder::make('pricing_explanation')
                                        ->label('')
                                        ->content(new \Illuminate\Support\HtmlString(
                                            '<div class="text-sm text-gray-500 bg-blue-50 p-3 rounded-lg">
                                                <strong>How Person Type Pricing Works:</strong> Configure different prices for different types of participants
                                                (e.g., Adults, Children, Infants). Both TND and EUR prices are required.
                                                Tunisian users will see TND prices, international users will see EUR prices.
                                            </div>'
                                        ))
                                        ->dehydrated(false),
                                ])
                                // Hide Person Type Pricing for accommodations (they use per-night pricing instead)
                                ->visible(fn (Get $get): bool => $get('service_type') !== ServiceType::ACCOMMODATION->value),

                            Forms\Components\Section::make('Booking Settings')
                                ->schema([
                                    Forms\Components\Toggle::make('require_traveler_names')
                                        ->label('Require Participant Names')
                                        ->helperText('Enable if this activity requires participant names for operations (e.g., ski lift tickets, permits)')
                                        ->default(false)
                                        ->live(),

                                    Forms\Components\Select::make('traveler_names_timing')
                                        ->label('When to Collect Names')
                                        ->options([
                                            'immediate' => 'Immediately after payment (urgent)',
                                            'before_activity' => 'Anytime before activity date (flexible)',
                                        ])
                                        ->default('before_activity')
                                        ->helperText('Choose "Immediate" if names are critical for booking confirmation. Choose "Flexible" if they can be provided later.')
                                        ->visible(fn ($get) => $get('require_traveler_names'))
                                        ->required(fn ($get) => $get('require_traveler_names')),
                                ])
                                ->description('Configure when and how participant information is collected')
                                ->collapsible(),

                            Forms\Components\Section::make('Cancellation Policy')
                                ->schema([
                                    Forms\Components\Select::make('cancellation_policy.type')
                                        ->label('Policy Type')
                                        ->options([
                                            'flexible' => 'Flexible - Full refund up to 24h before',
                                            'moderate' => 'Moderate - Full refund up to 5 days before',
                                            'strict' => 'Strict - 50% refund up to 1 week before',
                                            'non_refundable' => 'Non-refundable',
                                        ])
                                        ->helperText('Required for publishing'),

                                    Forms\Components\Textarea::make('cancellation_policy.description')
                                        ->label('Policy Description')
                                        ->rows(2),
                                ]),
                        ]),

                    // Step 6: Availability (Optional)
                    Forms\Components\Wizard\Step::make('Availability')
                        ->icon('heroicon-o-calendar-days')
                        ->description('Optional: Add basic availability rules now')
                        ->schema([
                            Forms\Components\Toggle::make('_skip_availability')
                                ->label('Skip for Now')
                                ->helperText('You can add availability rules later from the Availability menu')
                                ->default(false)
                                ->live(),

                            Forms\Components\Repeater::make('_quick_availability_rules')
                                ->label('Quick Availability Rules')
                                ->schema([
                                    Forms\Components\Select::make('rule_type')
                                        ->label('Rule Type')
                                        ->options([
                                            'weekly' => 'Weekly Schedule (e.g., Every Monday & Wednesday)',
                                            'daily' => 'Daily (Every day of the week)',
                                        ])
                                        ->default('weekly')
                                        ->live()
                                        ->required(),

                                    Forms\Components\CheckboxList::make('days_of_week')
                                        ->label('Days of Week')
                                        ->options([
                                            1 => 'Monday',
                                            2 => 'Tuesday',
                                            3 => 'Wednesday',
                                            4 => 'Thursday',
                                            5 => 'Friday',
                                            6 => 'Saturday',
                                            0 => 'Sunday',
                                        ])
                                        ->columns(4)
                                        ->visible(fn ($get) => in_array($get('rule_type'), ['weekly', 'daily']))
                                        ->default([1, 2, 3, 4, 5])
                                        ->required(fn ($get) => in_array($get('rule_type'), ['weekly', 'daily'])),

                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\TimePicker::make('start_time')
                                            ->label('Start Time')
                                            ->default('09:00')
                                            ->required(fn (Get $get): bool => $get('../../service_type') !== ServiceType::ACCOMMODATION->value),

                                        Forms\Components\TimePicker::make('end_time')
                                            ->label('End Time')
                                            ->default('17:00')
                                            ->required(fn (Get $get): bool => $get('../../service_type') !== ServiceType::ACCOMMODATION->value),
                                    ])
                                        ->visible(fn (Get $get): bool => $get('../../service_type') !== ServiceType::ACCOMMODATION->value),

                                    Forms\Components\TextInput::make('capacity')
                                        ->label('Capacity')
                                        ->helperText('How many people can book this time slot?')
                                        ->numeric()
                                        ->minValue(1)
                                        ->required(fn (Get $get): bool => $get('../../service_type') !== ServiceType::ACCOMMODATION->value)
                                        ->default(fn ($get) => $get('../../max_group_size') ?? 10)
                                        ->visible(fn (Get $get): bool => $get('../../service_type') !== ServiceType::ACCOMMODATION->value),
                                ])
                                ->maxItems(3)
                                ->visible(fn ($get) => ! $get('_skip_availability'))
                                ->collapsible()
                                ->itemLabel(
                                    fn (array $state): ?string => isset($state['rule_type'])
                                        ? ($state['rule_type'] === 'weekly' ? 'Weekly Schedule' : 'Daily Schedule')
                                        : 'New Rule'
                                )
                                ->addActionLabel('Add Another Schedule')
                                ->defaultItems(0),

                            Forms\Components\Placeholder::make('availability_help')
                                ->label('')
                                ->content(new \Illuminate\Support\HtmlString(
                                    '<div class="text-sm text-gray-500 bg-blue-50 p-3 rounded-lg">
                                        <strong>💡 Tip:</strong> These are basic availability rules to get started.
                                        For advanced scheduling (specific dates, blackout periods, etc.), use the
                                        dedicated Availability menu after creating your listing.
                                    </div>'
                                ))
                                ->visible(fn ($get) => ! $get('_skip_availability'))
                                ->dehydrated(false),
                        ]),
                ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->state(function (Listing $record): string {
                        $currentLocale = app()->getLocale();
                        $alternateLocale = $currentLocale === 'en' ? 'fr' : 'en';

                        // Try current locale first (disable Spatie's auto-fallback to show indicator)
                        $title = $record->getTranslation('title', $currentLocale, false);
                        $usedLocale = $currentLocale;

                        // Handle malformed nested arrays from earlier bug
                        if (is_array($title)) {
                            $title = $title[$currentLocale] ?? $title['en'] ?? reset($title) ?: null;

                            while (is_array($title)) {
                                $title = reset($title) ?: null;
                            }
                        }

                        // If empty, try alternate locale
                        if (empty($title)) {
                            $title = $record->getTranslation('title', $alternateLocale, false);
                            $usedLocale = $alternateLocale;

                            if (is_array($title)) {
                                $title = $title[$alternateLocale] ?? $title['en'] ?? reset($title) ?: null;

                                while (is_array($title)) {
                                    $title = reset($title) ?: null;
                                }
                            }
                        }

                        if (empty($title)) {
                            return 'Untitled';
                        }

                        // Add language indicator if using alternate locale
                        if ($usedLocale !== $currentLocale) {
                            $langLabel = strtoupper($usedLocale);

                            return "[{$langLabel}] {$title}";
                        }

                        return $title;
                    })
                    ->limit(40)
                    ->description(function ($record) {
                        // Check which languages have content
                        $titleEn = $record->getTranslation('title', 'en');
                        $titleFr = $record->getTranslation('title', 'fr');

                        // Handle malformed arrays
                        if (is_array($titleEn)) {
                            $titleEn = $titleEn['en'] ?? reset($titleEn) ?: null;
                        }

                        if (is_array($titleFr)) {
                            $titleFr = $titleFr['fr'] ?? reset($titleFr) ?: null;
                        }

                        $hasEn = ! empty($titleEn);
                        $hasFr = ! empty($titleFr);

                        if ($hasEn && $hasFr) {
                            return null; // Bilingual - no indicator needed
                        }

                        if ($hasEn) {
                            return __('filament.labels.english_only');
                        }

                        if ($hasFr) {
                            return __('filament.labels.french_only');
                        }

                        return null;
                    })
                    ->searchable(false),

                Tables\Columns\TextColumn::make('service_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (ServiceType $state): string => $state->label())
                    ->color(fn (ServiceType $state): string => match ($state) {
                        ServiceType::TOUR => 'success',
                        ServiceType::NAUTICAL => 'primary',
                        ServiceType::ACCOMMODATION => 'warning',
                        ServiceType::EVENT => 'info',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ListingStatus $state): string => $state->label())
                    ->color(fn (ListingStatus $state): string => $state->color()),

                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->formatStateUsing(function ($record) {
                        $name = $record->location?->getTranslation('name', app()->getLocale());

                        if (is_array($name)) {
                            $name = $name[app()->getLocale()] ?? $name['en'] ?? reset($name) ?: '-';

                            while (is_array($name)) {
                                $name = reset($name) ?: '-';
                            }
                        }

                        return $name ?: '-';
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('pricing.base')
                    ->label('Base Price')
                    ->formatStateUsing(
                        fn ($state, $record) => number_format((float) $state / 100, 2) . ' ' . ($record->pricing['currency'] ?? 'TND')
                    )
                    ->sortable(false),

                Tables\Columns\TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 1) . '/5' : '-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_type')
                    ->label('Type')
                    ->options([
                        ServiceType::TOUR->value => ServiceType::TOUR->label(),
                        ServiceType::NAUTICAL->value => ServiceType::NAUTICAL->label(),
                        ServiceType::ACCOMMODATION->value => ServiceType::ACCOMMODATION->label(),
                        ServiceType::EVENT->value => ServiceType::EVENT->label(),
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        ListingStatus::DRAFT->value => ListingStatus::DRAFT->label(),
                        ListingStatus::PENDING_REVIEW->value => ListingStatus::PENDING_REVIEW->label(),
                        ListingStatus::PUBLISHED->value => ListingStatus::PUBLISHED->label(),
                        ListingStatus::ARCHIVED->value => ListingStatus::ARCHIVED->label(),
                        ListingStatus::REJECTED->value => ListingStatus::REJECTED->label(),
                    ]),

                Tables\Filters\SelectFilter::make('content_language')
                    ->label(__('filament.filters.content_language'))
                    ->options([
                        'en_only' => __('filament.filters.english_only'),
                        'fr_only' => __('filament.filters.french_only'),
                        'bilingual' => __('filament.filters.bilingual'),
                        'missing_en' => __('filament.filters.missing_english'),
                        'missing_fr' => __('filament.filters.missing_french'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        // PostgreSQL JSON syntax: title->>'en' extracts as text
                        return match ($data['value']) {
                            'en_only' => $query->whereRaw("(title->>'en') IS NOT NULL AND (title->>'en') != ''")
                                ->where(function ($q) {
                                    $q->whereRaw("(title->>'fr') IS NULL")
                                        ->orWhereRaw("(title->>'fr') = ''");
                                }),
                            'fr_only' => $query->whereRaw("(title->>'fr') IS NOT NULL AND (title->>'fr') != ''")
                                ->where(function ($q) {
                                    $q->whereRaw("(title->>'en') IS NULL")
                                        ->orWhereRaw("(title->>'en') = ''");
                                }),
                            'bilingual' => $query->whereRaw("(title->>'en') IS NOT NULL AND (title->>'en') != ''")
                                ->whereRaw("(title->>'fr') IS NOT NULL AND (title->>'fr') != ''"),
                            'missing_en' => $query->where(function ($q) {
                                $q->whereRaw("(title->>'en') IS NULL")
                                    ->orWhereRaw("(title->>'en') = ''");
                            }),
                            'missing_fr' => $query->where(function ($q) {
                                $q->whereRaw("(title->>'fr') IS NULL")
                                    ->orWhereRaw("(title->>'fr') = ''");
                            }),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Listing $record) => $record->status->canEdit()),

                Tables\Actions\Action::make('submit_for_review')
                    ->label('Submit for Review')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Submit for Review')
                    ->modalDescription('Before submitting, please ensure all required fields are filled in.')
                    ->action(function (Listing $record) {
                        // Validate required fields for publishing
                        $errors = [];

                        // Basic info
                        if (! $record->location_id) {
                            $errors[] = 'Location is required';
                        }

                        // Check title - must have at least one translation (English OR French)
                        $titleEn = $record->getTranslation('title', 'en');
                        $titleFr = $record->getTranslation('title', 'fr');
                        $hasEnglishTitle = ! empty($titleEn) && ! (is_array($titleEn) && empty(array_filter($titleEn)));
                        $hasFrenchTitle = ! empty($titleFr) && ! (is_array($titleFr) && empty(array_filter($titleFr)));

                        if (! $hasEnglishTitle && ! $hasFrenchTitle) {
                            $errors[] = __('filament.validation.title_translation_required');
                        }

                        // Check summary - must have at least one translation (English OR French)
                        $summaryEn = $record->getTranslation('summary', 'en');
                        $summaryFr = $record->getTranslation('summary', 'fr');
                        $hasEnglishSummary = ! empty($summaryEn) && ! (is_array($summaryEn) && empty(array_filter($summaryEn)));
                        $hasFrenchSummary = ! empty($summaryFr) && ! (is_array($summaryFr) && empty(array_filter($summaryFr)));

                        if (! $hasEnglishSummary && ! $hasFrenchSummary) {
                            $errors[] = __('filament.validation.summary_translation_required');
                        }

                        // Check description - must have at least one translation (English OR French)
                        $descriptionEn = $record->getTranslation('description', 'en');
                        $descriptionFr = $record->getTranslation('description', 'fr');
                        $hasEnglishDescription = ! empty($descriptionEn) && ! (is_array($descriptionEn) && empty(array_filter($descriptionEn)));
                        $hasFrenchDescription = ! empty($descriptionFr) && ! (is_array($descriptionFr) && empty(array_filter($descriptionFr)));

                        if (! $hasEnglishDescription && ! $hasFrenchDescription) {
                            $errors[] = __('filament.validation.description_translation_required');
                        }

                        // Service-specific validation
                        if ($record->service_type === ServiceType::TOUR) {
                            if (empty($record->duration['value'])) {
                                $errors[] = 'Duration is required for tours';
                            }
                        } elseif ($record->service_type === ServiceType::NAUTICAL) {
                            if (empty($record->duration['value'])) {
                                $errors[] = 'Duration is required for nautical activities';
                            }
                        } elseif ($record->service_type === ServiceType::ACCOMMODATION) {
                            if (empty($record->duration['value'])) {
                                $errors[] = 'Duration (number of days) is required for accommodations';
                            }
                        } elseif ($record->service_type === ServiceType::EVENT) {
                            if (empty($record->event_type)) {
                                $errors[] = 'Event type is required';
                            }

                            if (empty($record->start_date)) {
                                $errors[] = 'Start date is required for events';
                            }

                            if (empty($record->venue['name'])) {
                                $errors[] = 'Venue name is required for events';
                            }
                        }

                        // Meeting point
                        if (empty($record->meeting_point['address'])) {
                            $errors[] = 'Meeting point address is required';
                        }

                        // Pricing validation based on service type
                        if ($record->service_type === ServiceType::ACCOMMODATION) {
                            // Accommodations use nightly pricing (direct columns)
                            $hasNightlyPricing = ! empty($record->nightly_price_tnd) || ! empty($record->nightly_price_eur);

                            if (! $hasNightlyPricing) {
                                $errors[] = 'Nightly pricing (TND or EUR) is required for accommodations';
                            }
                        } else {
                            // Tours/Events/Nautical use person type pricing
                            if (empty($record->pricing['person_types'])) {
                                $errors[] = 'At least one person type is required';
                            } else {
                                // Validate each person type has prices
                                $hasValidPricing = false;

                                foreach ($record->pricing['person_types'] as $personType) {
                                    if (! empty($personType['tnd_price']) && ! empty($personType['eur_price'])) {
                                        $hasValidPricing = true;
                                        break;
                                    }
                                }

                                if (! $hasValidPricing) {
                                    $errors[] = 'At least one person type must have both TND and EUR prices';
                                }
                            }
                        }

                        // Group size
                        if (empty($record->max_group_size)) {
                            $errors[] = 'Maximum group size is required';
                        }

                        // Cancellation policy
                        if (empty($record->cancellation_policy['type'])) {
                            $errors[] = 'Cancellation policy is required';
                        }

                        if (! empty($errors)) {
                            Notification::make()
                                ->title('Cannot Submit for Review')
                                ->body('Please fix the following issues:' . "\n• " . implode("\n• ", $errors))
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        $record->update(['status' => ListingStatus::PENDING_REVIEW]);

                        Notification::make()
                            ->title('Submitted for Review')
                            ->body('Your listing has been submitted and will be reviewed by our team.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Listing $record) => $record->status === ListingStatus::DRAFT),

                Tables\Actions\Action::make('archive')
                    ->label('Archive')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (Listing $record) {
                        $record->archive();
                    })
                    ->visible(fn (Listing $record) => $record->status === ListingStatus::PUBLISHED),

                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (Listing $record) {
                        $newListing = $record->replicate();
                        $newListing->slug = $record->slug . '-copy-' . time();
                        $newListing->status = ListingStatus::DRAFT;
                        $newListing->published_at = null;
                        $newListing->bookings_count = 0;
                        $newListing->reviews_count = 0;
                        $newListing->rating = null;
                        $newListing->save();
                    }),

                Tables\Actions\Action::make('manage_event')
                    ->label('Manage Event')
                    ->icon('heroicon-o-calendar-days')
                    ->color('primary')
                    ->action(function (Listing $record) {
                        // Only check for slots when user clicks, not during table render
                        $slot = $record->availabilitySlots()
                            ->where('start_time', '>=', now())
                            ->orderBy('start_time')
                            ->first();

                        if (! $slot) {
                            Notification::make()
                                ->title('No Upcoming Events')
                                ->body('There are no upcoming events scheduled for this listing.')
                                ->warning()
                                ->send();

                            return;
                        }

                        return redirect(BookingResource::getUrl('manage-event', ['slot' => $slot->id]));
                    })
                    ->visible(fn (Listing $record) => $record->isEvent()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ExtrasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListListings::route('/'),
            'create' => Pages\CreateListing::route('/create'),
            'view' => Pages\ViewListing::route('/{record}'),
            'edit' => Pages\EditListing::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', ListingStatus::DRAFT)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * Calculate distance between two coordinates using Haversine formula.
     * Returns distance in meters.
     */
    /**
     * Get service_type from within a nested repeater context.
     * Walks up the $get path to find the root-level service_type field.
     */
    private static function getServiceTypeFromRepeater(Get $get): ?string
    {
        // Try various parent depths since repeater nesting varies
        foreach (['../../service_type', '../../../service_type', '../../../../service_type'] as $path) {
            $value = $get($path);

            if ($value) {
                return $value;
            }
        }

        return null;
    }

    private static function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meters

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
