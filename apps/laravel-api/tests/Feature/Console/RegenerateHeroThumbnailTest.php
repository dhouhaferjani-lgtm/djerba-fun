<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\PlatformSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Test the media:regenerate-hero-thumbnail command.
 *
 * Note: These tests focus on command registration and basic flow.
 * FFmpeg-dependent functionality is tested only when FFmpeg is available.
 */
class RegenerateHeroThumbnailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Check if FFmpeg is available on this system.
     */
    private function ffmpegAvailable(): bool
    {
        $ffmpegPath = config('media-library.ffmpeg_path', '/usr/bin/ffmpeg');

        return file_exists($ffmpegPath);
    }

    public function test_command_is_registered(): void
    {
        // The command should be registered in artisan
        $this->artisan('list')
            ->expectsOutputToContain('media:regenerate-hero-thumbnail');
    }

    public function test_command_starts_with_ffmpeg_check(): void
    {
        // The command always starts by checking FFmpeg
        $this->artisan('media:regenerate-hero-thumbnail', ['--diagnose' => true])
            ->expectsOutputToContain('Checking FFmpeg');
    }

    public function test_command_reports_ffmpeg_not_found_when_missing(): void
    {
        if ($this->ffmpegAvailable()) {
            $this->markTestSkipped('FFmpeg is available, cannot test "not found" scenario');
        }

        $this->artisan('media:regenerate-hero-thumbnail', ['--diagnose' => true])
            ->assertFailed()
            ->expectsOutputToContain('FFmpeg not found');
    }

    public function test_command_with_ffmpeg_shows_error_when_no_platform_settings(): void
    {
        if (! $this->ffmpegAvailable()) {
            $this->markTestSkipped('FFmpeg not available');
        }

        // Ensure no settings exist
        PlatformSettings::query()->delete();

        $this->artisan('media:regenerate-hero-thumbnail', ['--diagnose' => true])
            ->assertFailed()
            ->expectsOutputToContain('PlatformSettings not found');
    }

    public function test_command_with_ffmpeg_shows_error_when_no_hero_banner(): void
    {
        if (! $this->ffmpegAvailable()) {
            $this->markTestSkipped('FFmpeg not available');
        }

        // Create settings without hero banner
        PlatformSettings::create([]);

        $this->artisan('media:regenerate-hero-thumbnail', ['--diagnose' => true])
            ->assertFailed()
            ->expectsOutputToContain('No hero banner media uploaded');
    }

    public function test_command_with_ffmpeg_handles_image_hero_banner(): void
    {
        if (! $this->ffmpegAvailable()) {
            $this->markTestSkipped('FFmpeg not available');
        }

        // Create settings with an image hero banner
        $settings = PlatformSettings::create([]);

        $image = UploadedFile::fake()->image('hero.jpg', 1920, 1080);
        $settings->addMedia($image)->toMediaCollection('hero_banner');

        $this->artisan('media:regenerate-hero-thumbnail', ['--diagnose' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Hero banner is an IMAGE');
    }

    public function test_command_with_ffmpeg_displays_media_information(): void
    {
        if (! $this->ffmpegAvailable()) {
            $this->markTestSkipped('FFmpeg not available');
        }

        $settings = PlatformSettings::create([]);

        $image = UploadedFile::fake()->image('hero-banner.jpg', 1920, 1080);
        $settings->addMedia($image)->toMediaCollection('hero_banner');

        $this->artisan('media:regenerate-hero-thumbnail', ['--diagnose' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('hero-banner.jpg')
            ->expectsOutputToContain('image/jpeg');
    }
}
