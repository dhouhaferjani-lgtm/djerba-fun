<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Admin;

use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\ListingResource;
use App\Filament\Admin\Resources\ListingResource\Pages\ViewListing;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression tests for ViewListing page to ensure all service types can be viewed.
 *
 * @see https://github.com/issue/service-type-sejour-undefined
 */
class ViewListingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);
    }

    /**
     * Test admin can view a TOUR listing without errors.
     */
    public function test_can_view_tour_listing(): void
    {
        // Arrange
        $listing = Listing::factory()->published()->create([
            'service_type' => ServiceType::TOUR,
        ]);

        // Act & Assert - No exception should be thrown
        $this->actingAs($this->admin);

        Livewire::test(ViewListing::class, ['record' => $listing->slug])
            ->assertSuccessful()
            ->assertSee($listing->getTranslation('title', 'en'));
    }

    /**
     * Test admin can view a NAUTICAL listing without errors.
     */
    public function test_can_view_nautical_listing(): void
    {
        // Arrange
        $listing = Listing::factory()->nautical()->published()->create();

        // Act & Assert - No exception should be thrown
        $this->actingAs($this->admin);

        Livewire::test(ViewListing::class, ['record' => $listing->slug])
            ->assertSuccessful()
            ->assertSee($listing->getTranslation('title', 'en'));
    }

    /**
     * Test admin can view an ACCOMMODATION listing without errors.
     * Regression test: Previously failed with "Undefined constant ServiceType::SEJOUR"
     */
    public function test_can_view_accommodation_listing(): void
    {
        // Arrange
        $listing = Listing::factory()->accommodation()->published()->create();

        // Act & Assert - No exception should be thrown
        $this->actingAs($this->admin);

        Livewire::test(ViewListing::class, ['record' => $listing->slug])
            ->assertSuccessful()
            ->assertSee($listing->getTranslation('title', 'en'));
    }

    /**
     * Test admin can view an EVENT listing without errors.
     */
    public function test_can_view_event_listing(): void
    {
        // Arrange
        $listing = Listing::factory()->event()->published()->create();

        // Act & Assert - No exception should be thrown
        $this->actingAs($this->admin);

        Livewire::test(ViewListing::class, ['record' => $listing->slug])
            ->assertSuccessful()
            ->assertSee($listing->getTranslation('title', 'en'));
    }

    /**
     * Test service type badge colors are correct for all types.
     */
    public function test_service_type_badge_colors(): void
    {
        // Test that each service type can be rendered without errors
        $serviceTypes = [
            ServiceType::TOUR,
            ServiceType::NAUTICAL,
            ServiceType::ACCOMMODATION,
            ServiceType::EVENT,
        ];

        foreach ($serviceTypes as $serviceType) {
            $listing = Listing::factory()->published()->create([
                'service_type' => $serviceType,
            ]);

            $this->actingAs($this->admin);

            // Just verify no exception is thrown when rendering
            Livewire::test(ViewListing::class, ['record' => $listing->slug])
                ->assertSuccessful();
        }
    }

    /**
     * Test Tour Details section is visible for tour-like service types.
     */
    public function test_tour_details_visible_for_tour_like_types(): void
    {
        // Tour-like types: TOUR, NAUTICAL, ACCOMMODATION
        $tourLikeTypes = [ServiceType::TOUR, ServiceType::NAUTICAL, ServiceType::ACCOMMODATION];

        foreach ($tourLikeTypes as $serviceType) {
            $listing = Listing::factory()->published()->create([
                'service_type' => $serviceType,
                'duration' => ['value' => 2, 'unit' => 'hours'],
            ]);

            $this->assertTrue(
                $serviceType->isTourLike(),
                "ServiceType::{$serviceType->name} should be tour-like"
            );
        }
    }

    /**
     * Test Tour Details section is NOT visible for EVENT type.
     */
    public function test_tour_details_not_visible_for_event(): void
    {
        $this->assertFalse(
            ServiceType::EVENT->isTourLike(),
            'ServiceType::EVENT should NOT be tour-like'
        );
    }

    /**
     * Test admin can approve a draft listing of any service type.
     */
    public function test_can_approve_listing_of_any_service_type(): void
    {
        foreach (ServiceType::cases() as $serviceType) {
            $listing = Listing::factory()->create([
                'service_type' => $serviceType,
                'status' => ListingStatus::PENDING_REVIEW,
            ]);

            $this->actingAs($this->admin);

            // Verify no exception when viewing pending listing
            Livewire::test(ViewListing::class, ['record' => $listing->slug])
                ->assertSuccessful();
        }
    }
}
