'use client';

import { QuoteBlockData } from '@/types/cms';

export function QuoteBlock({ quote, author, author_title }: QuoteBlockData) {
  return (
    <blockquote className="quote-block border-l-4 border-primary pl-6 py-4 my-8">
      <p className="text-2xl font-serif italic text-gray-800 mb-4">&ldquo;{quote}&rdquo;</p>

      {(author || author_title) && (
        <footer className="text-gray-600">
          {author && <cite className="font-semibold not-italic">{author}</cite>}
          {author_title && <span className="text-sm block mt-1">{author_title}</span>}
        </footer>
      )}
    </blockquote>
  );
}
