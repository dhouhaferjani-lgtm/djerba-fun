'use client';

import DOMPurify from 'dompurify';

interface SanitizedHtmlProps {
  html: string;
  className?: string;
}

/**
 * Transforms relative /storage/ URLs to absolute URLs pointing to the API domain.
 * This is needed because images uploaded via TinyEditor are stored on the API server.
 */
function transformStorageUrls(html: string): string {
  const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'https://app.go-adventure.net/api/v1';
  // Extract base URL (remove /api/v1 suffix)
  const baseUrl = apiUrl.replace(/\/api\/v1\/?$/, '');

  // Replace relative /storage/ URLs with absolute URLs
  return html.replace(/src="\/storage\//g, `src="${baseUrl}/storage/`);
}

/**
 * Renders sanitized HTML content safely.
 * Use this component whenever rendering user-generated or CMS HTML content.
 */
export function SanitizedHtml({ html, className = '' }: SanitizedHtmlProps) {
  if (!html) return null;

  // Transform relative storage URLs to absolute URLs pointing to API
  const transformedHtml = transformStorageUrls(html);

  // Sanitize HTML to prevent XSS attacks
  const sanitizedHtml = DOMPurify.sanitize(transformedHtml, {
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
