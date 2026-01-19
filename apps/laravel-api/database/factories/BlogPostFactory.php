<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlogPost>
 */
class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(6);

        return [
            'author_id' => User::factory()->state(['role' => UserRole::ADMIN->value]),
            'blog_category_id' => BlogCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->paragraph(2),
            'content' => fake()->paragraphs(5, true),
            'featured_image' => null,
            'tags' => fake()->words(3),
            'seo_title' => $title,
            'seo_description' => fake()->sentence(),
            'read_time_minutes' => fake()->numberBetween(2, 15),
            'views_count' => fake()->numberBetween(0, 1000),
            'is_featured' => false,
            'status' => 'draft',
            'published_at' => null,
        ];
    }

    /**
     * Indicate that the post is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Indicate that the post is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'published_at' => now()->addDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Indicate that the post is draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the post is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Create post with a specific author.
     */
    public function byAuthor(User $author): static
    {
        return $this->state(fn (array $attributes) => [
            'author_id' => $author->id,
        ]);
    }

    /**
     * Create post with a specific category.
     */
    public function inCategory(BlogCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'blog_category_id' => $category->id,
        ]);
    }
}
