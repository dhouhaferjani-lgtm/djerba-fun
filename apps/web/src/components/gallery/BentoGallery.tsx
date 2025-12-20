'use client';

import { useState } from 'react';
import Image from 'next/image';
import type { Media } from '@go-adventure/schemas';
import { Eye } from 'lucide-react';

interface BentoGalleryProps {
  images: Media[];
  onViewAll: (index?: number) => void;
}

export function BentoGallery({ images, onViewAll }: BentoGalleryProps) {
  // Filter to show first 5 images for bento layout
  const displayImages = images.slice(0, 5);

  if (displayImages.length === 0) {
    return (
      <div className="w-full aspect-[16/9] bg-neutral-100 rounded-lg flex items-center justify-center">
        <p className="text-neutral-500">No images available</p>
      </div>
    );
  }

  return (
    <div className="relative w-full">
      {/* Desktop: Bento grid layout */}
      <div className="hidden md:grid md:grid-cols-4 md:grid-rows-2 gap-2 h-[500px] rounded-xl overflow-hidden">
        {/* Main large image (2x2 grid) */}
        {displayImages[0] && (
          <button
            onClick={() => onViewAll(0)}
            className="relative col-span-2 row-span-2 overflow-hidden group"
          >
            <Image
              src={displayImages[0].url}
              alt={displayImages[0].alt || 'Listing image 1'}
              fill
              className="object-cover transition-transform duration-300 group-hover:scale-105"
              sizes="(min-width: 768px) 50vw, 100vw"
              priority
            />
            <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors" />
          </button>
        )}

        {/* Secondary images (1x1 grid each) */}
        {displayImages.slice(1, 5).map((image, index) => (
          <button
            key={image.id}
            onClick={() => onViewAll(index + 1)}
            className="relative overflow-hidden group"
          >
            <Image
              src={image.url}
              alt={image.alt || `Listing image ${index + 2}`}
              fill
              className="object-cover transition-transform duration-300 group-hover:scale-105"
              sizes="(min-width: 768px) 25vw, 50vw"
            />
            <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors" />

            {/* "View all photos" overlay on last image if there are more */}
            {index === 3 && images.length > 5 && (
              <div className="absolute inset-0 bg-black/60 flex items-center justify-center">
                <div className="text-white text-center">
                  <Eye className="h-8 w-8 mx-auto mb-2" />
                  <p className="font-semibold">View all {images.length} photos</p>
                </div>
              </div>
            )}
          </button>
        ))}
      </div>

      {/* Mobile: Single column stack */}
      <div className="md:hidden space-y-2">
        {displayImages.slice(0, 3).map((image, index) => (
          <button
            key={image.id}
            onClick={() => onViewAll(index)}
            className="relative w-full aspect-[4/3] overflow-hidden rounded-lg group"
          >
            <Image
              src={image.url}
              alt={image.alt || `Listing image ${index + 1}`}
              fill
              className="object-cover transition-transform duration-300 group-hover:scale-105"
              sizes="100vw"
              priority={index === 0}
            />
            <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors" />

            {/* "View all photos" overlay on last image */}
            {index === 2 && images.length > 3 && (
              <div className="absolute inset-0 bg-black/60 flex items-center justify-center">
                <div className="text-white text-center">
                  <Eye className="h-8 w-8 mx-auto mb-2" />
                  <p className="font-semibold">View all {images.length} photos</p>
                </div>
              </div>
            )}
          </button>
        ))}
      </div>

      {/* Floating "View all photos" button (desktop only, bottom-right) */}
      {images.length > 5 && (
        <button
          onClick={() => onViewAll(0)}
          className="hidden md:flex absolute bottom-4 right-4 items-center gap-2 px-4 py-2 bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow border border-neutral-200"
        >
          <Eye className="h-4 w-4" />
          <span className="text-sm font-medium">View all {images.length} photos</span>
        </button>
      )}
    </div>
  );
}
