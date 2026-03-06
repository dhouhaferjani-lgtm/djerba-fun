'use client';

import { Button } from '@djerba-fun/ui';
import Link from 'next/link';
import { CallToActionBlockData } from '@/types/cms';

export function CallToActionBlock({
  title,
  text,
  button_label,
  button_url,
  background_colour = 'primary',
}: CallToActionBlockData) {
  const bgColors: Record<string, string> = {
    primary: 'bg-primary',
    secondary: 'bg-secondary',
    accent: 'bg-accent',
    white: 'bg-white',
  };

  const textColors: Record<string, string> = {
    primary: 'text-white',
    secondary: 'text-primary',
    accent: 'text-primary',
    white: 'text-primary',
  };

  const bgClass = bgColors[background_colour] || 'bg-primary';
  const textClass = textColors[background_colour] || 'text-white';

  return (
    <div className={`cta-block ${bgClass} ${textClass} p-12 rounded-lg text-center`}>
      <h2 className="text-4xl font-display font-bold mb-4">{title}</h2>

      {text && <p className="text-xl mb-6 max-w-2xl mx-auto">{text}</p>}

      {button_label && button_url && (
        <Link href={button_url as any}>
          <Button size="lg" variant={background_colour === 'white' ? 'primary' : 'secondary'}>
            {button_label}
          </Button>
        </Link>
      )}
    </div>
  );
}
