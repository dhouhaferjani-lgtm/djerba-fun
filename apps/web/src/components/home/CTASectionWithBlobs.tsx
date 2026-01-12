'use client';

import { useTranslations } from 'next-intl';
import Link from 'next/link';
import { Button } from '@go-adventure/ui';
import { MessageCircle } from 'lucide-react';

interface CTASectionWithBlobsProps {
  locale: string;
}

export function CTASectionWithBlobs({ locale }: CTASectionWithBlobsProps) {
  const t = useTranslations('home');

  return (
    <section className="relative py-24 bg-primary overflow-hidden">
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
            className="bg-secondary text-primary hover:bg-secondary/90 shadow-lg"
          >
            <Link href={`/${locale}/contact` as any}>
              <MessageCircle className="mr-2 h-5 w-5" />
              {t('cta_custom_button')}
            </Link>
          </Button>
        </div>
      </div>
    </section>
  );
}
