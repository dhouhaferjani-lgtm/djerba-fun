'use client';

import DOMPurify from 'dompurify';
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

  // Sanitize HTML content to prevent XSS attacks
  const sanitizedContent = DOMPurify.sanitize(content, {
    ALLOWED_TAGS: [
      'p',
      'br',
      'strong',
      'em',
      'a',
      'ul',
      'ol',
      'li',
      'h1',
      'h2',
      'h3',
      'h4',
      'h5',
      'h6',
      'blockquote',
      'code',
      'pre',
      'img',
      'div',
      'span',
    ],
    ALLOWED_ATTR: ['href', 'src', 'alt', 'class', 'target', 'rel'],
  });

  return (
    <div className="text-image-block">
      {title && <h3 className="text-3xl font-bold mb-6">{title}</h3>}

      <div className={`grid gap-8 ${hasImage ? 'md:grid-cols-2 items-center' : 'grid-cols-1'}`}>
        {hasImage && imageOnLeft && (
          <div className="relative w-full h-[400px]">
            <Image
              src={image!}
              alt={title || 'Content image'}
              fill
              className="object-cover rounded-lg"
            />
          </div>
        )}

        <div
          className="prose prose-lg max-w-none"
          dangerouslySetInnerHTML={{ __html: sanitizedContent }}
        />

        {hasImage && !imageOnLeft && (
          <div className="relative w-full h-[400px]">
            <Image
              src={image!}
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
