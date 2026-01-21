<?php

namespace App\Filament\Admin\Resources\BlogPostResource\Pages;

use App\Filament\Admin\Resources\BlogPostResource;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\EditRecord\Concerns\Translatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class EditBlogPost extends EditRecord
{
    use Translatable;

    protected static string $resource = BlogPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label(__('filament.actions.preview'))
                ->icon('heroicon-o-eye')
                ->color('info')
                ->modalHeading(__('filament.actions.preview'))
                ->modalWidth('screen')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('filament.actions.close'))
                ->modalContent(fn () => new HtmlString(
                    view('filament.pages.blog-preview', [
                        'title' => $this->data['title'] ?? $this->record->title ?? '',
                        'content' => $this->data['content'] ?? $this->record->content ?? '',
                        'excerpt' => $this->data['excerpt'] ?? $this->record->excerpt ?? '',
                        'heroImages' => $this->getHeroImageUrls(),
                        'category' => $this->getCategory(),
                        'tags' => $this->data['tags'] ?? [],
                        'author' => auth()->user(),
                        'readTimeMinutes' => $this->calculateReadTime(),
                        'publishedAt' => now()->format('M d, Y'),
                        'relatedPosts' => $this->getRelatedPosts(),
                    ])->render()
                )),
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    /**
     * Get hero image URLs from form data or existing record.
     */
    protected function getHeroImageUrls(): array
    {
        try {
            $images = $this->data['hero_images'] ?? $this->record->hero_images ?? [];

            if (empty($images) || ! is_array($images)) {
                return [];
            }

            // Use array_values to ensure sequential numeric keys (0, 1, 2...)
            // Livewire file uploads use UUID keys which cause "non-numeric value" errors
            return array_values(array_filter(array_map(function ($image) {
                if (! is_string($image)) {
                    return null;
                }
                if (str_starts_with($image, 'http')) {
                    return $image;
                }

                return Storage::disk('public')->url($image);
            }, $images)));
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get category from form data.
     */
    protected function getCategory(): ?BlogCategory
    {
        try {
            $categoryId = $this->data['blog_category_id'] ?? $this->record->blog_category_id ?? null;

            if (! $categoryId) {
                return null;
            }

            return BlogCategory::find($categoryId);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Calculate read time from content.
     */
    protected function calculateReadTime(): int
    {
        $content = $this->data['content'] ?? $this->record->content ?? '';

        if (empty($content)) {
            return 1;
        }

        $wordCount = str_word_count(strip_tags($content));

        return max(1, (int) ceil($wordCount / 200));
    }

    /**
     * Get related posts from same category.
     */
    protected function getRelatedPosts(): Collection
    {
        try {
            $categoryId = $this->data['blog_category_id'] ?? $this->record->blog_category_id ?? null;

            if (! $categoryId) {
                return collect();
            }

            return BlogPost::where('blog_category_id', $categoryId)
                ->where('status', 'published')
                ->where('id', '!=', $this->record?->id)
                ->latest('published_at')
                ->take(3)
                ->get();
        } catch (\Exception $e) {
            return collect();
        }
    }
}
