'use client';

import Image from 'next/image';
import { ImageBlockData } from '@/types/cms';

export function ImageBlock({ image, title, caption, copyright }: ImageBlockData) {
  if (!image) return null;

  return (
    <figure className="image-block">
      {title && <h3 className="text-2xl font-bold mb-4">{title}</h3>}

      <div className="relative w-full h-[500px]">
        <Image
          src={image}
          alt={title || caption || 'Image'}
          fill
          className="object-cover rounded-lg"
        />
      </div>

      {(caption || copyright) && (
        <figcaption className="text-sm text-gray-600 mt-2 text-center space-y-1">
          {caption && <p>{caption}</p>}
          {copyright && <p className="text-xs">© {copyright}</p>}
        </figcaption>
      )}
    </figure>
  );
}
