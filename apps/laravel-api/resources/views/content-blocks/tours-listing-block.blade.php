@props(['listingType' => 'all', 'count' => 6, 'sortBy' => 'created_at', 'style' => 'grid'])

@php
    use App\Models\Listing;

    // Build query based on listing type
    $query = Listing::query()->where('status', 'active');

    if ($listingType !== 'all') {
        $query->where('service_type', $listingType);
    }

    // Apply sorting
    if ($sortBy === 'price') {
        $query->orderBy('price_amount', 'asc');
    } elseif ($sortBy === '-price') {
        $query->orderBy('price_amount', 'desc');
    } elseif ($sortBy === 'title') {
        $query->orderBy('title', 'asc');
    } else {
        $query->orderBy('created_at', 'desc');
    }

    // Fetch listings
    $listings = $query->with(['location', 'media'])
        ->take($count)
        ->get();
@endphp

<div class="tours-listing-block tours-listing-block--{{ $style }}" data-block-type="tours-listing">
    @if ($style === 'grid')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($listings as $listing)
                <article class="listing-card">
                    @if ($listing->getFirstMediaUrl('images'))
                        <div class="listing-image">
                            <img src="{{ $listing->getFirstMediaUrl('images', 'medium') }}"
                                 alt="{{ $listing->title }}"
                                 class="w-full h-64 object-cover rounded-lg">
                        </div>
                    @endif

                    <div class="listing-content p-4">
                        <span class="listing-type text-xs uppercase tracking-wide text-gray-500">
                            {{ $listing->service_type }}
                        </span>

                        <h3 class="listing-title text-xl font-bold mt-2 mb-2">
                            <a href="{{ route('listings.show', $listing->slug) }}"
                               class="hover:text-primary">
                                {{ $listing->title }}
                            </a>
                        </h3>

                        @if ($listing->location)
                            <p class="listing-location text-sm text-gray-600 mb-2">
                                📍 {{ $listing->location->city }}, {{ $listing->location->country }}
                            </p>
                        @endif

                        <div class="listing-price font-semibold text-lg text-primary">
                            {{ number_format($listing->price_amount / 100, 2) }} {{ $listing->price_currency }}
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

    @elseif ($style === 'carousel')
        <div class="swiper tours-carousel">
            <div class="swiper-wrapper">
                @foreach ($listings as $listing)
                    <div class="swiper-slide">
                        <article class="listing-card">
                            @if ($listing->getFirstMediaUrl('images'))
                                <div class="listing-image">
                                    <img src="{{ $listing->getFirstMediaUrl('images', 'medium') }}"
                                         alt="{{ $listing->title }}"
                                         class="w-full h-64 object-cover rounded-lg">
                                </div>
                            @endif

                            <div class="listing-content p-4">
                                <span class="listing-type text-xs uppercase tracking-wide text-gray-500">
                                    {{ $listing->service_type }}
                                </span>

                                <h3 class="listing-title text-xl font-bold mt-2 mb-2">
                                    <a href="{{ route('listings.show', $listing->slug) }}"
                                       class="hover:text-primary">
                                        {{ $listing->title }}
                                    </a>
                                </h3>

                                @if ($listing->location)
                                    <p class="listing-location text-sm text-gray-600 mb-2">
                                        📍 {{ $listing->location->city }}, {{ $listing->location->country }}
                                    </p>
                                @endif

                                <div class="listing-price font-semibold text-lg text-primary">
                                    {{ number_format($listing->price_amount / 100, 2) }} {{ $listing->price_currency }}
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>

    @else
        {{-- List style --}}
        <div class="space-y-4">
            @foreach ($listings as $listing)
                <article class="listing-card flex gap-4 border-b pb-4">
                    @if ($listing->getFirstMediaUrl('images'))
                        <div class="listing-image flex-shrink-0">
                            <img src="{{ $listing->getFirstMediaUrl('images', 'thumbnail') }}"
                                 alt="{{ $listing->title }}"
                                 class="w-32 h-32 object-cover rounded">
                        </div>
                    @endif

                    <div class="listing-content flex-1">
                        <span class="listing-type text-xs uppercase tracking-wide text-gray-500">
                            {{ $listing->service_type }}
                        </span>

                        <h3 class="listing-title text-xl font-bold mt-1 mb-2">
                            <a href="{{ route('listings.show', $listing->slug) }}"
                               class="hover:text-primary">
                                {{ $listing->title }}
                            </a>
                        </h3>

                        @if ($listing->location)
                            <p class="listing-location text-sm text-gray-600 mb-2">
                                📍 {{ $listing->location->city }}, {{ $listing->location->country }}
                            </p>
                        @endif

                        <div class="listing-price font-semibold text-lg text-primary">
                            {{ number_format($listing->price_amount / 100, 2) }} {{ $listing->price_currency }}
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
