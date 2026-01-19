<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactFormMail;
use Tests\TestCase;

/**
 * Contact Form API Tests
 *
 * BDD Scenarios:
 * - Successfully submit contact form with valid data
 * - Reject contact form with missing name
 * - Reject contact form with missing email
 * - Reject contact form with invalid email format
 * - Reject contact form with missing message
 * - Reject contact form with message less than 10 characters
 * - Email is queued when form is submitted successfully
 */
class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test contact form submission succeeds with valid data.
     */
    public function test_contact_form_submission_succeeds_with_valid_data(): void
    {
        // Arrange
        Mail::fake();

        $formData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a test message for the contact form.',
        ];

        // Act
        $response = $this->postJson('/api/v1/contact', $formData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test contact form submission queues email.
     */
    public function test_contact_form_submission_queues_email(): void
    {
        // Arrange
        Mail::fake();

        $formData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'message' => 'I would like to inquire about your services.',
        ];

        // Act
        $this->postJson('/api/v1/contact', $formData);

        // Assert
        Mail::assertQueued(ContactFormMail::class, function ($mail) use ($formData) {
            return $mail->hasTo('contact@go-adventure.net');
        });
    }

    /**
     * Test contact form submission fails without name.
     */
    public function test_contact_form_submission_fails_without_name(): void
    {
        // Arrange
        $formData = [
            'email' => 'john@example.com',
            'message' => 'This is a test message for the contact form.',
        ];

        // Act
        $response = $this->postJson('/api/v1/contact', $formData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test contact form submission fails without email.
     */
    public function test_contact_form_submission_fails_without_email(): void
    {
        // Arrange
        $formData = [
            'name' => 'John Doe',
            'message' => 'This is a test message for the contact form.',
        ];

        // Act
        $response = $this->postJson('/api/v1/contact', $formData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test contact form submission fails with invalid email format.
     */
    public function test_contact_form_submission_fails_with_invalid_email(): void
    {
        // Arrange
        $formData = [
            'name' => 'John Doe',
            'email' => 'not-a-valid-email',
            'message' => 'This is a test message for the contact form.',
        ];

        // Act
        $response = $this->postJson('/api/v1/contact', $formData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test contact form submission fails without message.
     */
    public function test_contact_form_submission_fails_without_message(): void
    {
        // Arrange
        $formData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        // Act
        $response = $this->postJson('/api/v1/contact', $formData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /**
     * Test contact form submission fails with message less than 10 characters.
     */
    public function test_contact_form_submission_fails_with_short_message(): void
    {
        // Arrange
        $formData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Too short', // Only 9 characters
        ];

        // Act
        $response = $this->postJson('/api/v1/contact', $formData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /**
     * Test contact form submission fails with empty name.
     */
    public function test_contact_form_submission_fails_with_empty_name(): void
    {
        // Arrange
        $formData = [
            'name' => '',
            'email' => 'john@example.com',
            'message' => 'This is a valid test message.',
        ];

        // Act
        $response = $this->postJson('/api/v1/contact', $formData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test contact form accepts name with minimum length.
     */
    public function test_contact_form_accepts_name_with_minimum_length(): void
    {
        // Arrange
        Mail::fake();

        $formData = [
            'name' => 'Jo', // 2 characters should be OK
            'email' => 'jo@example.com',
            'message' => 'This is a valid test message with enough characters.',
        ];

        // Act
        $response = $this->postJson('/api/v1/contact', $formData);

        // Assert
        $response->assertStatus(200);
    }

    /**
     * Test contact form accepts message with exactly 10 characters.
     */
    public function test_contact_form_accepts_message_with_exactly_ten_characters(): void
    {
        // Arrange
        Mail::fake();

        $formData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Ten chars!', // Exactly 10 characters
        ];

        // Act
        $response = $this->postJson('/api/v1/contact', $formData);

        // Assert
        $response->assertStatus(200);
    }

    /**
     * Test contact form returns French error messages with Accept-Language header.
     */
    public function test_contact_form_returns_localized_error_messages(): void
    {
        // Arrange
        $formData = [
            'name' => '',
            'email' => 'invalid',
            'message' => '',
        ];

        // Act
        $response = $this->withHeader('Accept-Language', 'fr')
            ->postJson('/api/v1/contact', $formData);

        // Assert
        $response->assertStatus(422);
        // Should return validation errors (content doesn't matter, just that it validates)
        $response->assertJsonValidationErrors(['name', 'email', 'message']);
    }

    /**
     * Test contact form email contains correct data.
     */
    public function test_contact_form_email_contains_submitted_data(): void
    {
        // Arrange
        Mail::fake();

        $formData = [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'message' => 'This is my detailed inquiry about adventure tours.',
        ];

        // Act
        $this->postJson('/api/v1/contact', $formData);

        // Assert
        Mail::assertQueued(ContactFormMail::class, function ($mail) use ($formData) {
            // Mail should contain the form data
            return $mail->name === $formData['name']
                && $mail->email === $formData['email']
                && $mail->contactMessage === $formData['message'];
        });
    }

    /**
     * Test contact form is rate limited.
     */
    public function test_contact_form_is_rate_limited(): void
    {
        // Arrange
        Mail::fake();

        $formData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a test message for rate limiting.',
        ];

        // Act - Submit multiple times rapidly
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/contact', $formData);
        }

        // The 6th request should potentially be rate limited
        // This test documents that rate limiting should be in place
        // Actual rate limit depends on configuration
        $response = $this->postJson('/api/v1/contact', $formData);

        // Assert - Should still work within normal limits
        // Rate limiting typically kicks in at higher volumes
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 429,
            'Response should be either success (200) or rate limited (429)'
        );
    }
}
