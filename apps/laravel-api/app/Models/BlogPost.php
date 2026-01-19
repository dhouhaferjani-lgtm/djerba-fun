<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class BlogPost extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    /**
     * The attributes that are translatable.
     */
    public array $translatable = [
        'title',
        'excerpt',
        'content',
        'seo_title',
        'seo_description',
    ];

    protected $fillable = [
        'author_id',
        'blog_category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'tags',
        'seo_title',
        'seo_description',
        'read_time_minutes',
        'views_count',
        'is_featured',
        'status',
        'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (BlogPost $post) {
            // Auto-generate slug
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }

            // Auto-calculate read time
            if (empty($post->read_time_minutes) && $post->content) {
                $post->read_time_minutes = self::calculateReadTime($post->content);
            }

            // Auto-set excerpt if not provided
            if (empty($post->excerpt) && $post->content) {
                $post->excerpt = Str::limit(strip_tags($post->content), 200);
            }

            // Auto-set SEO title if not provided
            if (empty($post->seo_title)) {
                $post->seo_title = $post->title;
            }
        });

        static::updating(function (BlogPost $post) {
            // Recalculate read time if content changed
            if ($post->isDirty('content')) {
                $post->read_time_minutes = self::calculateReadTime($post->content);
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the author of the post.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the category of the post.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    /**
     * Scope published posts.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope featured posts.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope by category.
     */
    public function scopeByCategory($query, $categorySlug)
    {
        return $query->whereHas('category', function ($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        });
    }

    /**
     * Increment views count.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Calculate read time from content.
     */
    protected static function calculateReadTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        $minutes = ceil($wordCount / 200); // Average reading speed: 200 words/minute

        return max(1, $minutes); // Minimum 1 minute
    }

    /**
     * Check if post is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    /**
     * Get the full URL for the featured image.
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (! $this->featured_image) {
            return null;
        }

        return Storage::disk('public')->url($this->featured_image);
    }
}
