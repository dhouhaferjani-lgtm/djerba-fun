'use client';

import { useState } from 'react';
import { BlogHeroCarousel } from './BlogHeroCarousel';
import { ImageLightbox } from '@/components/gallery/ImageLightbox';
import type { Media } from '@go-adventure/schemas';

interface BlogHeroSectionProps {
  images: string[];
  title: string;
  category?: { name: string; color: string } | null;
  author: { name: string };
  publishedAt: string;
  readTimeMinutes: number;
}

export function BlogHeroSection({
  images,
  title,
  category,
  author,
  publishedAt,
  readTimeMinutes,
}: BlogHeroSectionProps) {
  const [lightboxOpen, setLightboxOpen] = useState(false);

  // Convert string URLs to Media format for ImageLightbox
  const mediaImages: Media[] = images.map((url, index) => ({
    id: `hero-${index}`,
    url,
    alt: `${title} - Image ${index + 1}`,
    type: 'image' as const,
    category: 'hero' as const,
    order: index,
    thumbnailUrl: null,
  }));

  return (
    <>
      <BlogHeroCarousel
        images={images}
        title={title}
        category={category}
        author={author}
        publishedAt={publishedAt}
        readTimeMinutes={readTimeMinutes}
        onOpenLightbox={() => setLightboxOpen(true)}
      />

      {images.length > 0 && (
        <ImageLightbox
          isOpen={lightboxOpen}
          onClose={() => setLightboxOpen(false)}
          images={mediaImages}
        />
      )}
    </>
  );
}
