@props([
    'title' => '',
    'subtitle' => '',
    'tag' => '',
    'primaryButtonLabel' => '',
    'primaryButtonUrl' => '',
    'secondaryButtonLabel' => '',
    'secondaryButtonUrl' => '',
    'backgroundColour' => 'primary'
])

@php
    $bgColors = [
        'primary' => 'from-[#0D642E] to-transparent',
        'secondary' => 'from-[#8BC34A] to-transparent',
        'accent' => 'from-[#f5f0d1] to-transparent',
        'dark' => 'from-black to-transparent',
    ];

    $textColors = [
        'primary' => 'text-white',
        'secondary' => 'text-white',
        'accent' => 'text-primary',
        'dark' => 'text-white',
    ];

    $bgGradient = $bgColors[$backgroundColour] ?? $bgColors['primary'];
    $textColor = $textColors[$backgroundColour] ?? $textColors['primary'];
@endphp

<section class="promo-banner-block relative overflow-hidden h-[500px] flex items-center">
    {!! /* Background Gradient */ !!}
    <div class="absolute inset-0 bg-gradient-to-r {{ $bgGradient }}"></div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-3xl">
            @if($tag)
                <span class="inline-block bg-[#8BC34A] text-[#0D642E] px-4 py-2 rounded-full text-sm font-semibold mb-4">
                    {{ $tag }}
                </span>
            @endif

            <h2 class="text-5xl md:text-6xl font-display font-bold {{ $textColor }} mb-4">
                {{ $title }}
            </h2>

            @if($subtitle)
                <p class="text-xl {{ $textColor }} mb-8 opacity-90">
                    {{ $subtitle }}
                </p>
            @endif

            <div class="flex flex-wrap gap-4">
                @if($primaryButtonLabel && $primaryButtonUrl)
                    <a href="{{ $primaryButtonUrl }}"
                       class="inline-flex items-center gap-2 bg-white text-primary px-8 py-4 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                        {{ $primaryButtonLabel }}
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                @endif

                @if($secondaryButtonLabel && $secondaryButtonUrl)
                    <a href="{{ $secondaryButtonUrl }}"
                       class="inline-flex items-center gap-2 border-2 border-white {{ $textColor }} px-8 py-4 rounded-lg font-semibold hover:bg-white/10 transition-colors">
                        {{ $secondaryButtonLabel }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
