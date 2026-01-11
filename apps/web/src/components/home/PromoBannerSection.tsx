import Image from 'next/image';
import Link from 'next/link';
import { Button } from '@go-adventure/ui';
import { ArrowRight } from 'lucide-react';

// Default fallback values when CMS data is not available
const DEFAULT_EVENT = {
  tag: 'Event of the Year',
  title: 'Djerba Music\nFestival 2025',
  description:
    "Three days of world music, traditional Tunisian performances, and international artists on Djerba's stunning beaches.",
  link: '/en/djerba/djerba-music-festival-2025',
  image: 'https://images.unsplash.com/photo-1682687220742-aba13b6e50ba?w=1920',
};

interface EventOfYearData {
  enabled: boolean;
  tag: string | null;
  title: string | null;
  description: string | null;
  link: string | null;
  image: string | null;
}

interface PromoBannerSectionProps {
  locale: string;
  eventOfYear?: EventOfYearData;
}

export function PromoBannerSection({ locale, eventOfYear }: PromoBannerSectionProps) {
  // Don't render if event of year is disabled
  if (eventOfYear && !eventOfYear.enabled) {
    return null;
  }

  // Use CMS data or fallback to defaults
  const tag = eventOfYear?.tag || DEFAULT_EVENT.tag;
  const title = eventOfYear?.title || DEFAULT_EVENT.title;
  const description = eventOfYear?.description || DEFAULT_EVENT.description;
  const image = eventOfYear?.image || DEFAULT_EVENT.image;

  // Build the link - if CMS provides a relative link, prepend locale; otherwise use default
  let eventLink = eventOfYear?.link || DEFAULT_EVENT.link;
  if (eventLink && !eventLink.startsWith('http') && !eventLink.startsWith(`/${locale}`)) {
    // If the link doesn't start with the locale, add it
    eventLink = eventLink.startsWith('/')
      ? `/${locale}${eventLink.slice(3)}`
      : `/${locale}/${eventLink}`;
  }

  // Parse title for line breaks (stored with \n in CMS)
  const titleLines = title.split('\n');

  return (
    <section className="py-16 bg-accent">
      <div className="container mx-auto px-4">
        <div className="relative h-[500px] rounded-lg overflow-hidden">
          {/* Background Image */}
          <div className="absolute inset-0">
            <Image src={image} alt={tag} fill className="object-cover" />
            {/* Horizontal Gradient Overlay */}
            <div className="absolute inset-0 bg-gradient-to-r from-primary to-transparent" />
          </div>

          {/* Content - Left Aligned */}
          <div className="relative h-full flex items-center">
            <div className="max-w-2xl px-8 md:px-16">
              {/* Event Tag */}
              <div className="inline-block bg-secondary px-4 py-2 rounded-full mb-6">
                <span className="text-sm font-bold text-primary uppercase tracking-wide">
                  {tag}
                </span>
              </div>

              {/* Title */}
              <h2 className="text-4xl md:text-5xl lg:text-6xl font-display font-bold text-white mb-6 leading-tight">
                {titleLines.map((line, index) => (
                  <span key={index}>
                    {line}
                    {index < titleLines.length - 1 && <br />}
                  </span>
                ))}
              </h2>

              <p className="text-xl text-white/90 mb-8 max-w-lg">{description}</p>

              {/* Buttons */}
              <div className="flex flex-wrap gap-4">
                <Button asChild size="lg" className="bg-white text-primary hover:bg-white/90">
                  <Link href={eventLink as string}>
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
                  <Link href={eventLink as string}>Register Now</Link>
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
