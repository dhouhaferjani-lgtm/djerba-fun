<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PageResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->input('locale', $request->header('Accept-Language', 'fr'));

        // Map Accept-Language values to supported locales
        if (str_starts_with($locale, 'en')) {
            $locale = 'en';
        } else {
            $locale = 'fr'; // Default to French
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'slug' => $this->getTranslation('slug', $locale),
            'title' => $this->getTranslation('title', $locale),
            'intro' => $this->getTranslation('intro', $locale),

            // Destination-style description (per-locale column)
            'description' => $this->getDescription($locale),
            'link' => $this->link,

            // Hero image
            'heroImage' => $this->getFirstMediaUrl('hero_image'),
            'heroImageCopyright' => $this->getTranslation('hero_image_copyright', $locale),
            'heroImageTitle' => $this->getTranslation('hero_image_title', $locale),
            'heroCallToActions' => $this->transformHeroCallToActions($locale),

            // Destination-style SEO (per-locale columns)
            'seoTitle' => $this->getSeoTitleForLocale($locale) ?? $this->getTranslation('seo_title', $locale),
            'seoDescription' => $this->getSeoDescriptionForLocale($locale) ?? $this->getTranslation('seo_description', $locale),
            'seoText' => $this->getSeoTextForLocale($locale),
            'seoKeywords' => $this->getTranslation('seo_keywords', $locale),
            'seoImage' => $this->getFirstMediaUrl('seo_image'),

            // Overview
            'overviewTitle' => $this->getTranslation('overview_title', $locale),
            'overviewDescription' => $this->getTranslation('overview_description', $locale),
            'overviewImage' => $this->getFirstMediaUrl('overview_image'),

            // Destination-style content sections
            'highlights' => $this->getLocalizedHighlights($locale),
            'keyFacts' => $this->getLocalizedKeyFacts($locale),
            'gallery' => $this->getLocalizedGallery($locale),
            'pointsOfInterest' => $this->getLocalizedPointsOfInterest($locale),

            // Content blocks (legacy flexible blocks)
            'contentBlocks' => $this->transformContentBlocks($locale),

            // Publishing
            'publishingBeginsAt' => $this->publishing_begins_at?->toISOString(),
            'publishingEndsAt' => $this->publishing_ends_at?->toISOString(),

            // Metadata
            'author' => $this->whenLoaded('author', function () {
                return [
                    'id' => $this->author->id,
                    'name' => $this->author->name,
                ];
            }),
            'createdAt' => $this->created_at->toISOString(),
            'updatedAt' => $this->updated_at->toISOString(),
        ];
    }

    /**
     * Transform hero call to actions with localized button labels.
     */
    protected function transformHeroCallToActions(string $locale): array
    {
        if (! is_array($this->hero_call_to_actions)) {
            return [];
        }

        return array_map(function ($cta) use ($locale) {
            return [
                'ctaModel' => $cta['cta_model'] ?? 'url',
                'url' => $cta['url'] ?? '',
                'buttonStyle' => $cta['button_style'] ?? 'primary',
                'buttonLabel' => $cta['button_label'][$locale] ?? $cta['button_label']['en'] ?? '',
                'buttonOpenNewWindow' => $cta['button_open_new_window'] ?? false,
            ];
        }, $this->hero_call_to_actions);
    }

    /**
     * Transform content blocks for API consumption.
     */
    protected function transformContentBlocks(string $locale): array
    {
        if (! $this->content_blocks) {
            return [];
        }

        $blocks = [];

        foreach ($this->content_blocks as $block) {
            $blockType = $block['type'] ?? 'unknown';

            // Extract the block class name
            $blockClassName = class_basename($blockType);

            // Transform block data
            $blockData = [
                'type' => $blockClassName,
                'data' => $block['data'] ?? [],
            ];

            // Handle translatable fields if they exist
            if (isset($block['data'])) {
                foreach ($block['data'] as $key => $value) {
                    if (is_array($value) && isset($value[$locale])) {
                        $blockData['data'][$key] = $value[$locale];
                    }
                }
            }

            $blocks[] = $blockData;
        }

        return $blocks;
    }
}
