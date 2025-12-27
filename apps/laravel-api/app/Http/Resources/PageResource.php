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
        $locale = $request->input('locale', 'en');

        return [
            'id' => $this->id,
            'code' => $this->code,
            'slug' => $this->getTranslation('slug', $locale),
            'title' => $this->getTranslation('title', $locale),
            'intro' => $this->getTranslation('intro', $locale),

            // Hero image
            'heroImage' => $this->getFirstMediaUrl('hero_image'),
            'heroImageCopyright' => $this->getTranslation('hero_image_copyright', $locale),
            'heroImageTitle' => $this->getTranslation('hero_image_title', $locale),
            'heroCallToActions' => is_array($this->hero_call_to_actions) ? $this->toCamelCase($this->hero_call_to_actions) : $this->hero_call_to_actions,

            // SEO
            'seoTitle' => $this->getTranslation('seo_title', $locale),
            'seoDescription' => $this->getTranslation('seo_description', $locale),
            'seoKeywords' => $this->getTranslation('seo_keywords', $locale),
            'seoImage' => $this->getFirstMediaUrl('seo_image'),

            // Overview
            'overviewTitle' => $this->getTranslation('overview_title', $locale),
            'overviewDescription' => $this->getTranslation('overview_description', $locale),
            'overviewImage' => $this->getFirstMediaUrl('overview_image'),

            // Content blocks
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
