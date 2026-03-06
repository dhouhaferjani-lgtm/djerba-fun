<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ServiceType;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ServiceType enum.
 *
 * MIGRATION NOTE: Evasion Djerba requires these service types:
 * - TOUR (existing)
 * - NAUTICAL (new - for water activities like jet ski, parasailing)
 * - ACCOMMODATION (renamed from SEJOUR)
 * - EVENT (existing)
 */
class ServiceTypeTest extends TestCase
{
    /**
     * Test that all expected service types exist.
     */
    public function test_all_service_types_exist(): void
    {
        $expectedTypes = ['tour', 'nautical', 'accommodation', 'event'];
        $actualTypes = array_map(fn($case) => $case->value, ServiceType::cases());

        sort($expectedTypes);
        sort($actualTypes);

        $this->assertEquals($expectedTypes, $actualTypes, 'ServiceType enum should have exactly: tour, nautical, accommodation, event');
    }

    /**
     * Test service type values are correct.
     */
    public function test_service_type_values(): void
    {
        $this->assertEquals('tour', ServiceType::TOUR->value);
        $this->assertEquals('nautical', ServiceType::NAUTICAL->value);
        $this->assertEquals('accommodation', ServiceType::ACCOMMODATION->value);
        $this->assertEquals('event', ServiceType::EVENT->value);
    }

    /**
     * Test service type labels.
     */
    public function test_service_type_labels(): void
    {
        $this->assertEquals('Tour', ServiceType::TOUR->label());
        $this->assertEquals('Nautical', ServiceType::NAUTICAL->label());
        $this->assertEquals('Accommodation', ServiceType::ACCOMMODATION->label());
        $this->assertEquals('Event', ServiceType::EVENT->label());
    }

    /**
     * Test service type icons.
     */
    public function test_service_type_icons(): void
    {
        // Tour uses map icon
        $this->assertEquals('heroicon-o-map', ServiceType::TOUR->icon());

        // Nautical uses lifebuoy/anchor icon
        $this->assertMatchesRegularExpression('/heroicon-o-(lifebuoy|anchor)/', ServiceType::NAUTICAL->icon());

        // Accommodation uses home/building icon
        $this->assertMatchesRegularExpression('/heroicon-o-(home|building-office)/', ServiceType::ACCOMMODATION->icon());

        // Event uses calendar icon
        $this->assertEquals('heroicon-o-calendar', ServiceType::EVENT->icon());
    }

    /**
     * Test that we can create ServiceType from string values.
     */
    public function test_can_create_from_value(): void
    {
        $this->assertEquals(ServiceType::TOUR, ServiceType::from('tour'));
        $this->assertEquals(ServiceType::NAUTICAL, ServiceType::from('nautical'));
        $this->assertEquals(ServiceType::ACCOMMODATION, ServiceType::from('accommodation'));
        $this->assertEquals(ServiceType::EVENT, ServiceType::from('event'));
    }

    /**
     * Test that tryFrom returns null for invalid values.
     */
    public function test_try_from_returns_null_for_invalid(): void
    {
        $this->assertNull(ServiceType::tryFrom('invalid'));
        $this->assertNull(ServiceType::tryFrom('sejour')); // Old value should not work
        $this->assertNull(ServiceType::tryFrom(''));
    }

    /**
     * Test that there are exactly 4 service types.
     */
    public function test_service_type_count(): void
    {
        $this->assertCount(4, ServiceType::cases());
    }
}
