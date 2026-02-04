'use client';

import { useState, useEffect } from 'react';
import DOMPurify from 'dompurify';
import Image from 'next/image';
import { TextImageBlockData } from '@/types/cms';

const ALLOWED_TAGS = [
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
];

const ALLOWED_ATTR = ['href', 'src', 'alt', 'class', 'target', 'rel'];

export function TextImageBlock({
  content,
  image,
  image_position = 'right',
  title,
}: TextImageBlockData) {
  const [sanitizedContent, setSanitizedContent] = useState<string>('');
  const [isClient, setIsClient] = useState(false);

  const hasImage = Boolean(image);
  const imageOnLeft = image_position === 'left';

  useEffect(() => {
    setIsClient(true);
    if (content) {
      // Sanitize HTML on client-side only (DOMPurify needs browser DOM)
      setSanitizedContent(DOMPurify.sanitize(content, { ALLOWED_TAGS, ALLOWED_ATTR }));
    }
  }, [content]);

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

        {!isClient ? (
          <div className="prose prose-lg max-w-none animate-pulse bg-neutral-100 h-24 rounded" />
        ) : (
          <div
            className="prose prose-lg max-w-none"
            dangerouslySetInnerHTML={{ __html: sanitizedContent }}
          />
        )}

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
