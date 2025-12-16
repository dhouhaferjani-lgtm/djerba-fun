@props([
    'title' => '',
    'text' => '',
    'buttonLabel' => '',
    'buttonUrl' => '',
    'buttonVariant' => 'secondary'
])

@php
    $buttonClasses = [
        'primary' => 'bg-primary text-white hover:bg-primary-dark',
        'secondary' => 'bg-secondary text-primary hover:bg-secondary-dark',
        'white' => 'bg-white text-primary hover:bg-gray-100',
    ];

    $buttonClass = $buttonClasses[$buttonVariant] ?? $buttonClasses['secondary'];
@endphp

<section class="cta-with-blobs-block relative overflow-hidden bg-[#0D642E] py-20">
    {!! /* Decorative Blobs */ !!}
    <div class="absolute top-0 left-0 w-96 h-96 bg-[#8BC34A] rounded-full filter blur-3xl opacity-20"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-[#8BC34A] rounded-full filter blur-3xl opacity-20"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-[#8BC34A] rounded-full filter blur-3xl opacity-10"></div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-3xl mx-auto text-center">
            <h2 class="text-4xl md:text-5xl font-display font-bold text-white mb-6">
                {{ $title }}
            </h2>

            @if($text)
                <p class="text-xl text-white/90 mb-8">
                    {{ $text }}
                </p>
            @endif

            @if($buttonLabel && $buttonUrl)
                <a href="{{ $buttonUrl }}"
                   class="inline-flex items-center gap-2 {{ $buttonClass }} px-8 py-4 rounded-lg font-semibold transition-colors">
                    {{ $buttonLabel }}
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            @endif
        </div>
    </div>
</section>
