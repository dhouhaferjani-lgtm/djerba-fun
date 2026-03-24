<?php

declare(strict_types=1);

namespace App\Models;

use Statikbe\FilamentFlexibleContentBlockPages\Models\Page as VendorPage;

/**
 * Custom Page model that extends the vendor's Page model.
 *
 * Adds destination-style columns for fixed-section content:
 * - description_en/fr (replaces JSON intro for simple pages)
 * - seo_title_en/fr, seo_description_en/fr, seo_text_en/fr
 * - highlights, key_facts, gallery, points_of_interest (JSON arrays)
 *
 * @property string|null $description_en
 * @property string|null $description_fr
 * @property string|null $link
 * @property string|null $seo_title_en
 * @property string|null $seo_title_fr
 * @property string|null $seo_description_en
 * @property string|null $seo_description_fr
 * @property string|null $seo_text_en
 * @property string|null $seo_text_fr
 * @property array|null $highlights
 * @property array|null $key_facts
 * @property array|null $gallery
 * @property array|null $points_of_interest
 */
class Page extends VendorPage
{
    /**
     * The attributes that are mass assignable.
     *
     * IMPORTANT: When $fillable is defined in a child class, it completely
     * OVERRIDES the parent's $fillable - it doesn't merge! So we must include
     * all parent fields here too.
     */
    protected $fillable = [
        // Inherited from parent model (vendor Page)
        'is_undeletable',
        'code',
        'title',
        'slug',
        'intro',
        'content_blocks',
        'hero_image_title',
        'hero_image_copyright',
        'hero_call_to_actions',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'overview_title',
        'overview_description',
        'author_id',
        'publishing_begins_at',
        'publishing_ends_at',
        // New destination-style fields
        'description_en',
        'description_fr',
        'link',
        'seo_title_en',
        'seo_title_fr',
        'seo_description_en',
        'seo_description_fr',
        'seo_text_en',
        'seo_text_fr',
        'highlights',
        'key_facts',
        'gallery',
        'points_of_interest',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'highlights' => 'array',
            'key_facts' => 'array',
            'gallery' => 'array',
            'points_of_interest' => 'array',
        ]);
    }

    /**
     * Get description for a specific locale.
     */
    public function getDescription(string $locale = 'en'): ?string
    {
        return $locale === 'fr' ? $this->description_fr : $this->description_en;
    }

    /**
     * Get SEO title for a specific locale.
     */
    public function getSeoTitleForLocale(string $locale = 'en'): ?string
    {
        return $locale === 'fr' ? $this->seo_title_fr : $this->seo_title_en;
    }

    /**
     * Get SEO description for a specific locale.
     */
    public function getSeoDescriptionForLocale(string $locale = 'en'): ?string
    {
        return $locale === 'fr' ? $this->seo_description_fr : $this->seo_description_en;
    }

    /**
     * Get SEO text for a specific locale.
     */
    public function getSeoTextForLocale(string $locale = 'en'): ?string
    {
        return $locale === 'fr' ? $this->seo_text_fr : $this->seo_text_en;
    }

    /**
     * Get highlights with localized titles/descriptions.
     */
    public function getLocalizedHighlights(string $locale = 'en'): array
    {
        if (! $this->highlights) {
            return [];
        }

        return array_map(function ($highlight) use ($locale) {
            return [
                'icon' => $highlight['icon'] ?? null,
                'title' => $locale === 'fr'
                    ? ($highlight['title_fr'] ?? $highlight['title_en'] ?? '')
                    : ($highlight['title_en'] ?? ''),
                'description' => $locale === 'fr'
                    ? ($highlight['description_fr'] ?? $highlight['description_en'] ?? '')
                    : ($highlight['description_en'] ?? ''),
            ];
        }, $this->highlights);
    }

    /**
     * Get key facts with localized labels.
     */
    public function getLocalizedKeyFacts(string $locale = 'en'): array
    {
        if (! $this->key_facts) {
            return [];
        }

        return array_map(function ($fact) use ($locale) {
            return [
                'icon' => $fact['icon'] ?? null,
                'label' => $locale === 'fr'
                    ? ($fact['label_fr'] ?? $fact['label_en'] ?? '')
                    : ($fact['label_en'] ?? ''),
                'value' => $fact['value'] ?? '',
            ];
        }, $this->key_facts);
    }

    /**
     * Get gallery with localized alt/captions.
     */
    public function getLocalizedGallery(string $locale = 'en'): array
    {
        if (! $this->gallery) {
            return [];
        }

        return array_map(function ($item) use ($locale) {
            return [
                'image' => $item['image'] ?? null,
                'alt' => $locale === 'fr'
                    ? ($item['alt_fr'] ?? $item['alt_en'] ?? '')
                    : ($item['alt_en'] ?? ''),
                'caption' => $locale === 'fr'
                    ? ($item['caption_fr'] ?? $item['caption_en'] ?? '')
                    : ($item['caption_en'] ?? ''),
            ];
        }, $this->gallery);
    }

    /**
     * Get points of interest with localized names/descriptions.
     */
    public function getLocalizedPointsOfInterest(string $locale = 'en'): array
    {
        if (! $this->points_of_interest) {
            return [];
        }

        return array_map(function ($poi) use ($locale) {
            return [
                'name' => $locale === 'fr'
                    ? ($poi['name_fr'] ?? $poi['name_en'] ?? '')
                    : ($poi['name_en'] ?? ''),
                'description' => $locale === 'fr'
                    ? ($poi['description_fr'] ?? $poi['description_en'] ?? '')
                    : ($poi['description_en'] ?? ''),
            ];
        }, $this->points_of_interest);
    }
}
