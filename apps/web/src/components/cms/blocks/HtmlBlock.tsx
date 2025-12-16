'use client';

import { HtmlBlockData } from '@/types/cms';

export function HtmlBlock({ html }: HtmlBlockData) {
  if (!html) return null;

  return (
    <div
      className="html-block prose prose-lg max-w-none"
      dangerouslySetInnerHTML={{ __html: html }}
    />
  );
}
