<?php

namespace App\Enums;

enum BoatEquipment: string
{
    // Safety
    case LIFE_JACKETS = 'life_jackets';
    case FIRST_AID_KIT = 'first_aid_kit';
    case FIRE_EXTINGUISHER = 'fire_extinguisher';
    case FLARES = 'flares';

    // Navigation
    case GPS = 'gps';
    case RADIO = 'radio';
    case DEPTH_FINDER = 'depth_finder';
    case FISH_FINDER = 'fish_finder';
    case COMPASS = 'compass';

    // Water Activities
    case SNORKELING_GEAR = 'snorkeling_gear';
    case FISHING_EQUIPMENT = 'fishing_equipment';
    case WATER_SKIS = 'water_skis';
    case WAKEBOARD = 'wakeboard';
    case DIVING_EQUIPMENT = 'diving_equipment';
    case PADDLE_BOARD = 'paddle_board';
    case KAYAK = 'kayak';

    // Comfort
    case COOLER = 'cooler';
    case SUN_SHADE = 'sun_shade';
    case BLUETOOTH_SPEAKERS = 'bluetooth_speakers';
    case SHOWER = 'shower';
    case TOILET = 'toilet';
    case CABIN = 'cabin';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::LIFE_JACKETS => 'Life Jackets',
            self::FIRST_AID_KIT => 'First Aid Kit',
            self::FIRE_EXTINGUISHER => 'Fire Extinguisher',
            self::FLARES => 'Safety Flares',
            self::GPS => 'GPS Navigation',
            self::RADIO => 'VHF Radio',
            self::DEPTH_FINDER => 'Depth Finder',
            self::FISH_FINDER => 'Fish Finder',
            self::COMPASS => 'Compass',
            self::SNORKELING_GEAR => 'Snorkeling Gear',
            self::FISHING_EQUIPMENT => 'Fishing Equipment',
            self::WATER_SKIS => 'Water Skis',
            self::WAKEBOARD => 'Wakeboard',
            self::DIVING_EQUIPMENT => 'Diving Equipment',
            self::PADDLE_BOARD => 'Paddle Board',
            self::KAYAK => 'Kayak',
            self::COOLER => 'Cooler / Ice Box',
            self::SUN_SHADE => 'Sun Shade / Bimini',
            self::BLUETOOTH_SPEAKERS => 'Bluetooth Speakers',
            self::SHOWER => 'Shower',
            self::TOILET => 'Toilet / Head',
            self::CABIN => 'Cabin',
        };
    }

    /**
     * Get the icon name (Heroicon).
     */
    public function icon(): string
    {
        return match ($this) {
            self::LIFE_JACKETS => 'heroicon-o-lifebuoy',
            self::FIRST_AID_KIT => 'heroicon-o-heart',
            self::FIRE_EXTINGUISHER => 'heroicon-o-fire',
            self::FLARES => 'heroicon-o-bolt',
            self::GPS => 'heroicon-o-map-pin',
            self::RADIO => 'heroicon-o-signal',
            self::DEPTH_FINDER => 'heroicon-o-arrow-down',
            self::FISH_FINDER => 'heroicon-o-magnifying-glass',
            self::COMPASS => 'heroicon-o-arrows-pointing-out',
            self::SNORKELING_GEAR => 'heroicon-o-eye',
            self::FISHING_EQUIPMENT => 'heroicon-o-bookmark',
            self::WATER_SKIS => 'heroicon-o-arrow-right',
            self::WAKEBOARD => 'heroicon-o-arrow-right',
            self::DIVING_EQUIPMENT => 'heroicon-o-arrow-down-circle',
            self::PADDLE_BOARD => 'heroicon-o-rectangle-group',
            self::KAYAK => 'heroicon-o-arrow-long-right',
            self::COOLER => 'heroicon-o-cube',
            self::SUN_SHADE => 'heroicon-o-sun',
            self::BLUETOOTH_SPEAKERS => 'heroicon-o-speaker-wave',
            self::SHOWER => 'heroicon-o-beaker',
            self::TOILET => 'heroicon-o-home',
            self::CABIN => 'heroicon-o-home-modern',
        };
    }

    /**
     * Get category for grouping.
     */
    public function category(): string
    {
        return match ($this) {
            self::LIFE_JACKETS, self::FIRST_AID_KIT, self::FIRE_EXTINGUISHER, self::FLARES => 'safety',
            self::GPS, self::RADIO, self::DEPTH_FINDER, self::FISH_FINDER, self::COMPASS => 'navigation',
            self::SNORKELING_GEAR, self::FISHING_EQUIPMENT, self::WATER_SKIS, self::WAKEBOARD, self::DIVING_EQUIPMENT, self::PADDLE_BOARD, self::KAYAK => 'activities',
            self::COOLER, self::SUN_SHADE, self::BLUETOOTH_SPEAKERS, self::SHOWER, self::TOILET, self::CABIN => 'comfort',
        };
    }

    /**
     * Get all equipment as options for Filament.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $equipment) => [$equipment->value => $equipment->label()])
            ->toArray();
    }

    /**
     * Get equipment grouped by category.
     */
    public static function grouped(): array
    {
        return collect(self::cases())
            ->groupBy(fn (self $equipment) => $equipment->category())
            ->map(fn ($group) => $group->mapWithKeys(fn (self $equipment) => [$equipment->value => $equipment->label()]))
            ->toArray();
    }
}
