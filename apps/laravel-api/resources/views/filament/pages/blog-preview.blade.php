<div class="blog-preview bg-white dark:bg-gray-900 rounded-lg overflow-hidden max-h-[70vh] overflow-y-auto">
    {{-- Featured Image --}}
    @if($featuredImage)
        <div class="w-full h-64 bg-gray-200 dark:bg-gray-700 overflow-hidden">
            <img
                src="{{ Storage::disk('public')->url($featuredImage) }}"
                alt="{{ $title }}"
                class="w-full h-full object-cover"
            />
        </div>
    @endif

    <div class="p-6 lg:p-8">
        {{-- Title --}}
        @if($title)
            <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                {{ $title }}
            </h1>
        @else
            <p class="text-gray-400 italic mb-4">{{ __('filament.preview.no_title') }}</p>
        @endif

        {{-- Excerpt --}}
        @if($excerpt)
            <p class="text-lg text-gray-600 dark:text-gray-300 mb-6 border-l-4 border-primary-500 pl-4 italic">
                {{ $excerpt }}
            </p>
        @endif

        {{-- Content --}}
        @if($content)
            <div class="prose prose-lg dark:prose-invert max-w-none">
                {!! $content !!}
            </div>
        @else
            <p class="text-gray-400 italic">{{ __('filament.preview.no_content') }}</p>
        @endif
    </div>
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
</style>
