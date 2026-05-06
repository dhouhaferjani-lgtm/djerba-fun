<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\PricingUnitLabel;
use PHPUnit\Framework\TestCase;

final class PricingUnitLabelTest extends TestCase
{
    public function test_returns_locale_match_when_present(): void
    {
        $pricing = ['unit_label' => ['fr' => 'par jetski', 'en' => 'per jetski']];

        self::assertSame('par jetski', PricingUnitLabel::resolve($pricing, 'fr'));
        self::assertSame('per jetski', PricingUnitLabel::resolve($pricing, 'en'));
    }

    public function test_falls_back_to_other_locale_when_requested_missing(): void
    {
        $pricing = ['unit_label' => ['fr' => 'par jetski']];

        self::assertSame('par jetski', PricingUnitLabel::resolve($pricing, 'en'));
    }

    public function test_returns_null_when_unit_label_missing(): void
    {
        self::assertNull(PricingUnitLabel::resolve([], 'fr'));
        self::assertNull(PricingUnitLabel::resolve(['unit_label' => null], 'fr'));
    }

    public function test_treats_whitespace_as_empty(): void
    {
        $pricing = ['unit_label' => ['fr' => '   ', 'en' => "\t\n"]];

        self::assertNull(PricingUnitLabel::resolve($pricing, 'fr'));
        self::assertNull(PricingUnitLabel::resolve($pricing, 'en'));
    }

    public function test_camel_case_key_also_supported(): void
    {
        // The Filament form persists snake_case (unit_label),
        // but external callers may pass camelCase (unitLabel).
        $pricing = ['unitLabel' => ['fr' => 'par scooter']];

        self::assertSame('par scooter', PricingUnitLabel::resolve($pricing, 'fr'));
    }

    public function test_to_array_returns_clean_translatable_map(): void
    {
        $pricing = ['unit_label' => ['fr' => 'par jetski', 'en' => '   ']];

        self::assertSame(['fr' => 'par jetski'], PricingUnitLabel::toArray($pricing));
    }

    public function test_to_array_returns_null_when_empty(): void
    {
        self::assertNull(PricingUnitLabel::toArray([]));
        self::assertNull(PricingUnitLabel::toArray(['unit_label' => ['fr' => '']]));
    }
}
