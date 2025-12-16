'use client';

import Image from 'next/image';
import { TextImageBlockData } from '@/types/cms';

export function TextImageBlock({
  content,
  image,
  image_position = 'right',
  title,
}: TextImageBlockData) {
  const hasImage = Boolean(image);
  const imageOnLeft = image_position === 'left';

  return (
    <div className="text-image-block">
      {title && <h3 className="text-3xl font-bold mb-6">{title}</h3>}

      <div className={`grid gap-8 ${hasImage ? 'md:grid-cols-2 items-center' : 'grid-cols-1'}`}>
        {hasImage && imageOnLeft && (
          <div className="relative w-full h-[400px]">
            <Image
              src={image}
              alt={title || 'Content image'}
              fill
              className="object-cover rounded-lg"
            />
          </div>
        )}

        <div className="prose prose-lg max-w-none" dangerouslySetInnerHTML={{ __html: content }} />

        {hasImage && !imageOnLeft && (
          <div className="relative w-full h-[400px]">
            <Image
              src={image}
              alt={title || 'Content image'}
              fill
              className="object-cover rounded-lg"
            />
          </div>
        )}
      </div>
    </div>
  );
}
