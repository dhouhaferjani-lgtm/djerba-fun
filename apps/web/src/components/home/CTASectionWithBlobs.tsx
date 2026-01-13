'use client';

import { useTranslations } from 'next-intl';
import Link from 'next/link';
import { Button } from '@go-adventure/ui';
import { MessageCircle } from 'lucide-react';

// CSS for shining light sweep animation and click pulse effect
const shineAnimationStyles = `
  @keyframes shine {
    0% {
      left: -100%;
    }
    50%, 100% {
      left: 100%;
    }
  }

  @keyframes pulse-click {
    0%, 100% {
      transform: scale(1);
      box-shadow: 0 4px 15px rgba(13, 100, 46, 0.4);
    }
    50% {
      transform: scale(0.97);
      box-shadow: 0 2px 8px rgba(13, 100, 46, 0.6);
    }
  }

  .shine-button-cta {
    position: relative;
    overflow: hidden;
    animation: pulse-click 2s ease-in-out infinite;
  }

  .shine-button-cta::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 50%;
    height: 100%;
    background: linear-gradient(
      90deg,
      transparent,
      rgba(255, 255, 255, 0.6),
      transparent
    );
    animation: shine 2.5s infinite;
    pointer-events: none;
  }
`;

interface CTASectionWithBlobsProps {
  locale: string;
}

export function CTASectionWithBlobs({ locale }: CTASectionWithBlobsProps) {
  const t = useTranslations('home');

  return (
    <section className="relative py-24 bg-primary overflow-hidden">
      <style dangerouslySetInnerHTML={{ __html: shineAnimationStyles }} />
      {/* Blurred Blob Decorations */}
      <div className="absolute top-10 left-10 w-96 h-96 bg-secondary rounded-full filter blur-3xl opacity-20" />
      <div className="absolute bottom-10 right-10 w-80 h-80 bg-secondary rounded-full filter blur-3xl opacity-20" />
      <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-secondary rounded-full filter blur-3xl opacity-10" />

      {/* Content */}
      <div className="container mx-auto px-4 relative z-10">
        <div className="max-w-3xl mx-auto text-center">
          <h2 className="text-4xl md:text-5xl font-display font-bold text-white mb-6">
            {t('cta_custom_title')}
          </h2>
          <p className="text-xl text-white/90 mb-8">{t('cta_custom_description')}</p>
          <Button
            asChild
            size="lg"
            variant="secondary"
            className="shine-button-cta bg-secondary text-primary hover:bg-secondary/90 shadow-lg"
          >
            <Link href={`/${locale}/custom-trip` as any}>
              <MessageCircle className="mr-2 h-5 w-5" />
              {t('cta_custom_button')}
            </Link>
          </Button>
        </div>
      </div>
    </section>
  );
}
