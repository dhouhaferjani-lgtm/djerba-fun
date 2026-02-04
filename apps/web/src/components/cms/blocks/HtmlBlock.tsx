'use client';

import { useState, useEffect } from 'react';
import DOMPurify from 'dompurify';
import { HtmlBlockData } from '@/types/cms';

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

export function HtmlBlock({ html }: HtmlBlockData) {
  const [sanitizedHtml, setSanitizedHtml] = useState<string>('');
  const [isClient, setIsClient] = useState(false);

  useEffect(() => {
    setIsClient(true);
    if (html) {
      // Sanitize HTML on client-side only (DOMPurify needs browser DOM)
      setSanitizedHtml(DOMPurify.sanitize(html, { ALLOWED_TAGS, ALLOWED_ATTR }));
    }
  }, [html]);

  if (!html) return null;

  // Show placeholder during SSR, render sanitized content on client
  if (!isClient) {
    return (
      <div className="html-block prose prose-lg max-w-none animate-pulse bg-neutral-100 h-24 rounded" />
    );
  }

  return (
    <div
      className="html-block prose prose-lg max-w-none"
      dangerouslySetInnerHTML={{ __html: sanitizedHtml }}
    />
  );
}
