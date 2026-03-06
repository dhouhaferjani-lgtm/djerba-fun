<?php

declare(strict_types=1);

namespace App\Enums;

enum TagType: string
{
    case TOUR_TYPE = 'tour_type';
    case BOAT_TYPE = 'boat_type';
    case SPACE_TYPE = 'space_type';
    case EVENT_FEATURE = 'event_feature';
    case AMENITY = 'amenity';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::TOUR_TYPE => 'Tour Type',
            self::BOAT_TYPE => 'Boat Type',
            self::SPACE_TYPE => 'Space Type',
            self::EVENT_FEATURE => 'Event Feature',
            self::AMENITY => 'Amenity',
        };
    }

    /**
     * Get French label
     */
    public function labelFr(): string
    {
        return match ($this) {
            self::TOUR_TYPE => 'Type de tour',
            self::BOAT_TYPE => 'Type de bateau',
            self::SPACE_TYPE => 'Type d\'espace',
            self::EVENT_FEATURE => 'Caractéristique d\'événement',
            self::AMENITY => 'Équipement',
        };
    }

    /**
     * Get icon for UI (Heroicons)
     */
    public function icon(): string
    {
        return match ($this) {
            self::TOUR_TYPE => 'heroicon-o-map',
            self::BOAT_TYPE => 'heroicon-o-lifebuoy',
            self::SPACE_TYPE => 'heroicon-o-home',
            self::EVENT_FEATURE => 'heroicon-o-sparkles',
            self::AMENITY => 'heroicon-o-check-badge',
        };
    }

    /**
     * Get service types this tag type applies to.
     * Returns array of ServiceType values.
     *
     * @return array<string>
     */
    public function applicableServiceTypes(): array
    {
        return match ($this) {
            self::TOUR_TYPE => [ServiceType::TOUR->value],
            self::BOAT_TYPE => [ServiceType::NAUTICAL->value],
            self::SPACE_TYPE => [ServiceType::ACCOMMODATION->value, ServiceType::EVENT->value],
            self::EVENT_FEATURE => [ServiceType::EVENT->value, ServiceType::TOUR->value],
            self::AMENITY => [ServiceType::ACCOMMODATION->value],
        };
    }

    /**
     * Check if this tag type applies to a given service type.
     */
    public function appliesToServiceType(ServiceType $serviceType): bool
    {
        return in_array($serviceType->value, $this->applicableServiceTypes(), true);
    }

    /**
     * Get all tag types for a given service type.
     *
     * @return array<self>
     */
    public static function forServiceType(ServiceType $serviceType): array
    {
        return array_filter(
            self::cases(),
            fn (self $type) => $type->appliesToServiceType($serviceType)
        );
    }
}
