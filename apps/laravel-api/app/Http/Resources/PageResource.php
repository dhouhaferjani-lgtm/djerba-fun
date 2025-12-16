<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
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
            'hero_image' => $this->getFirstMediaUrl('hero_image'),
            'hero_image_copyright' => $this->getTranslation('hero_image_copyright', $locale),
            'hero_image_title' => $this->getTranslation('hero_image_title', $locale),
            'hero_call_to_actions' => $this->hero_call_to_actions,

            // SEO
            'seo_title' => $this->getTranslation('seo_title', $locale),
            'seo_description' => $this->getTranslation('seo_description', $locale),
            'seo_keywords' => $this->getTranslation('seo_keywords', $locale),
            'seo_image' => $this->getFirstMediaUrl('seo_image'),

            // Overview
            'overview_title' => $this->getTranslation('overview_title', $locale),
            'overview_description' => $this->getTranslation('overview_description', $locale),
            'overview_image' => $this->getFirstMediaUrl('overview_image'),

            // Content blocks
            'content_blocks' => $this->transformContentBlocks($locale),

            // Publishing
            'publishing_begins_at' => $this->publishing_begins_at?->toISOString(),
            'publishing_ends_at' => $this->publishing_ends_at?->toISOString(),

            // Metadata
            'author' => $this->whenLoaded('author', function () {
                return [
                    'id' => $this->author->id,
                    'name' => $this->author->name,
                ];
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    /**
     * Transform content blocks for API consumption.
     */
    protected function transformContentBlocks(string $locale): array
    {
        if (!$this->content_blocks) {
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
