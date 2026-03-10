<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadExternalImages extends Command
{
    protected $signature = 'app:download-external-images';

    protected $description = 'Download external images (evasiondjerba.com) and store locally';

    public function handle(): int
    {
        $media = Media::where('url', 'like', '%evasiondjerba.com%')->get();

        if ($media->isEmpty()) {
            $this->info('No external images found to download.');
            return self::SUCCESS;
        }

        $this->info("Found {$media->count()} external images to download.");

        $downloaded = 0;
        $failed = 0;

        foreach ($media as $item) {
            $this->line("Downloading: {$item->url}");

            try {
                $response = Http::timeout(30)->get($item->url);

                if (! $response->successful()) {
                    $this->warn("  Failed (HTTP {$response->status()}): {$item->url}");
                    $failed++;
                    continue;
                }

                // Get file extension from URL
                $extension = pathinfo(parse_url($item->url, PHP_URL_PATH), PATHINFO_EXTENSION);
                if (! $extension) {
                    $extension = 'jpg';
                }

                // Generate a unique filename
                $listingSlug = $item->mediable?->slug ?? 'unknown';
                $directory = "listings/{$listingSlug}";
                $filename = Str::uuid() . '.' . $extension;
                $path = "{$directory}/{$filename}";

                // Store in public disk
                Storage::disk('public')->makeDirectory($directory);
                Storage::disk('public')->put($path, $response->body());

                // Update the media record with the local URL
                $localUrl = config('app.url') . '/storage/' . $path;
                $item->update(['url' => $localUrl]);

                $this->info("  Saved: {$localUrl}");
                $downloaded++;
            } catch (\Exception $e) {
                $this->error("  Error: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Done! Downloaded: {$downloaded}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
