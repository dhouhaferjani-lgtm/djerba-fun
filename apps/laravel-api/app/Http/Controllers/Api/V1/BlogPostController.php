<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogPostResource;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlogPostController extends Controller
{
    /**
     * Display a listing of published blog posts.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = BlogPost::with(['author', 'category'])
            ->published()
            ->orderBy('published_at', 'desc');

        // Filter by category
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Filter by tag
        if ($request->has('tag')) {
            $query->whereJsonContains('tags', $request->tag);
        }

        // Filter featured only
        if ($request->boolean('featured')) {
            $query->featured();
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('excerpt', 'ilike', "%{$search}%")
                    ->orWhere('content', 'ilike', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 12);
        $posts = $query->paginate($perPage);

        return BlogPostResource::collection($posts);
    }

    /**
     * Display the specified blog post.
     */
    public function show(string $slug): BlogPostResource
    {
        $post = BlogPost::with(['author', 'category'])
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        // Increment views count
        $post->incrementViews();

        return new BlogPostResource($post);
    }

    /**
     * Get featured posts for homepage.
     */
    public function featured(Request $request): AnonymousResourceCollection
    {
        $limit = $request->input('limit', 3);

        $posts = BlogPost::with(['author', 'category'])
            ->published()
            ->featured()
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();

        return BlogPostResource::collection($posts);
    }

    /**
     * Get related posts.
     */
    public function related(string $slug): AnonymousResourceCollection
    {
        $post = BlogPost::where('slug', $slug)->firstOrFail();

        $relatedPosts = BlogPost::with(['author', 'category'])
            ->published()
            ->where('id', '!=', $post->id)
            ->where(function ($query) use ($post) {
                // Same category or shared tags
                $query->where('blog_category_id', $post->blog_category_id);

                if ($post->tags) {
                    foreach ($post->tags as $tag) {
                        $query->orWhereJsonContains('tags', $tag);
                    }
                }
            })
            ->orderBy('published_at', 'desc')
            ->limit(3)
            ->get();

        return BlogPostResource::collection($relatedPosts);
    }
}
