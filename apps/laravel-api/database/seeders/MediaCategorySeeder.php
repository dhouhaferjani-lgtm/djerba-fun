<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MediaCategory;
use App\Models\Listing;
use Illuminate\Database\Seeder;

class MediaCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $listings = Listing::with('media')->get();
        $totalUpdated = 0;

        foreach ($listings as $listing) {
            $media = $listing->media()->orderBy('order')->get();

            if ($media->isEmpty()) {
                continue;
            }

            // First image becomes hero
            $firstImage = $media->first();

            if ($firstImage && $firstImage->category !== MediaCategory::HERO) {
                $firstImage->update(['category' => MediaCategory::HERO]);
                $totalUpdated++;
            }

            // Next 3 images become featured (if they exist)
            $featuredImages = $media->skip(1)->take(3);

            foreach ($featuredImages as $image) {
                if ($image->category !== MediaCategory::FEATURED) {
                    $image->update(['category' => MediaCategory::FEATURED]);
                    $totalUpdated++;
                }
            }

            // Remaining images stay as gallery (which is the default)
            $galleryImages = $media->skip(4);

            foreach ($galleryImages as $image) {
                if ($image->category !== MediaCategory::GALLERY) {
                    $image->update(['category' => MediaCategory::GALLERY]);
                    $totalUpdated++;
                }
            }
        }

        $this->command->info('MediaCategory seeder completed! Updated ' . $totalUpdated . ' media items across ' . $listings->count() . ' listings.');
    }
}
