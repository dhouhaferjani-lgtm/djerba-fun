<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class BlogPostResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->getTranslations('title'),
            'slug' => $this->slug,
            'excerpt' => $this->getTranslations('excerpt'),
            'content' => $this->getTranslations('content'),
            'featuredImage' => $this->featured_image_url,
            'heroImages' => $this->hero_image_urls,
            'heroImageCount' => count($this->hero_images ?? []),
            'tags' => $this->tags ?? [],
            'readTimeMinutes' => $this->read_time_minutes,
            'viewsCount' => $this->views_count,
            'isFeatured' => $this->is_featured,
            'status' => $this->status,
            'publishedAt' => $this->published_at?->toISOString(),
            'createdAt' => $this->created_at->toISOString(),
            'updatedAt' => $this->updated_at->toISOString(),

            // Relationships
            'author' => [
                'id' => $this->author->id,
                'name' => $this->author->display_name,
                'avatarUrl' => $this->author->avatar_url,
            ],
            'category' => $this->when($this->category, function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                    'color' => $this->category->color,
                ];
            }),

            // SEO - use translations with fallback
            'seo' => [
                'title' => $this->getTranslations('seo_title') ?: $this->getTranslations('title'),
                'description' => $this->getTranslations('seo_description') ?: $this->getTranslations('excerpt'),
            ],
        ];
    }
}
