'use client';

import { useEffect, useRef, useCallback } from 'react';
import Script from 'next/script';
import { useParams } from 'next/navigation';

interface TurnstileWidgetProps {
  onVerify: (token: string) => void;
  onError?: (error: string) => void;
  onExpire?: () => void;
  className?: string;
}

declare global {
  interface Window {
    turnstile?: {
      render: (container: string | HTMLElement, options: TurnstileOptions) => string;
      reset: (widgetId: string) => void;
      remove: (widgetId: string) => void;
    };
  }
}

interface TurnstileOptions {
  sitekey: string;
  callback: (token: string) => void;
  'error-callback'?: (error: string) => void;
  'expired-callback'?: () => void;
  theme?: 'light' | 'dark' | 'auto';
  language?: string;
  size?: 'normal' | 'compact';
}

export function TurnstileWidget({ onVerify, onError, onExpire, className }: TurnstileWidgetProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  const widgetIdRef = useRef<string | null>(null);
  const params = useParams();
  const locale = (params.locale as string) || 'en';

  const siteKey = process.env.NEXT_PUBLIC_TURNSTILE_SITE_KEY;
  const enabled = process.env.NEXT_PUBLIC_TURNSTILE_ENABLED === 'true';

  const renderWidget = useCallback(() => {
    if (!window.turnstile || !containerRef.current || !siteKey || !enabled) {
      return;
    }

    // Remove existing widget if any
    if (widgetIdRef.current) {
      try {
        window.turnstile.remove(widgetIdRef.current);
      } catch {
        // Widget may already be removed
      }
    }

    widgetIdRef.current = window.turnstile.render(containerRef.current, {
      sitekey: siteKey,
      callback: onVerify,
      'error-callback': onError,
      'expired-callback': onExpire,
      theme: 'light',
      language: locale === 'fr' ? 'fr' : 'en',
    });
  }, [siteKey, enabled, locale, onVerify, onError, onExpire]);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      if (widgetIdRef.current && window.turnstile) {
        try {
          window.turnstile.remove(widgetIdRef.current);
        } catch {
          // Widget may already be removed
        }
      }
    };
  }, []);

  // If disabled, render nothing
  if (!enabled || !siteKey) {
    return null;
  }

  return (
    <>
      <Script
        src="https://challenges.cloudflare.com/turnstile/v0/api.js"
        strategy="lazyOnload"
        onLoad={renderWidget}
      />
      <div ref={containerRef} className={className} />
    </>
  );
}

/**
 * Hook for easy form integration with Turnstile
 */
export function useTurnstile() {
  const tokenRef = useRef<string>('');

  const handleVerify = useCallback((token: string) => {
    tokenRef.current = token;
  }, []);

  const getToken = useCallback(() => tokenRef.current, []);

  const resetToken = useCallback(() => {
    tokenRef.current = '';
  }, []);

  return { handleVerify, getToken, resetToken };
}
