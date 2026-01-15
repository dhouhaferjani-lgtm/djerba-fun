'use client';

import DOMPurify from 'dompurify';

interface SanitizedHtmlProps {
  html: string;
  className?: string;
}

/**
 * Renders sanitized HTML content safely.
 * Use this component whenever rendering user-generated or CMS HTML content.
 */
export function SanitizedHtml({ html, className = '' }: SanitizedHtmlProps) {
  if (!html) return null;

  // Sanitize HTML to prevent XSS attacks
  const sanitizedHtml = DOMPurify.sanitize(html, {
    ALLOWED_TAGS: [
      'p',
      'br',
      'strong',
      'b',
      'em',
      'i',
      'u',
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
      'figure',
      'figcaption',
      'div',
      'span',
      'table',
      'thead',
      'tbody',
      'tr',
      'th',
      'td',
      'hr',
    ],
    ALLOWED_ATTR: ['href', 'src', 'alt', 'class', 'target', 'rel', 'title', 'width', 'height'],
    ADD_ATTR: ['target'], // Allow target attribute for links
  });

  return <div className={className} dangerouslySetInnerHTML={{ __html: sanitizedHtml }} />;
}
