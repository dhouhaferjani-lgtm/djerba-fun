'use client';

import { useState, useEffect } from 'react';
import Image from 'next/image';
import { Images } from 'lucide-react';
import { cn } from '@/lib/utils/cn';

interface BlogHeroCarouselProps {
  images: string[];
  title: string;
  category?: { name: string; color: string } | null;
  author: { name: string };
  publishedAt: string;
  readTimeMinutes: number;
  onOpenLightbox: () => void;
}

export function BlogHeroCarousel({
  images,
  title,
  category,
  author,
  publishedAt,
  readTimeMinutes,
  onOpenLightbox,
}: BlogHeroCarouselProps) {
  const [currentIndex, setCurrentIndex] = useState(0);

  // Auto-rotate every 5 seconds (only if multiple images)
  useEffect(() => {
    if (images.length <= 1) return;

    const interval = setInterval(() => {
      setCurrentIndex((prev) => (prev + 1) % images.length);
    }, 5000);

    return () => clearInterval(interval);
  }, [images.length]);

  // Pause rotation on hover
  const [isPaused, setIsPaused] = useState(false);

  useEffect(() => {
    if (images.length <= 1 || isPaused) return;

    const interval = setInterval(() => {
      setCurrentIndex((prev) => (prev + 1) % images.length);
    }, 5000);

    return () => clearInterval(interval);
  }, [images.length, isPaused]);

  if (images.length === 0) {
    // Fallback when no images
    return (
      <div className="relative min-h-[60vh] flex items-center justify-center bg-primary">
        <div className="relative z-10 container mx-auto px-4 max-w-4xl text-center text-white py-20">
          {category && (
            <span
              className="inline-block px-4 py-2 rounded-full text-sm font-semibold mb-4"
              style={{ backgroundColor: category.color }}
            >
              {category.name}
            </span>
          )}
          <h1 className="text-4xl md:text-5xl font-display font-bold mb-6">{title}</h1>
          <div className="flex items-center justify-center gap-6 text-sm text-gray-200">
            <span>{author.name}</span>
            <span>•</span>
            <span>{new Date(publishedAt).toLocaleDateString()}</span>
            <span>•</span>
            <span>{readTimeMinutes} min read</span>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div
      className="relative min-h-[60vh] flex items-center justify-center"
      onMouseEnter={() => setIsPaused(true)}
      onMouseLeave={() => setIsPaused(false)}
    >
      {/* Fade carousel images */}
      {images.map((image, index) => (
        <Image
          key={`${image}-${index}`}
          src={image}
          alt={`${title} - Image ${index + 1}`}
          fill
          className={cn(
            'object-cover transition-opacity duration-1000',
            index === currentIndex ? 'opacity-100' : 'opacity-0'
          )}
          priority={index === 0}
          sizes="100vw"
        />
      ))}

      {/* Dark overlay */}
      <div className="absolute inset-0 bg-black/50" />

      {/* Content overlay (title, category, author, date) */}
      <div className="relative z-10 container mx-auto px-4 max-w-4xl text-center text-white py-20">
        {category && (
          <span
            className="inline-block px-4 py-2 rounded-full text-sm font-semibold mb-4"
            style={{ backgroundColor: category.color }}
          >
            {category.name}
          </span>
        )}
        <h1 className="text-4xl md:text-5xl font-display font-bold mb-6">{title}</h1>
        <div className="flex items-center justify-center gap-6 text-sm text-gray-200">
          <span>{author.name}</span>
          <span>•</span>
          <span>{new Date(publishedAt).toLocaleDateString()}</span>
          <span>•</span>
          <span>{readTimeMinutes} min read</span>
        </div>
      </div>

      {/* "View X Photos" button (only if multiple images) */}
      {images.length > 1 && (
        <button
          onClick={onOpenLightbox}
          className="absolute bottom-6 right-6 z-20 flex items-center gap-2
                     bg-white/90 hover:bg-white text-gray-900 px-4 py-2
                     rounded-full shadow-lg transition-colors"
        >
          <Images className="w-5 h-5" />
          <span>View {images.length} Photos</span>
        </button>
      )}

      {/* Dot indicators (only if multiple images) */}
      {images.length > 1 && (
        <div className="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 flex gap-2">
          {images.map((_, index) => (
            <button
              key={index}
              onClick={() => setCurrentIndex(index)}
              className={cn(
                'w-2 h-2 rounded-full transition-all',
                index === currentIndex ? 'bg-white w-6' : 'bg-white/50 hover:bg-white/75'
              )}
              aria-label={`Go to image ${index + 1}`}
            />
          ))}
        </div>
      )}
    </div>
  );
}
