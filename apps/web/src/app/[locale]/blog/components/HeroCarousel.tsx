'use client';

import { useState, useEffect } from 'react';
import Image from 'next/image';

interface HeroCarouselProps {
  images: string[];
  heroLabel: string;
  heroTitle: string;
  heroSubtitle: string;
}

export function HeroCarousel({ images, heroLabel, heroTitle, heroSubtitle }: HeroCarouselProps) {
  const [currentIndex, setCurrentIndex] = useState(0);

  useEffect(() => {
    if (images.length <= 1) return;

    const interval = setInterval(() => {
      setCurrentIndex((prev) => (prev + 1) % images.length);
    }, 4000);

    return () => clearInterval(interval);
  }, [images.length]);

  return (
    <section className="relative h-[400px] overflow-hidden">
      {/* Background Images */}
      {images.length > 0 ? (
        images.map((image, index) => (
          <div
            key={image}
            className={`absolute inset-0 transition-opacity duration-1000 ${
              index === currentIndex ? 'opacity-100' : 'opacity-0'
            }`}
          >
            <Image src={image} alt="" fill className="object-cover" priority={index === 0} />
            {/* Dark overlay for text readability */}
            <div className="absolute inset-0 bg-black/50" />
          </div>
        ))
      ) : (
        // Fallback to green background if no images
        <div className="absolute inset-0 bg-primary" />
      )}

      {/* Content */}
      <div className="relative z-10 h-full flex items-center justify-center">
        <div className="container mx-auto px-4 text-center">
          <p className="text-secondary text-sm font-semibold uppercase tracking-wide mb-2">
            {heroLabel}
          </p>
          <h1 className="text-4xl md:text-5xl font-display font-bold text-white mb-4">
            {heroTitle}
          </h1>
          <p className="text-white/90 text-lg max-w-2xl mx-auto">{heroSubtitle}</p>
        </div>
      </div>

      {/* Carousel Indicators */}
      {images.length > 1 && (
        <div className="absolute bottom-4 left-1/2 -translate-x-1/2 z-10 flex gap-2">
          {images.map((_, index) => (
            <button
              key={index}
              onClick={() => setCurrentIndex(index)}
              className={`w-2 h-2 rounded-full transition-all ${
                index === currentIndex ? 'bg-white w-6' : 'bg-white/50'
              }`}
              aria-label={`Go to slide ${index + 1}`}
            />
          ))}
        </div>
      )}
    </section>
  );
}
