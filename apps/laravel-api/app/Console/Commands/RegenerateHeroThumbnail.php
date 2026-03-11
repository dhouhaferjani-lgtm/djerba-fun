<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlatformSettings;
use Illuminate\Console\Command;
use Spatie\MediaLibrary\Conversions\ConversionCollection;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\Process\Process;

/**
 * Command to diagnose and regenerate hero banner video thumbnails.
 *
 * The thumbnail is extracted from the first second of the video using FFmpeg.
 * If the thumbnail wasn't generated during upload (e.g., FFmpeg failure),
 * this command can diagnose the issue and force regeneration.
 */
class RegenerateHeroThumbnail extends Command
{
    protected $signature = 'media:regenerate-hero-thumbnail
                            {--diagnose : Only diagnose, do not regenerate}
                            {--force : Force regeneration even if thumbnail exists}';

    protected $description = 'Diagnose and regenerate hero banner video thumbnail';

    public function handle(): int
    {
        $diagnoseOnly = $this->option('diagnose');
        $force = $this->option('force');

        $this->info('=== Hero Banner Thumbnail Diagnostic ===');
        $this->newLine();

        // Step 1: Check FFmpeg availability
        if (! $this->checkFfmpeg()) {
            return Command::FAILURE;
        }

        // Step 2: Get hero banner media
        $media = $this->getHeroBannerMedia();
        if (! $media) {
            return Command::FAILURE;
        }

        // Step 3: Check if it's a video
        if (! $this->isVideo($media)) {
            return Command::SUCCESS;
        }

        // Step 4: Check current thumbnail state
        $hasThumbnail = $this->checkThumbnailState($media);

        if ($hasThumbnail && ! $force) {
            $this->info('Thumbnail already exists. Use --force to regenerate anyway.');

            return Command::SUCCESS;
        }

        if ($diagnoseOnly) {
            $this->info('Diagnose mode: Skipping regeneration.');
            $this->info('Run without --diagnose to regenerate the thumbnail.');

            return $hasThumbnail ? Command::SUCCESS : Command::FAILURE;
        }

        // Step 5: Regenerate thumbnail
        return $this->regenerateThumbnail($media);
    }

    /**
     * Check if FFmpeg is available and working.
     */
    private function checkFfmpeg(): bool
    {
        $this->info('Checking FFmpeg availability...');

        $ffmpegPath = config('media-library.ffmpeg_path', '/usr/bin/ffmpeg');
        $this->line("  FFmpeg path: {$ffmpegPath}");

        // Check if file exists
        if (! file_exists($ffmpegPath)) {
            // Try to find it with 'which'
            $process = Process::fromShellCommandline('which ffmpeg');
            $process->run();
            $actualPath = trim($process->getOutput());

            if ($actualPath && file_exists($actualPath)) {
                $this->warn("  FFmpeg not at configured path, but found at: {$actualPath}");
                $this->warn('  Consider updating FFMPEG_PATH env variable.');
                $ffmpegPath = $actualPath;
            } else {
                $this->error('  FFmpeg not found!');
                $this->error('  Install FFmpeg or set FFMPEG_PATH env variable.');

                return false;
            }
        }

        // Check FFmpeg version
        $process = Process::fromShellCommandline("{$ffmpegPath} -version");
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('  FFmpeg cannot be executed!');
            $this->error('  Error: '.$process->getErrorOutput());

            return false;
        }

        $version = explode("\n", $process->getOutput())[0] ?? 'Unknown';
        $this->info("  FFmpeg OK: {$version}");
        $this->newLine();

        return true;
    }

    /**
     * Get the hero banner media from PlatformSettings.
     */
    private function getHeroBannerMedia(): ?Media
    {
        $this->info('Checking hero banner media...');

        $settings = PlatformSettings::with('media')->first();

        if (! $settings) {
            $this->error('  PlatformSettings not found!');

            return null;
        }

        $media = $settings->getFirstMedia('hero_banner');

        if (! $media) {
            $this->error('  No hero banner media uploaded!');
            $this->info('  Upload a video via Admin Panel → Platform Settings → Hero Banner.');

            return null;
        }

        $this->line("  Media ID: {$media->id}");
        $this->line("  File: {$media->file_name}");
        $this->line("  MIME: {$media->mime_type}");
        $this->line("  Size: ".number_format($media->size / 1024 / 1024, 2).' MB');
        $this->line("  Disk: {$media->disk}");
        $this->newLine();

        return $media;
    }

    /**
     * Check if the media is a video.
     */
    private function isVideo(Media $media): bool
    {
        $this->info('Checking media type...');

        $isVideo = str_starts_with($media->mime_type, 'video/');

        if (! $isVideo) {
            $this->info('  Hero banner is an IMAGE, not a video.');
            $this->info('  No thumbnail extraction needed - the image itself is used as poster.');

            return false;
        }

        $this->info('  Media is a VIDEO - thumbnail extraction applicable.');
        $this->newLine();

        return true;
    }

    /**
     * Check the current thumbnail state.
     */
    private function checkThumbnailState(Media $media): bool
    {
        $this->info('Checking thumbnail conversion state...');

        $hasConversion = $media->hasGeneratedConversion('thumbnail');
        $this->line('  hasGeneratedConversion("thumbnail"): '.($hasConversion ? 'true' : 'false'));

        if ($hasConversion) {
            $thumbnailUrl = $media->getUrl('thumbnail');
            $this->info("  Thumbnail URL: {$thumbnailUrl}");

            // Check if file actually exists on disk
            $thumbnailPath = $media->getPath('thumbnail');
            $this->line("  Thumbnail path: {$thumbnailPath}");

            // For remote storage, we can't easily check file existence
            // Just report the URL exists in the conversions array
            $this->info('  Thumbnail appears to be generated correctly.');
        } else {
            $this->warn('  Thumbnail conversion NOT found!');
            $this->warn('  This is why the fallback image is showing.');

            // Check what conversions exist
            $conversions = $media->generated_conversions ?? [];
            $this->line('  Existing conversions: '.($conversions ? json_encode($conversions) : 'none'));
        }

        $this->newLine();

        return $hasConversion;
    }

    /**
     * Regenerate the thumbnail for the video.
     */
    private function regenerateThumbnail(Media $media): int
    {
        $this->info('Regenerating thumbnail...');

        try {
            // Re-register media conversions
            $media->model->registerMediaConversions($media);

            // Get the thumbnail conversion
            $conversionCollection = app(ConversionCollection::class);
            $conversions = $conversionCollection->createForMedia($media)
                ->filter(fn ($conversion) => $conversion->getName() === 'thumbnail');

            if ($conversions->isEmpty()) {
                $this->error('  No thumbnail conversion found in model definition!');
                $this->error('  Check PlatformSettings::registerMediaConversions()');

                return Command::FAILURE;
            }

            $this->line('  Conversion definition found.');
            $this->line('  Executing FFmpeg extraction...');

            // Run the conversion synchronously
            $job = new PerformConversionsJob($conversions, $media, true);
            $job->handle();

            // Refresh and verify
            $media->refresh();

            if ($media->hasGeneratedConversion('thumbnail')) {
                $thumbnailUrl = $media->getUrl('thumbnail');
                $this->newLine();
                $this->info('✓ Thumbnail regenerated successfully!');
                $this->info("  URL: {$thumbnailUrl}");
                $this->newLine();
                $this->info('Next steps:');
                $this->line('  1. Clear Laravel cache: php artisan cache:clear');
                $this->line('  2. Verify API returns thumbnail: curl /api/v1/platform/settings | jq .data.branding.heroBannerThumbnail');
                $this->line('  3. Hard refresh homepage in browser (Cmd+Shift+R)');

                return Command::SUCCESS;
            }

            $this->error('  Thumbnail generation completed but hasGeneratedConversion still returns false!');
            $this->error('  This indicates a storage or database sync issue.');

            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error('  Exception during thumbnail generation:');
            $this->error('  '.$e->getMessage());
            $this->newLine();
            $this->error('  Stack trace:');
            $this->line('  '.$e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
