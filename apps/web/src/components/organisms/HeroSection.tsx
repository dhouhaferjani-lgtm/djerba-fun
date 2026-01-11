'use client';

import { useTranslations } from 'next-intl';
import Image from 'next/image';
import { useState, useEffect, useCallback } from 'react';
import { HeroSearchForm } from '../molecules/HeroSearchForm';
import { shouldUnoptimizeImage } from '@/lib/utils/image';
import { travelTipsApi, type TravelTip } from '@/lib/api/client';

// Default hero image (Unsplash) - used when no custom banner is set
const DEFAULT_HERO_IMAGE = 'https://images.unsplash.com/photo-1509099836639-18ba1795216d?w=1920';

// Timing constants
const TYPING_SPEED = 30; // ms per character
const PAUSE_AFTER_TYPING = 3000; // ms to pause after typing completes
const CURSOR_BLINK_SPEED = 530; // ms for cursor blink

interface HeroSectionProps {
  locale: string;
  heroBannerUrl?: string | null;
}

export function HeroSection({ locale, heroBannerUrl }: HeroSectionProps) {
  const t = useTranslations('home');
  const backgroundImage = heroBannerUrl || DEFAULT_HERO_IMAGE;

  // Travel tips state
  const [tips, setTips] = useState<TravelTip[]>([]);
  const [currentTipIndex, setCurrentTipIndex] = useState(0);
  const [displayedText, setDisplayedText] = useState('');
  const [isTyping, setIsTyping] = useState(true);
  const [showCursor, setShowCursor] = useState(true);

  // Fallback tip from translations
  const fallbackTip = t('hero_travel_tip_content');

  // Get current tip content
  const currentTip = tips.length > 0 ? tips[currentTipIndex]?.content : fallbackTip;

  // Fetch tips on mount
  useEffect(() => {
    const fetchTips = async () => {
      try {
        const response = await travelTipsApi.getAll(locale);
        if (response.data && response.data.length > 0) {
          setTips(response.data);
        }
      } catch (error) {
        // Silently fail - will use fallback tip
        console.warn('Failed to fetch travel tips:', error);
      }
    };

    fetchTips();
  }, [locale]);

  // Typewriter effect
  useEffect(() => {
    if (!currentTip) return;

    setDisplayedText('');
    setIsTyping(true);
    let charIndex = 0;

    const typingInterval = setInterval(() => {
      if (charIndex < currentTip.length) {
        setDisplayedText(currentTip.slice(0, charIndex + 1));
        charIndex++;
      } else {
        clearInterval(typingInterval);
        setIsTyping(false);
      }
    }, TYPING_SPEED);

    return () => clearInterval(typingInterval);
  }, [currentTip, currentTipIndex]);

  // Move to next tip after pause
  useEffect(() => {
    if (isTyping || tips.length <= 1) return;

    const timeout = setTimeout(() => {
      setCurrentTipIndex((prev) => (prev + 1) % tips.length);
    }, PAUSE_AFTER_TYPING);

    return () => clearTimeout(timeout);
  }, [isTyping, tips.length]);

  // Blinking cursor effect
  useEffect(() => {
    const cursorInterval = setInterval(() => {
      setShowCursor((prev) => !prev);
    }, CURSOR_BLINK_SPEED);

    return () => clearInterval(cursorInterval);
  }, []);

  return (
    <section className="relative h-[85vh] flex items-center justify-center overflow-hidden">
      {/* Background Image */}
      <div className="absolute inset-0 z-0">
        <Image
          src={backgroundImage}
          alt="Hero Banner"
          fill
          className="object-cover"
          priority
          unoptimized={shouldUnoptimizeImage(backgroundImage)}
        />
        {/* Dark Green Gradient Overlay - keeps photo visible but ensures text readability */}
        <div
          className="absolute inset-0"
          style={{
            background:
              'linear-gradient(to bottom, rgba(13, 100, 46, 0.7) 0%, rgba(13, 100, 46, 0.5) 50%, rgba(13, 100, 46, 0.6) 100%)',
          }}
        />
      </div>

      {/* Content */}
      <div className="container mx-auto px-4 relative z-10 text-center">
        <div className="max-w-7xl mx-auto">
          {/* Hero Headline - Elegant serif style with text shadow */}
          <h1
            className="font-normal text-white mb-3 md:mb-4 leading-[1.1] tracking-normal"
            style={{
              fontFamily: 'var(--font-serif)',
              fontSize: 'clamp(32px, 6vw, 150px)',
              textShadow: '0 2px 4px rgba(0, 0, 0, 0.3)',
            }}
          >
            <span className="block whitespace-nowrap">{t('hero_title_line1')}</span>
            <span className="block text-[#8BC34A] -mt-[0.15em]">{t('hero_title_line2')}</span>
          </h1>
          <p className="text-base sm:text-lg md:text-xl lg:text-2xl font-light text-white/90 mb-8 max-w-2xl mx-auto leading-[0.8]">
            {t('hero_subtitle')}
          </p>

          {/* Search Form - Floating with Glassmorphism */}
          <div className="max-w-5xl mx-auto mb-6">
            <HeroSearchForm locale={locale} />
          </div>

          {/* Travel Tip Banner - Transparent with white border, typewriter effect */}
          <div className="w-full max-w-5xl mx-auto bg-white/10 backdrop-blur-sm border border-white px-8 py-2 rounded-lg">
            <p className="text-white text-sm">
              <span className="text-[#8BC34A] font-semibold">Travel Tip:</span>{' '}
              <span className="inline">
                {displayedText}
                <span
                  className={`inline-block w-[2px] h-[1em] bg-white ml-[1px] align-middle ${
                    showCursor ? 'opacity-100' : 'opacity-0'
                  }`}
                />
              </span>
            </p>
          </div>
        </div>
      </div>
    </section>
  );
}
