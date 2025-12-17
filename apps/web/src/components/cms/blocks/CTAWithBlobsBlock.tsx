'use client';

import { Button } from '@go-adventure/ui';
import Link from 'next/link';
import { ArrowRight } from 'lucide-react';

export interface CTAWithBlobsBlockData {
  title: string;
  text?: string;
  button_label: string;
  button_url: string;
  button_variant?: 'primary' | 'secondary' | 'outline';
}

export function CTAWithBlobsBlock({
  title,
  text,
  button_label,
  button_url,
  button_variant = 'secondary',
}: CTAWithBlobsBlockData) {
  return (
    <section className="cta-with-blobs-block relative overflow-hidden bg-primary py-20">
      {/* Decorative Blobs */}
      <div className="absolute top-0 left-0 w-96 h-96 bg-secondary rounded-full filter blur-3xl opacity-20" />
      <div className="absolute bottom-0 right-0 w-96 h-96 bg-secondary rounded-full filter blur-3xl opacity-20" />
      <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-secondary rounded-full filter blur-3xl opacity-10" />

      <div className="container mx-auto px-4 relative z-10">
        <div className="max-w-3xl mx-auto text-center">
          <h2 className="text-4xl md:text-5xl font-display font-bold text-white mb-6">{title}</h2>

          {text && <p className="text-xl text-white/90 mb-8">{text}</p>}

          <Link href={button_url as any}>
            <Button size="lg" variant={button_variant} className="gap-2">
              {button_label}
              <ArrowRight className="w-5 h-5" />
            </Button>
          </Link>
        </div>
      </div>
    </section>
  );
}
