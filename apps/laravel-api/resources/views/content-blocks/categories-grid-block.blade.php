@props(['categories' => []])

<section class="categories-grid-block py-16">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($categories as $category)
                <a href="{{ $category['url'] ?? '#' }}"
                   class="category-card group relative overflow-hidden rounded-lg">
                    {!! /* Category Image */ !!}
                    <div class="relative h-48 overflow-hidden">
                        <img src="{{ $category['image'] ?? '' }}"
                             alt="{{ $category['name'] ?? '' }}"
                             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">

                        {!! /* Dark Overlay on Hover */ !!}
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>

                    {!! /* Category Footer */ !!}
                    <div class="bg-[#fcfaf2] p-4">
                        <h3 class="font-display font-semibold text-lg text-primary">
                            {{ $category['name'] ?? 'Category' }}
                        </h3>

                        @if(isset($category['count']) && $category['count'] > 0)
                            <p class="text-sm text-gray-600 mt-1">
                                {{ $category['count'] }} {{ Str::plural('experience', $category['count']) }}
                            </p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
