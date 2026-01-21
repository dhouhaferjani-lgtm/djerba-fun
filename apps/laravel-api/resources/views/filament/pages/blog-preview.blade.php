<div class="blog-preview bg-white dark:bg-gray-900 overflow-y-auto max-h-[90vh]"
     x-data="{
        current: 0,
        images: {{ Js::from($heroImages ?? []) }},
        paused: false,
        init() {
            if (this.images.length > 1) {
                setInterval(() => {
                    if (!this.paused) {
                        this.current = (this.current + 1) % this.images.length;
                    }
                }, 5000);
            }
        }
     }">

    {{-- Hero Section --}}
    <div class="relative min-h-[60vh] flex items-center justify-center bg-gray-900"
         @mouseenter="paused = true"
         @mouseleave="paused = false">

        {{-- Hero Images with Fade Carousel --}}
        @if(!empty($heroImages))
            @foreach($heroImages as $index => $image)
                <img
                    src="{{ $image }}"
                    alt="{{ $title }} - Image {{ $index + 1 }}"
                    class="absolute inset-0 w-full h-full object-cover transition-opacity duration-1000"
                    :class="current === {{ $index }} ? 'opacity-100' : 'opacity-0'"
                />
            @endforeach
        @else
            {{-- Placeholder when no images --}}
            <div class="absolute inset-0 bg-gradient-to-br from-gray-700 to-gray-900"></div>
        @endif

        {{-- Dark Overlay --}}
        <div class="absolute inset-0 bg-black/50"></div>

        {{-- Content Overlay --}}
        <div class="relative z-10 container mx-auto px-4 max-w-4xl text-center text-white py-20">
            {{-- Category Badge --}}
            @if($category)
                <span class="inline-block px-4 py-2 rounded-full text-sm font-semibold mb-4"
                      style="background-color: {{ $category->color ?? '#0D642E' }}">
                    {{ $category->name }}
                </span>
            @endif

            {{-- Title --}}
            @if($title)
                <h1 class="text-4xl md:text-5xl font-bold mb-6 leading-tight">
                    {{ $title }}
                </h1>
            @else
                <p class="text-2xl text-gray-300 italic mb-6">{{ __('filament.preview.no_title') }}</p>
            @endif

            {{-- Metadata Row --}}
            <div class="flex items-center justify-center gap-4 text-sm text-gray-200 flex-wrap">
                @if($author)
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                        {{ $author->display_name ?? $author->name }}
                    </span>
                    <span class="text-gray-400">•</span>
                @endif
                <span>{{ $publishedAt }}</span>
                <span class="text-gray-400">•</span>
                <span>{{ $readTimeMinutes }} min read</span>
            </div>
        </div>

        {{-- View Photos Button (only if multiple images) --}}
        @if(count($heroImages ?? []) > 1)
            <div class="absolute bottom-6 right-6 z-20">
                <span class="flex items-center gap-2 bg-white/90 text-gray-900 px-4 py-2 rounded-full shadow-lg text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ __('filament.preview.view_photos', ['count' => count($heroImages)]) }}
                </span>
            </div>
        @endif

        {{-- Dot Indicators (only if multiple images) --}}
        @if(count($heroImages ?? []) > 1)
            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 flex gap-2">
                @foreach($heroImages as $index => $image)
                    <button
                        @click="current = {{ $index }}"
                        class="h-2 rounded-full transition-all duration-300"
                        :class="current === {{ $index }} ? 'bg-white w-6' : 'bg-white/50 hover:bg-white/75 w-2'"
                        aria-label="Go to image {{ $index + 1 }}"
                    ></button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Content Section --}}
    <div class="container mx-auto px-4 py-12 max-w-3xl">
        {{-- Excerpt --}}
        @if($excerpt)
            <p class="text-xl text-gray-600 dark:text-gray-300 mb-8 border-l-4 border-primary-500 pl-4 italic leading-relaxed">
                {{ $excerpt }}
            </p>
        @endif

        {{-- Main Content --}}
        @if($content)
            <div class="prose prose-lg dark:prose-invert max-w-none prose-headings:font-bold prose-a:text-primary-600 prose-img:rounded-lg">
                {!! $content !!}
            </div>
        @else
            <p class="text-gray-400 italic text-center py-12">{{ __('filament.preview.no_content') }}</p>
        @endif

        {{-- Tags Section --}}
        @if(!empty($tags))
            <div class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">
                    {{ __('filament.preview.tags') }}
                </h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($tags as $tag)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                            {{ $tag }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Author Bio Section --}}
        @if($author)
            <div class="mt-12 p-6 bg-gray-50 dark:bg-gray-800 rounded-xl">
                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                            {{ strtoupper(substr($author->display_name ?? $author->name ?? 'A', 0, 1)) }}
                        </span>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ __('filament.preview.about_author') }}
                        </h3>
                        <p class="text-primary-600 dark:text-primary-400 font-medium">
                            {{ $author->display_name ?? $author->name }}
                        </p>
                        @if($author->bio ?? null)
                            <p class="mt-2 text-gray-600 dark:text-gray-300 text-sm">
                                {{ $author->bio }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Related Posts Section --}}
    @if($relatedPosts->isNotEmpty())
        <div class="bg-gray-50 dark:bg-gray-800 py-12">
            <div class="container mx-auto px-4 max-w-6xl">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                    {{ __('filament.preview.related_posts') }}
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($relatedPosts as $post)
                        <div class="bg-white dark:bg-gray-900 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                            {{-- Post Image --}}
                            <div class="aspect-video bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                @php
                                    $postImage = $post->hero_image_urls[0] ?? null;
                                @endphp
                                @if($postImage)
                                    <img src="{{ $postImage }}" alt="{{ $post->title }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            {{-- Post Content --}}
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white line-clamp-2 mb-2">
                                    {{ $post->title }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $post->published_at?->format('M d, Y') }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        {{-- Related Posts Placeholder --}}
        <div class="bg-gray-50 dark:bg-gray-800 py-12">
            <div class="container mx-auto px-4 max-w-6xl text-center">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                    {{ __('filament.preview.related_posts') }}
                </h2>
                <p class="text-gray-500 dark:text-gray-400">
                    {{ __('filament.preview.related_posts_hint') }}
                </p>
            </div>
        </div>
    @endif
</div>

<style>
    .blog-preview .prose figure {
        margin: 1.5rem 0;
    }
    .blog-preview .prose figure figcaption {
        text-align: center;
        font-size: 0.875rem;
        color: #6b7280;
        margin-top: 0.5rem;
        font-style: italic;
    }
    .blog-preview .prose img {
        max-width: 100%;
        height: auto;
        border-radius: 0.5rem;
    }
    .blog-preview .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
