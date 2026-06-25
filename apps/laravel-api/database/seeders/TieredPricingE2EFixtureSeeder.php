<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AvailabilityRuleType;
use App\Enums\DifficultyLevel;
use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use App\Models\AvailabilityRule;
use App\Models\Listing;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Opt-in fixture for the tiered group-discount pricing Playwright spec
 * (apps/web/tests/e2e/tiered-pricing/*).
 *
 * Seeds one published TOUR listing with NORMAL per-person-type pricing
 * (adult 50, child 30 in both currencies) PLUS optional group prices for
 * sizes 2 -> 90 and 3 -> 130 (both currencies), priced by greedy "circle"
 * packing, so:
 *   1 -> 50, 2 -> 90, 3 -> 130, 4 -> 130+50=180, 5 -> 130+90=220, 6 -> 130+130=260.
 *
 * Idempotent: re-running updates the same rows in place.
 *
 * Run:     php artisan db:seed --class=TieredPricingE2EFixtureSeeder
 * Cleanup: App\Models\Listing::where('slug','tiered-group-tour-e2e')->forceDelete();
 */
class TieredPricingE2EFixtureSeeder extends Seeder
{
    public const LISTING_SLUG = 'tiered-group-tour-e2e';

    public const FLAT_LISTING_SLUG = 'flat-pricing-tour-e2e';

    public const LOCATION_SLUG = 'djerba-tiered-e2e';

    public function run(): void
    {
        $vendor = User::firstOrCreate(
            ['email' => 'tiered-e2e-vendor@example.com'],
            [
                'display_name' => 'Tiered E2E Vendor',
                'password' => bcrypt('password'),
                'role' => UserRole::VENDOR,
                'email_verified_at' => now(),
            ],
        );

        $location = Location::updateOrCreate(
            ['slug' => self::LOCATION_SLUG],
            [
                'name' => ['en' => 'Djerba (Tiered E2E)', 'fr' => 'Djerba (Tiered E2E)'],
                'city' => 'Djerba',
                'region' => 'Medenine',
                'country' => 'TN',
                'timezone' => 'Africa/Tunis',
                'latitude' => 33.8076,
                'longitude' => 10.8451,
                'description' => ['en' => 'Tiered pricing e2e fixture location.', 'fr' => 'Lieu fixture e2e tarification par groupe.'],
            ],
        );

        $listing = Listing::updateOrCreate(
            ['slug' => self::LISTING_SLUG],
            [
                'vendor_id' => $vendor->id,
                'location_id' => $location->id,
                'service_type' => ServiceType::TOUR,
                'status' => ListingStatus::PUBLISHED,
                'title' => ['en' => 'Tiered Group Tour (E2E)', 'fr' => 'Excursion en groupe (E2E)'],
                'summary' => ['en' => 'Group pricing demo tour.', 'fr' => 'Excursion démo tarification groupe.'],
                'description' => ['en' => 'A tour priced by group size.', 'fr' => 'Une excursion au tarif par taille de groupe.'],
                'highlights' => [],
                'included' => [],
                'not_included' => [],
                'requirements' => [],
                'meeting_point' => [],
                'cancellation_policy' => ['type' => 'flexible'],
                'pricing' => [
                    'currency' => 'TND',
                    'pricing_strategy' => 'tiered',
                    'person_types' => [
                        ['key' => 'adult', 'label' => ['en' => 'Adult', 'fr' => 'Adulte'], 'tnd_price' => 50, 'eur_price' => 50, 'minAge' => 18, 'minQuantity' => 1],
                        ['key' => 'child', 'label' => ['en' => 'Child', 'fr' => 'Enfant'], 'tnd_price' => 30, 'eur_price' => 30, 'minAge' => 2, 'maxAge' => 17, 'minQuantity' => 0],
                    ],
                    'tiers' => [
                        ['group_size' => 2, 'tnd_total' => 90, 'eur_total' => 90],
                        ['group_size' => 3, 'tnd_total' => 130, 'eur_total' => 130],
                    ],
                ],
                'min_group_size' => 1,
                'max_group_size' => 10,
                'duration' => 180,
                'difficulty' => DifficultyLevel::EASY,
                'published_at' => now(),
            ],
        );

        $this->seedWeeklyRule($listing);

        // FLAT regression listing: classic per-person-type pricing in the same
        // location, so the e2e can prove flat behaviour is untouched. Adult 50,
        // child 30 in BOTH currencies (currency-independent assertions).
        $flat = Listing::updateOrCreate(
            ['slug' => self::FLAT_LISTING_SLUG],
            [
                'vendor_id' => $vendor->id,
                'location_id' => $location->id,
                'service_type' => ServiceType::TOUR,
                'status' => ListingStatus::PUBLISHED,
                'title' => ['en' => 'Flat Pricing Tour (E2E)', 'fr' => 'Excursion tarif fixe (E2E)'],
                'summary' => ['en' => 'Classic per-person pricing.', 'fr' => 'Tarification classique par personne.'],
                'description' => ['en' => 'A tour priced per person.', 'fr' => 'Une excursion au tarif par personne.'],
                'highlights' => [],
                'included' => [],
                'not_included' => [],
                'requirements' => [],
                'meeting_point' => [],
                'cancellation_policy' => ['type' => 'flexible'],
                'pricing' => [
                    'currency' => 'TND',
                    'person_types' => [
                        ['key' => 'adult', 'label' => ['en' => 'Adult', 'fr' => 'Adulte'], 'tnd_price' => 50, 'eur_price' => 50, 'minAge' => 18, 'minQuantity' => 1],
                        ['key' => 'child', 'label' => ['en' => 'Child', 'fr' => 'Enfant'], 'tnd_price' => 30, 'eur_price' => 30, 'minAge' => 2, 'maxAge' => 17, 'minQuantity' => 0],
                    ],
                ],
                'min_group_size' => 1,
                'max_group_size' => 10,
                'duration' => 180,
                'difficulty' => DifficultyLevel::EASY,
                'published_at' => now(),
            ],
        );

        $this->seedWeeklyRule($flat);

        $this->command?->info('Seeded tiered fixture: /' . self::LOCATION_SLUG . '/' . self::LISTING_SLUG);
        $this->command?->info('Seeded flat fixture:   /' . self::LOCATION_SLUG . '/' . self::FLAT_LISTING_SLUG);
    }

    /**
     * Materialise bookable slots for a listing via a daily weekly rule.
     * CalculateAvailabilityJob runs on the rule's saved() observer. Resets any
     * prior rule first so the seeder stays idempotent.
     */
    private function seedWeeklyRule(Listing $listing): void
    {
        $listing->availabilityRules()->delete();

        AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [0, 1, 2, 3, 4, 5, 6], // every day, so a slot is always near
            'time_slots' => [
                ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => 10],
            ],
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'is_active' => true,
        ]);
    }
}
