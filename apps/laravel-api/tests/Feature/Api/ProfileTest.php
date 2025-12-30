<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\TravelerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test getting authenticated user profile.
     */
    public function test_get_authenticated_user_profile(): void
    {
        // Arrange
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->getJson('/api/v1/auth/me');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'email' => 'john@example.com',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ],
            ]);
    }

    /**
     * Test updating user profile.
     */
    public function test_update_user_profile(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->putJson('/api/v1/users/profile', [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'phone' => '+1234567890',
            ]);

        // Assert
        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals('Jane', $user->first_name);
        $this->assertEquals('Smith', $user->last_name);
        $this->assertEquals('+1234567890', $user->phone);
    }

    /**
     * Test updating email requires unique email.
     */
    public function test_update_email_requires_unique(): void
    {
        // Arrange
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        // Act
        $response = $this->actingAs($user1)
            ->putJson('/api/v1/users/profile', [
                'email' => 'user2@example.com',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test getting traveler profile.
     */
    public function test_get_traveler_profile(): void
    {
        // Arrange
        $user = User::factory()->create();
        $travelerProfile = TravelerProfile::factory()->create([
            'user_id' => $user->id,
            'nationality' => 'CA',
            'date_of_birth' => '1990-01-01',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->getJson('/api/v1/users/traveler-profile');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'profile' => [
                    'id',
                    'nationality',
                    'date_of_birth',
                ],
            ]);
    }

    /**
     * Test updating traveler profile.
     */
    public function test_update_traveler_profile(): void
    {
        // Arrange
        $user = User::factory()->create();
        TravelerProfile::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)
            ->putJson('/api/v1/users/traveler-profile', [
                'nationality' => 'FR',
                'date_of_birth' => '1985-05-15',
                'emergency_contact_name' => 'Emergency Contact',
                'emergency_contact_phone' => '+9876543210',
            ]);

        // Assert
        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals('FR', $user->travelerProfile->nationality);
        $this->assertEquals('1985-05-15', $user->travelerProfile->date_of_birth->format('Y-m-d'));
    }

    /**
     * Test creating traveler profile if not exists.
     */
    public function test_create_traveler_profile_if_not_exists(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->putJson('/api/v1/users/traveler-profile', [
                'nationality' => 'US',
                'date_of_birth' => '1992-03-20',
            ]);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('traveler_profiles', [
            'user_id' => $user->id,
            'nationality' => 'US',
        ]);
    }

    /**
     * Test traveler profile preferences.
     */
    public function test_update_traveler_preferences(): void
    {
        // Arrange
        $user = User::factory()->create();
        TravelerProfile::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)
            ->putJson('/api/v1/users/traveler-profile', [
                'preferences' => [
                    'preferred_currency' => 'EUR',
                    'newsletter_subscribed' => true,
                    'dietary_restrictions' => ['vegetarian'],
                ],
            ]);

        // Assert
        $response->assertStatus(200);

        $user->refresh();
        $preferences = $user->travelerProfile->preferences;
        $this->assertEquals('EUR', $preferences['preferred_currency']);
        $this->assertTrue($preferences['newsletter_subscribed']);
    }

    /**
     * Test unauthenticated user cannot access profile.
     */
    public function test_unauthenticated_cannot_access_profile(): void
    {
        // Act
        $response = $this->getJson('/api/v1/auth/me');

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Test deleting user profile (soft delete).
     */
    public function test_delete_user_account(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/users/account');

        // Assert
        $response->assertStatus(200);

        // User should be soft deleted
        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }

    /**
     * Test profile validation.
     */
    public function test_profile_validation(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act - Invalid phone format
        $response = $this->actingAs($user)
            ->putJson('/api/v1/users/profile', [
                'phone' => 'invalid',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /**
     * Test updating password.
     */
    public function test_update_password(): void
    {
        // Arrange
        $user = User::factory()->create([
            'password' => bcrypt('old-password'),
        ]);

        // Act
        $response = $this->actingAs($user)
            ->putJson('/api/v1/users/password', [
                'current_password' => 'old-password',
                'new_password' => 'new-password',
                'new_password_confirmation' => 'new-password',
            ]);

        // Assert
        $response->assertStatus(200);

        // Verify new password works
        $this->assertTrue(
            \Hash::check('new-password', $user->fresh()->password)
        );
    }

    /**
     * Test updating password requires correct current password.
     */
    public function test_update_password_requires_correct_current(): void
    {
        // Arrange
        $user = User::factory()->create([
            'password' => bcrypt('old-password'),
        ]);

        // Act
        $response = $this->actingAs($user)
            ->putJson('/api/v1/users/password', [
                'current_password' => 'wrong-password',
                'new_password' => 'new-password',
                'new_password_confirmation' => 'new-password',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }
}
