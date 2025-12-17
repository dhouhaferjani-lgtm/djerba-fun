'use client';

import { useTranslations } from 'next-intl';
import Image from 'next/image';
import Link from 'next/link';
import { Button } from '@go-adventure/ui';
import { ArrowRight } from 'lucide-react';

interface PromoBannerSectionProps {
  locale: string;
}

export function PromoBannerSection({ locale }: PromoBannerSectionProps) {
  const t = useTranslations('home');

  return (
    <section className="py-16 bg-accent">
      <div className="container mx-auto px-4">
        <div className="relative h-[500px] rounded-lg overflow-hidden">
          {/* Background Image */}
          <div className="absolute inset-0">
            <Image
              src="https://images.unsplash.com/photo-1682687220742-aba13b6e50ba?w=1920"
              alt="Ultra Mirage Event"
              fill
              className="object-cover"
            />
            {/* Horizontal Gradient Overlay */}
            <div className="absolute inset-0 bg-gradient-to-r from-primary to-transparent" />
          </div>

          {/* Content - Left Aligned */}
          <div className="relative h-full flex items-center">
            <div className="max-w-2xl px-8 md:px-16">
              {/* Event Tag */}
              <div className="inline-block bg-secondary px-4 py-2 rounded-full mb-6">
                <span className="text-sm font-bold text-primary uppercase tracking-wide">
                  Event of the Year
                </span>
              </div>

              {/* Title */}
              <h2 className="text-4xl md:text-5xl lg:text-6xl font-display font-bold text-white mb-6 leading-tight">
                Ultra Mirage
                <br />
                Marathon 2025
              </h2>

              <p className="text-xl text-white/90 mb-8 max-w-lg">
                Experience the ultimate desert challenge. 165km across the Sahara's most stunning
                landscapes.
              </p>

              {/* Buttons */}
              <div className="flex flex-wrap gap-4">
                <Button asChild size="lg" className="bg-white text-primary hover:bg-white/90">
                  <Link href={`/${locale}/events/ultra-mirage-2025`}>
                    Learn More
                    <ArrowRight className="ml-2 h-5 w-5" />
                  </Link>
                </Button>
                <Button
                  asChild
                  variant="outline"
                  size="lg"
                  className="border-white text-white hover:bg-white hover:text-primary"
                >
                  <Link href={`/${locale}/events/ultra-mirage-2025#register`}>Register Now</Link>
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
