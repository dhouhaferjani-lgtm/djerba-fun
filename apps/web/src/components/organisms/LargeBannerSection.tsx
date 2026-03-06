'use client';

import { useTranslations } from 'next-intl';
import Link from 'next/link';
import Image from 'next/image';
import { Button } from '@djerba-fun/ui';

export function LargeBannerSection() {
  const t = useTranslations('home');

  return (
    <section className="py-20 bg-secondary overflow-hidden">
      <div className="container mx-auto px-4">
        <div className="flex flex-col lg:flex-row items-center gap-12">
          <div className="flex-1 max-w-xl">
            <span className="text-primary font-semibold text-sm uppercase tracking-wider">
              {t('large_banner_subtitle')}
            </span>
            <h2 className="text-4xl md:text-5xl font-bold text-neutral-darker mt-2 mb-6">
              {t('large_banner_title')}
            </h2>
            <p className="text-lg text-neutral-dark leading-relaxed mb-8">
              {t('large_banner_description')}
            </p>
            <Link href="/en/listings?type=tour&category=eco-friendly" passHref>
              <Button variant="primary" size="lg" className="inline-flex items-center gap-2">
                {t('large_banner_cta')}
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M17 8l4 4m0 0l-4 4m4-4H3"
                  />
                </svg>
              </Button>
            </Link>
          </div>
          <div className="flex-1 relative">
            <div className="relative w-full aspect-[4/3] rounded-2xl overflow-hidden shadow-2xl">
              <Image
                src="https://images.unsplash.com/photo-1510414936301-b51139453716?q=80&w=1920&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D"
                alt="Eco-friendly package"
                fill
                className="object-cover"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent" />
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
