'use client';

import { useState, useEffect, useCallback } from 'react';
import Image from 'next/image';
import type { Media } from '@djerba-fun/schemas';
import { X, ChevronLeft, ChevronRight } from 'lucide-react';
import { Dialog } from '@djerba-fun/ui';
import { normalizeMediaUrl } from '@/lib/utils/image';

interface ImageLightboxProps {
  images: Media[];
  initialIndex?: number;
  isOpen: boolean;
  onClose: () => void;
}

export function ImageLightbox({ images, initialIndex = 0, isOpen, onClose }: ImageLightboxProps) {
  const [currentIndex, setCurrentIndex] = useState(initialIndex);

  // Reset to initial index when opened
  useEffect(() => {
    if (isOpen) {
      setCurrentIndex(initialIndex);
    }
  }, [isOpen, initialIndex]);

  const goToPrevious = useCallback(() => {
    setCurrentIndex((prev) => (prev === 0 ? images.length - 1 : prev - 1));
  }, [images.length]);

  const goToNext = useCallback(() => {
    setCurrentIndex((prev) => (prev === images.length - 1 ? 0 : prev + 1));
  }, [images.length]);

  // Keyboard navigation
  useEffect(() => {
    if (!isOpen) return;

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'ArrowLeft') {
        goToPrevious();
      } else if (e.key === 'ArrowRight') {
        goToNext();
      } else if (e.key === 'Escape') {
        onClose();
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, goToPrevious, goToNext, onClose]);

  if (images.length === 0) return null;

  const currentImage = images[currentIndex];

  return (
    <Dialog isOpen={isOpen} onClose={onClose}>
      <div className="fixed inset-0 z-50 bg-black">
        {/* Header */}
        <div className="absolute top-0 left-0 right-0 z-10 flex items-center justify-between p-4 bg-gradient-to-b from-black/80 to-transparent">
          <div className="text-white font-medium">
            {currentIndex + 1} / {images.length}
          </div>
          <button
            onClick={onClose}
            className="text-white hover:text-neutral-300 transition-colors p-2 rounded-full hover:bg-white/10"
            aria-label="Close lightbox"
          >
            <X className="h-6 w-6" />
          </button>
        </div>

        {/* Main Image */}
        <div className="absolute inset-0 flex items-center justify-center p-4 md:p-8">
          <div className="relative w-full h-full max-w-7xl max-h-full">
            <Image
              src={normalizeMediaUrl(currentImage.url)}
              alt={currentImage.alt || `Image ${currentIndex + 1}`}
              fill
              className="object-contain"
              sizes="100vw"
              quality={90}
              priority
            />
          </div>
        </div>

        {/* Navigation Arrows */}
        {images.length > 1 && (
          <>
            <button
              onClick={goToPrevious}
              className="absolute left-4 top-1/2 -translate-y-1/2 z-10 text-white hover:text-neutral-300 transition-colors p-3 rounded-full hover:bg-white/10 backdrop-blur-sm"
              aria-label="Previous image"
            >
              <ChevronLeft className="h-8 w-8" />
            </button>
            <button
              onClick={goToNext}
              className="absolute right-4 top-1/2 -translate-y-1/2 z-10 text-white hover:text-neutral-300 transition-colors p-3 rounded-full hover:bg-white/10 backdrop-blur-sm"
              aria-label="Next image"
            >
              <ChevronRight className="h-8 w-8" />
            </button>
          </>
        )}

        {/* Thumbnail Strip (bottom) */}
        {images.length > 1 && (
          <div className="absolute bottom-0 left-0 right-0 z-10 bg-gradient-to-t from-black/80 to-transparent p-4">
            <div className="flex gap-2 overflow-x-auto scrollbar-hide max-w-4xl mx-auto">
              {images.map((image, index) => (
                <button
                  key={image.id}
                  onClick={() => setCurrentIndex(index)}
                  className={`relative flex-shrink-0 w-20 h-14 rounded overflow-hidden transition-all ${
                    index === currentIndex
                      ? 'ring-2 ring-white scale-105'
                      : 'opacity-60 hover:opacity-100'
                  }`}
                >
                  <Image
                    src={normalizeMediaUrl(image.url)}
                    alt={image.alt || `Thumbnail ${index + 1}`}
                    fill
                    className="object-cover"
                    sizes="80px"
                  />
                </button>
              ))}
            </div>
          </div>
        )}
      </div>
    </Dialog>
  );
}
