<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestimonialTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test can list active testimonials.
     */
    public function test_can_list_active_testimonials(): void
    {
        // Arrange - Create active testimonials
        Testimonial::factory()->count(3)->active()->create();

        // Create inactive testimonial (should not appear)
        Testimonial::factory()->inactive()->create();

        // Act
        $response = $this->getJson('/api/v1/testimonials');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'photo',
                        'text',
                        'textFr',
                        'textEn',
                        'rating',
                        'location',
                        'activity',
                        'createdAt',
                    ],
                ],
            ]);
    }

    /**
     * Test testimonials are returned in correct order.
     */
    public function test_testimonials_returned_in_sort_order(): void
    {
        // Arrange - Create testimonials with specific sort orders
        $third = Testimonial::factory()->active()->withSortOrder(3)->create(['name' => 'Third']);
        $first = Testimonial::factory()->active()->withSortOrder(1)->create(['name' => 'First']);
        $second = Testimonial::factory()->active()->withSortOrder(2)->create(['name' => 'Second']);

        // Act
        $response = $this->getJson('/api/v1/testimonials');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals('First', $data[0]['name']);
        $this->assertEquals('Second', $data[1]['name']);
        $this->assertEquals('Third', $data[2]['name']);
    }

    /**
     * Test limit parameter works correctly.
     */
    public function test_limit_parameter_works(): void
    {
        // Arrange
        Testimonial::factory()->count(10)->active()->create();

        // Act
        $response = $this->getJson('/api/v1/testimonials?limit=3');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test limit is clamped to maximum of 20.
     */
    public function test_limit_clamped_to_maximum(): void
    {
        // Arrange
        Testimonial::factory()->count(25)->active()->create();

        // Act
        $response = $this->getJson('/api/v1/testimonials?limit=100');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(20, 'data');
    }

    /**
     * Test limit is clamped to minimum of 1.
     */
    public function test_limit_clamped_to_minimum(): void
    {
        // Arrange
        Testimonial::factory()->count(5)->active()->create();

        // Act
        $response = $this->getJson('/api/v1/testimonials?limit=0');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test testimonials returned in correct locale (French).
     */
    public function test_testimonials_returned_in_french_locale(): void
    {
        // Arrange
        Testimonial::factory()->active()->create([
            'text' => ['fr' => 'Texte en français', 'en' => 'English text'],
        ]);

        // Act
        $response = $this->getJson('/api/v1/testimonials', [
            'Accept-Language' => 'fr',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.0.text', 'Texte en français')
            ->assertJsonPath('data.0.textFr', 'Texte en français')
            ->assertJsonPath('data.0.textEn', 'English text');
    }

    /**
     * Test testimonials returned in correct locale (English).
     */
    public function test_testimonials_returned_in_english_locale(): void
    {
        // Arrange
        Testimonial::factory()->active()->create([
            'text' => ['fr' => 'Texte en français', 'en' => 'English text'],
        ]);

        // Act
        $response = $this->getJson('/api/v1/testimonials', [
            'Accept-Language' => 'en',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.0.text', 'English text')
            ->assertJsonPath('data.0.textFr', 'Texte en français')
            ->assertJsonPath('data.0.textEn', 'English text');
    }

    /**
     * Test invalid locale falls back to French.
     */
    public function test_invalid_locale_falls_back_to_french(): void
    {
        // Arrange
        Testimonial::factory()->active()->create([
            'text' => ['fr' => 'Texte en français', 'en' => 'English text'],
        ]);

        // Act - Invalid locale
        $response = $this->getJson('/api/v1/testimonials', [
            'Accept-Language' => 'de', // German, not supported
        ]);

        // Assert - Should fall back to French
        $response->assertStatus(200)
            ->assertJsonPath('data.0.text', 'Texte en français');
    }

    /**
     * Test can get single testimonial by UUID.
     */
    public function test_can_get_testimonial_by_uuid(): void
    {
        // Arrange
        $testimonial = Testimonial::factory()->active()->create([
            'name' => 'Test Name',
        ]);

        // Act
        $response = $this->getJson("/api/v1/testimonials/{$testimonial->uuid}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $testimonial->uuid)
            ->assertJsonPath('data.name', 'Test Name');
    }

    /**
     * Test cannot get inactive testimonial by UUID.
     */
    public function test_cannot_get_inactive_testimonial(): void
    {
        // Arrange
        $testimonial = Testimonial::factory()->inactive()->create();

        // Act
        $response = $this->getJson("/api/v1/testimonials/{$testimonial->uuid}");

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test nonexistent testimonial returns 404.
     */
    public function test_nonexistent_testimonial_returns_404(): void
    {
        // Act
        $response = $this->getJson('/api/v1/testimonials/00000000-0000-0000-0000-000000000000');

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test photo URL is returned correctly when set.
     */
    public function test_photo_url_returned_correctly(): void
    {
        // Arrange
        $testimonial = Testimonial::factory()->active()->create([
            'photo' => 'https://example.com/photo.jpg',
        ]);

        // Act
        $response = $this->getJson('/api/v1/testimonials');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.0.photo', 'https://example.com/photo.jpg');
    }

    /**
     * Test photo is null when not set.
     */
    public function test_photo_null_when_not_set(): void
    {
        // Arrange
        Testimonial::factory()->active()->create([
            'photo' => null,
        ]);

        // Act
        $response = $this->getJson('/api/v1/testimonials');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.0.photo', null);
    }

    /**
     * Test rating is returned correctly.
     */
    public function test_rating_returned_correctly(): void
    {
        // Arrange
        Testimonial::factory()->active()->create([
            'rating' => 5,
        ]);

        // Act
        $response = $this->getJson('/api/v1/testimonials');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.0.rating', 5);
    }

    /**
     * Test empty list when no active testimonials.
     */
    public function test_empty_list_when_no_active_testimonials(): void
    {
        // Arrange - Only inactive testimonials
        Testimonial::factory()->count(3)->inactive()->create();

        // Act
        $response = $this->getJson('/api/v1/testimonials');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /**
     * Test fallback to French when translation missing.
     */
    public function test_fallback_to_french_when_translation_missing(): void
    {
        // Arrange - Only French translation
        Testimonial::factory()->active()->create([
            'text' => ['fr' => 'Texte en français'],
        ]);

        // Act - Request English
        $response = $this->getJson('/api/v1/testimonials', [
            'Accept-Language' => 'en',
        ]);

        // Assert - Should fall back to French
        $response->assertStatus(200)
            ->assertJsonPath('data.0.text', 'Texte en français');
    }

    /**
     * Test location and activity are returned.
     */
    public function test_location_and_activity_returned(): void
    {
        // Arrange
        Testimonial::factory()->active()->create([
            'location' => 'Paris, France',
            'activity' => 'Tour en calèche',
        ]);

        // Act
        $response = $this->getJson('/api/v1/testimonials');

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.0.location', 'Paris, France')
            ->assertJsonPath('data.0.activity', 'Tour en calèche');
    }
}
