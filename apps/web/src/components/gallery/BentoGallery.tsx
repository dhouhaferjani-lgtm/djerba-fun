'use client';

import { useState } from 'react';
import Image from 'next/image';
import type { Media } from '@djerba-fun/schemas';
import { Eye } from 'lucide-react';
import { normalizeMediaUrl } from '@/lib/utils/image';

interface BentoGalleryProps {
  images: Media[];
  onViewAll: (index?: number) => void;
}

export function BentoGallery({ images, onViewAll }: BentoGalleryProps) {
  const count = Math.min(images.length, 5);

  if (count === 0) {
    return (
      <div className="w-full aspect-[16/9] bg-neutral-100 rounded-lg flex items-center justify-center">
        <p className="text-neutral-500">No images available</p>
      </div>
    );
  }

  // Render based on image count
  switch (count) {
    case 1:
      return <SingleImageLayout images={images} onViewAll={onViewAll} />;
    case 2:
      return <TwoImageLayout images={images} onViewAll={onViewAll} />;
    case 3:
      return <ThreeImageLayout images={images} onViewAll={onViewAll} />;
    case 4:
      return <FourImageLayout images={images} onViewAll={onViewAll} />;
    case 5:
    default:
      return <FiveImageLayout images={images} onViewAll={onViewAll} />;
  }
}

// 1 Image: Full width
function SingleImageLayout({ images, onViewAll }: BentoGalleryProps) {
  const image = images[0];

  return (
    <div className="relative w-full">
      {/* Desktop */}
      <button
        onClick={() => onViewAll(0)}
        className="hidden md:block relative w-full aspect-[16/9] overflow-hidden rounded-xl group"
      >
        <Image
          src={normalizeMediaUrl(image.url)}
          alt={image.alt || 'Listing image'}
          fill
          className="object-cover transition-transform duration-300 group-hover:scale-105"
          sizes="100vw"
          priority
        />
        <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors" />
      </button>

      {/* Mobile */}
      <button
        onClick={() => onViewAll(0)}
        className="md:hidden relative w-full aspect-[4/3] overflow-hidden rounded-lg group"
      >
        <Image
          src={normalizeMediaUrl(image.url)}
          alt={image.alt || 'Listing image'}
          fill
          className="object-cover transition-transform duration-300 group-hover:scale-105"
          sizes="100vw"
          priority
        />
      </button>
    </div>
  );
}

// 2 Images: Two equal columns
function TwoImageLayout({ images, onViewAll }: BentoGalleryProps) {
  const displayImages = images.slice(0, 2);

  return (
    <div className="relative w-full">
      {/* Desktop: 2 columns */}
      <div className="hidden md:grid md:grid-cols-2 gap-2 h-[400px] rounded-xl overflow-hidden">
        {displayImages.map((image, index) => (
          <button
            key={image.id}
            onClick={() => onViewAll(index)}
            className="relative overflow-hidden group"
          >
            <Image
              src={normalizeMediaUrl(image.url)}
              alt={image.alt || `Listing image ${index + 1}`}
              fill
              className="object-cover transition-transform duration-300 group-hover:scale-105"
              sizes="50vw"
              priority={index === 0}
            />
            <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors" />
          </button>
        ))}
      </div>

      {/* Mobile: Stack */}
      <div className="md:hidden space-y-2">
        {displayImages.map((image, index) => (
          <button
            key={image.id}
            onClick={() => onViewAll(index)}
            className="relative w-full aspect-[4/3] overflow-hidden rounded-lg group"
          >
            <Image
              src={normalizeMediaUrl(image.url)}
              alt={image.alt || `Listing image ${index + 1}`}
              fill
              className="object-cover transition-transform duration-300 group-hover:scale-105"
              sizes="100vw"
              priority={index === 0}
            />
          </button>
        ))}
      </div>
    </div>
  );
}

// 3 Images: 1 large + 2 small stacked
function ThreeImageLayout({ images, onViewAll }: BentoGalleryProps) {
  const displayImages = images.slice(0, 3);

  return (
    <div className="relative w-full">
      {/* Desktop: 1 large left, 2 stacked right */}
      <div className="hidden md:grid md:grid-cols-2 md:grid-rows-2 gap-2 h-[500px] rounded-xl overflow-hidden">
        {/* Large image - left, spans 2 rows */}
        <button onClick={() => onViewAll(0)} className="relative row-span-2 overflow-hidden group">
          <Image
            src={normalizeMediaUrl(displayImages[0].url)}
            alt={displayImages[0].alt || 'Listing image 1'}
            fill
            className="object-cover transition-transform duration-300 group-hover:scale-105"
            sizes="50vw"
            priority
          />
          <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors" />
        </button>

        {/* Top right */}
        <button onClick={() => onViewAll(1)} className="relative overflow-hidden group">
          <Image
            src={normalizeMediaUrl(displayImages[1].url)}
            alt={displayImages[1].alt || 'Listing image 2'}
            fill
            className="object-cover transition-transform duration-300 group-hover:scale-105"
            sizes="25vw"
          />
          <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors" />
        </button>

        {/* Bottom right */}
        <button onClick={() => onViewAll(2)} className="relative overflow-hidden group">
          <Image
            src={normalizeMediaUrl(displayImages[2].url)}
            alt={displayImages[2].alt || 'Listing image 3'}
            fill
            className="object-cover transition-transform duration-300 group-hover:scale-105"
            sizes="25vw"
          />
          <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors" />
        </button>
      </div>

      {/* Mobile: Stack first 3 */}
      <div className="md:hidden space-y-2">
        {displayImages.map((image, index) => (
          <button
            key={image.id}
            onClick={() => onViewAll(index)}
            className="relative w-full aspect-[4/3] overflow-hidden rounded-lg group"
          >
            <Image
              src={normalizeMediaUrl(image.url)}
              alt={image.alt || `Listing image ${index + 1}`}
              fill
              className="object-cover transition-transform duration-300 group-hover:scale-105"
              sizes="100vw"
              priority={index === 0}
            />
          </button>
        ))}
      </div>
    </div>
  );
}

// 4 Images: 2x2 grid
function FourImageLayout({ images, onViewAll }: BentoGalleryProps) {
  const displayImages = images.slice(0, 4);

  return (
    <div className="relative w-full">
      {/* Desktop: 2x2 grid */}
      <div className="hidden md:grid md:grid-cols-2 md:grid-rows-2 gap-2 h-[500px] rounded-xl overflow-hidden">
        {displayImages.map((image, index) => (
          <button
            key={image.id}
            onClick={() => onViewAll(index)}
            className="relative overflow-hidden group"
          >
            <Image
              src={normalizeMediaUrl(image.url)}
              alt={image.alt || `Listing image ${index + 1}`}
              fill
              className="object-cover transition-transform duration-300 group-hover:scale-105"
              sizes="25vw"
              priority={index === 0}
            />
            <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors" />
          </button>
        ))}
      </div>

      {/* Mobile: Stack first 3 */}
      <div className="md:hidden space-y-2">
        {displayImages.slice(0, 3).map((image, index) => (
          <button
            key={image.id}
            onClick={() => onViewAll(index)}
            className="relative w-full aspect-[4/3] overflow-hidden rounded-lg group"
          >
            <Image
              src={normalizeMediaUrl(image.url)}
              alt={image.alt || `Listing image ${index + 1}`}
              fill
              className="object-cover transition-transform duration-300 group-hover:scale-105"
              sizes="100vw"
              priority={index === 0}
            />
            {/* "View all" overlay on last visible mobile image */}
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
    </div>
  );
}

// 5 Images: Bento 1-4 layout (1 large left + 4 small right)
function FiveImageLayout({ images, onViewAll }: BentoGalleryProps) {
  const displayImages = images.slice(0, 5);

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
              src={normalizeMediaUrl(displayImages[0].url)}
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
              src={normalizeMediaUrl(image.url)}
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
              src={normalizeMediaUrl(image.url)}
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
