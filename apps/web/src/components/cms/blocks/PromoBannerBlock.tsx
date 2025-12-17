'use client';

import { Button } from '@go-adventure/ui';
import Link from 'next/link';
import { ArrowRight } from 'lucide-react';

export interface PromoBannerBlockData {
  title: string;
  subtitle?: string;
  tag?: string;
  primary_button_label?: string;
  primary_button_url?: string;
  secondary_button_label?: string;
  secondary_button_url?: string;
  background_colour?: 'primary' | 'secondary' | 'accent' | 'dark';
}

export function PromoBannerBlock({
  title,
  subtitle,
  tag,
  primary_button_label,
  primary_button_url,
  secondary_button_label,
  secondary_button_url,
  background_colour = 'primary',
}: PromoBannerBlockData) {
  const bgGradients = {
    primary: 'from-primary to-transparent',
    secondary: 'from-secondary to-transparent',
    accent: 'from-accent to-transparent',
    dark: 'from-black to-transparent',
  };

  const textColors = {
    primary: 'text-white',
    secondary: 'text-white',
    accent: 'text-primary',
    dark: 'text-white',
  };

  const bgGradient = bgGradients[background_colour];
  const textColor = textColors[background_colour];

  return (
    <section className="promo-banner-block relative overflow-hidden h-[500px] flex items-center">
      {/* Background Gradient */}
      <div className={`absolute inset-0 bg-gradient-to-r ${bgGradient}`} />

      <div className="container mx-auto px-4 relative z-10">
        <div className="max-w-3xl">
          {tag && (
            <span className="inline-block bg-secondary text-primary px-4 py-2 rounded-full text-sm font-semibold mb-4">
              {tag}
            </span>
          )}

          <h2 className={`text-5xl md:text-6xl font-display font-bold ${textColor} mb-4`}>
            {title}
          </h2>

          {subtitle && <p className={`text-xl ${textColor} mb-8 opacity-90`}>{subtitle}</p>}

          <div className="flex flex-wrap gap-4">
            {primary_button_label && primary_button_url && (
              <Link href={primary_button_url as any}>
                <Button size="lg" variant="secondary" className="gap-2">
                  {primary_button_label}
                  <ArrowRight className="w-5 h-5" />
                </Button>
              </Link>
            )}

            {secondary_button_label && secondary_button_url && (
              <Link href={secondary_button_url as any}>
                <Button size="lg" variant="outline" className={textColor}>
                  {secondary_button_label}
                </Button>
              </Link>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}
