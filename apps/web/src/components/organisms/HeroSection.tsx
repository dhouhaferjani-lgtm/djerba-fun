'use client';

import { useTranslations } from 'next-intl';
import Image from 'next/image';
import { useState, useEffect } from 'react';
import { HeroSearchForm } from '../molecules/HeroSearchForm';
import { shouldUnoptimizeImage } from '@/lib/utils/image';
import { travelTipsApi, type TravelTip } from '@/lib/api/client';

// Default hero image - used when no custom banner is set
const DEFAULT_HERO_IMAGE = '/images/hero/hero-banner.jpg';

// Timing constants
const TYPING_SPEED = 30; // ms per character
const PAUSE_AFTER_TYPING = 3000; // ms to pause after typing completes

// Running Traveler SVG Component - Dynamic running pose facing right
function RunningTraveler({ isRunning }: { isRunning: boolean }) {
  return (
    <svg
      width="28"
      height="24"
      viewBox="0 0 28 24"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      className="inline-block align-middle ml-1"
      style={{
        transform: 'translateY(-2px)',
      }}
    >
      {/* Motion lines behind (left side) when running */}
      {isRunning && (
        <g className="motion-lines" opacity="0.5">
          <line x1="1" y1="8" x2="4" y2="8" stroke="white" strokeWidth="1.5" strokeLinecap="round">
            <animate
              attributeName="opacity"
              values="0.2;0.7;0.2"
              dur="0.3s"
              repeatCount="indefinite"
            />
          </line>
          <line
            x1="0"
            y1="11"
            x2="3"
            y2="11"
            stroke="white"
            strokeWidth="1.5"
            strokeLinecap="round"
          >
            <animate
              attributeName="opacity"
              values="0.2;0.7;0.2"
              dur="0.3s"
              begin="0.1s"
              repeatCount="indefinite"
            />
          </line>
          <line
            x1="1"
            y1="14"
            x2="4"
            y2="14"
            stroke="white"
            strokeWidth="1.5"
            strokeLinecap="round"
          >
            <animate
              attributeName="opacity"
              values="0.2;0.7;0.2"
              dur="0.3s"
              begin="0.2s"
              repeatCount="indefinite"
            />
          </line>
        </g>
      )}

      {/* Backpack on back (left side) */}
      <rect
        x="7"
        y="6"
        width="4"
        height="6"
        rx="1"
        fill="#8BC34A"
        stroke="white"
        strokeWidth="0.5"
        style={{
          transform: isRunning ? 'rotate(-8deg)' : 'rotate(0deg)',
          transformOrigin: '9px 9px',
        }}
      />
      {/* Backpack strap */}
      <line
        x1="11"
        y1="7"
        x2="13"
        y2="5"
        stroke="#8BC34A"
        strokeWidth="1.5"
        strokeLinecap="round"
      />

      {/* Body - leaning forward when running */}
      <line
        x1={isRunning ? '12' : '13'}
        y1="5"
        x2={isRunning ? '14' : '13'}
        y2="12"
        stroke="white"
        strokeWidth="2.5"
        strokeLinecap="round"
        style={{
          transform: isRunning ? 'rotate(15deg)' : 'rotate(0deg)',
          transformOrigin: '13px 8px',
        }}
      />

      {/* Head - slightly forward when running */}
      <circle cx={isRunning ? '16' : '14'} cy={isRunning ? '3' : '3'} r="2.5" fill="white" />

      {/* Back arm (behind body) */}
      <line
        x1="13"
        y1="7"
        x2={isRunning ? '9' : '11'}
        y2={isRunning ? '10' : '11'}
        stroke="white"
        strokeWidth="2"
        strokeLinecap="round"
        style={{
          transformOrigin: '13px 7px',
          animation: isRunning ? 'backArmSwing 0.3s ease-in-out infinite alternate' : 'none',
        }}
      />

      {/* Front arm (pumping forward) */}
      <line
        x1="14"
        y1="7"
        x2={isRunning ? '20' : '17'}
        y2={isRunning ? '5' : '10'}
        stroke="white"
        strokeWidth="2"
        strokeLinecap="round"
        style={{
          transformOrigin: '14px 7px',
          animation: isRunning
            ? 'frontArmSwing 0.3s ease-in-out infinite alternate-reverse'
            : 'none',
        }}
      />

      {/* Back leg (pushing off) */}
      <line
        x1="14"
        y1="12"
        x2={isRunning ? '10' : '12'}
        y2={isRunning ? '20' : '21'}
        stroke="white"
        strokeWidth="2.5"
        strokeLinecap="round"
        style={{
          transformOrigin: '14px 12px',
          animation: isRunning ? 'backLegSwing 0.3s ease-in-out infinite alternate' : 'none',
        }}
      />

      {/* Front leg (striding forward) */}
      <line
        x1="14"
        y1="12"
        x2={isRunning ? '20' : '16'}
        y2={isRunning ? '19' : '21'}
        stroke="white"
        strokeWidth="2.5"
        strokeLinecap="round"
        style={{
          transformOrigin: '14px 12px',
          animation: isRunning
            ? 'frontLegSwing 0.3s ease-in-out infinite alternate-reverse'
            : 'none',
        }}
      />

      {/* Foot details when running */}
      {isRunning && (
        <>
          <circle cx="10" cy="20" r="1" fill="white">
            <animate attributeName="cy" values="20;19;20" dur="0.3s" repeatCount="indefinite" />
          </circle>
          <circle cx="20" cy="19" r="1" fill="white">
            <animate attributeName="cy" values="19;20;19" dur="0.3s" repeatCount="indefinite" />
          </circle>
        </>
      )}

      <style>{`
        @keyframes backArmSwing {
          0% { transform: rotate(-30deg); }
          100% { transform: rotate(10deg); }
        }
        @keyframes frontArmSwing {
          0% { transform: rotate(30deg); }
          100% { transform: rotate(-10deg); }
        }
        @keyframes backLegSwing {
          0% { transform: rotate(-25deg); }
          100% { transform: rotate(15deg); }
        }
        @keyframes frontLegSwing {
          0% { transform: rotate(25deg); }
          100% { transform: rotate(-15deg); }
        }
      `}</style>
    </svg>
  );
}

interface HeroData {
  title: string | null;
  subtitle: string | null;
}

// Default hero video fallback - used when no CMS video is uploaded
const DEFAULT_HERO_VIDEO = '/videos/hero-banner.mp4';

interface HeroSectionProps {
  locale: string;
  heroBannerUrl?: string | null;
  heroBannerIsVideo?: boolean;
  heroData?: HeroData;
}

export function HeroSection({
  locale,
  heroBannerUrl,
  heroBannerIsVideo,
  heroData,
}: HeroSectionProps) {
  const t = useTranslations('home');

  // Show video when: (a) CMS uploaded a video, or (b) no CMS banner at all (use local fallback)
  // If CMS has an IMAGE banner, NO video renders — preserving the admin's choice
  const showVideo = heroBannerIsVideo || !heroBannerUrl;
  const videoSrc = heroBannerIsVideo && heroBannerUrl ? heroBannerUrl : DEFAULT_HERO_VIDEO;
  const backgroundImage = heroBannerUrl && !heroBannerIsVideo ? heroBannerUrl : DEFAULT_HERO_IMAGE;

  // Video readiness state — poster shows until video is buffered
  const [videoReady, setVideoReady] = useState(false);

  // Reset video readiness if we switch away from video mode
  useEffect(() => {
    if (!showVideo) setVideoReady(false);
  }, [showVideo]);

  // Use CMS values with translation fallbacks
  // Fallback combines the two translation keys for backwards compatibility
  const fullTitle = heroData?.title || `${t('hero_title_line1')} ${t('hero_title_line2')}`;
  const subtitle = heroData?.subtitle || t('hero_subtitle');

  // Split title: first word is green, rest is white
  const titleWords = fullTitle.split(' ');
  const firstWord = titleWords[0] || '';
  const restOfTitle = titleWords.slice(1).join(' ');

  // Travel tips state
  const [tips, setTips] = useState<TravelTip[]>([]);
  const [currentTipIndex, setCurrentTipIndex] = useState(0);
  const [displayedText, setDisplayedText] = useState('');
  const [isTyping, setIsTyping] = useState(true);

  // Fallback tip from translations
  const fallbackTip = t('hero_travel_tip_content');

  // Get current tip content
  const currentTip = tips.length > 0 ? tips[currentTipIndex]?.content : fallbackTip;

  // Find the longest tip to reserve max container space (prevents layout shift between rotations)
  const longestTip =
    tips.length > 0
      ? tips.reduce((a, b) => (a.content.length > b.content.length ? a : b)).content
      : fallbackTip;

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

  return (
    <section className="relative h-[85vh] flex items-center justify-center overflow-hidden">
      {/* Background — Poster image + Video (desktop only) */}
      <div className="absolute inset-0 z-0 overflow-hidden">
        {/* Poster image — shows immediately, fades out once video is ready on desktop */}
        <Image
          src={backgroundImage}
          alt="Hero Banner"
          fill
          className={`object-cover transition-opacity duration-1000 ${showVideo && videoReady ? 'md:opacity-0' : 'opacity-100'}`}
          priority
          unoptimized={shouldUnoptimizeImage(backgroundImage)}
        />

        {/* Video background — only rendered when we have a video to show (CMS video or no CMS banner) */}
        {showVideo && (
          <video
            autoPlay
            muted
            loop
            playsInline
            preload="auto"
            onCanPlay={() => setVideoReady(true)}
            className={`absolute inset-0 w-full h-full object-cover bg-transparent transition-opacity duration-1000 scale-150 md:scale-[1.08] ${videoReady ? 'opacity-100' : 'opacity-0'}`}
          >
            <source src={videoSrc} type="video/mp4" />
          </video>
        )}

        {/* Dark Green Gradient Overlay */}
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
          {/* Hero Headline - First word in green, rest in white */}
          {/* CMS values with translation fallbacks */}
          <h1
            className="font-normal text-white mb-3 md:mb-4 leading-[1.1] tracking-normal"
            style={{
              fontFamily: 'var(--font-serif)',
              fontSize: 'clamp(32px, 6vw, 150px)',
              textShadow: '0 2px 4px rgba(0, 0, 0, 0.3)',
            }}
          >
            <span className="text-[#8BC34A]">{firstWord}</span>{' '}
            <span className="text-white">{restOfTitle}</span>
          </h1>
          <p className="text-base sm:text-lg md:text-xl lg:text-2xl font-light text-white/90 mb-8 max-w-2xl mx-auto leading-[0.8]">
            {subtitle}
          </p>

          {/* Search Form - Floating with Glassmorphism */}
          <div className="max-w-5xl mx-auto mb-6">
            <HeroSearchForm locale={locale} />
          </div>

          {/* Travel Tip Banner - Transparent with white border, typewriter effect with running traveler */}
          <div className="w-full max-w-5xl mx-auto bg-white/10 backdrop-blur-sm border border-white px-8 py-3 rounded-lg overflow-hidden">
            <p className="text-white text-sm text-center">
              <span className="inline-flex items-center justify-center">
                <span className="relative">
                  <span className="invisible">{longestTip}</span>
                  <span className="absolute inset-0 text-center">
                    {displayedText}
                    <RunningTraveler isRunning={isTyping} />
                  </span>
                </span>
              </span>
            </p>
          </div>
        </div>
      </div>
    </section>
  );
}
