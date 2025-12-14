'use client';

import { useTranslations } from 'next-intl';
import Link from 'next/link';
import Image from 'next/image';
import { useLocale } from 'next-intl';

interface Activity {
  id: string;
  slug: string;
  title: string;
  summary: string;
  image: string;
  price: number;
  rating: number;
  reviewsCount: number;
  duration?: string;
  type: 'tour' | 'event';
}

interface LatestToursEventsSectionProps {
  activities?: Activity[];
}

const defaultActivities: Activity[] = [
  {
    id: '1',
    slug: 'djerba-island-discovery-tour',
    title: 'Djerba Island Discovery Tour',
    summary:
      'Explore the enchanting island of Djerba with its stunning beaches and ancient synagogue.',
    image: 'https://images.unsplash.com/photo-1590059390047-f5e617690a0b?w=600',
    price: 85,
    rating: 4.8,
    reviewsCount: 42,
    duration: '8 hours',
    type: 'tour',
  },
  {
    id: '2',
    slug: 'sahara-desert-camel-trek',
    title: 'Sahara Desert Camel Trek',
    summary: 'Experience the magic of the Sahara with a sunset camel trek and overnight camping.',
    image: 'https://images.unsplash.com/photo-1509099836639-18ba1795216d?w=600',
    price: 195,
    rating: 4.9,
    reviewsCount: 38,
    duration: '2 days',
    type: 'tour',
  },
  {
    id: '3',
    slug: 'tunisian-cooking-masterclass',
    title: 'Tunisian Cooking Masterclass',
    summary: 'Learn to prepare authentic Tunisian dishes with a local chef in a traditional home.',
    image: 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=600',
    price: 75,
    rating: 4.7,
    reviewsCount: 28,
    duration: '4 hours',
    type: 'event',
  },
  {
    id: '4',
    slug: 'medina-tunis-walking-tour',
    title: 'Medina of Tunis Walking Tour',
    summary: 'Discover the UNESCO-listed Medina with its labyrinthine streets and historic souks.',
    image: 'https://images.unsplash.com/photo-1590492106698-05dc0e19fb26?w=600',
    price: 45,
    rating: 4.6,
    reviewsCount: 56,
    duration: '4 hours',
    type: 'tour',
  },
];

export function LatestToursEventsSection({
  activities = defaultActivities,
}: LatestToursEventsSectionProps) {
  const t = useTranslations('home');
  const tCommon = useTranslations('common');
  const locale = useLocale();

  return (
    <section className="py-20 bg-white">
      <div className="container mx-auto px-4">
        <div className="flex justify-between items-end mb-12">
          <div>
            <h2 className="text-3xl md:text-4xl font-bold text-neutral-darker mb-4">
              {t('latest_tours_events_title')}
            </h2>
            <p className="text-lg text-neutral-dark max-w-2xl">
              {t('latest_tours_events_subtitle')}
            </p>
          </div>
          <Link
            href={`/${locale}/listings`}
            className="hidden md:flex items-center gap-2 text-primary font-semibold hover:underline"
          >
            {tCommon('view_all')}
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M17 8l4 4m0 0l-4 4m4-4H3"
              />
            </svg>
          </Link>
        </div>

        <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          {activities.map((activity) => (
            <Link
              key={activity.id}
              href={`/${locale}/listings/${activity.slug}`}
              className="group bg-neutral-light rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-shadow duration-300"
            >
              <div className="relative aspect-[4/3] overflow-hidden">
                <Image
                  src={activity.image}
                  alt={activity.title}
                  fill
                  className="object-cover transition-transform duration-500 group-hover:scale-110"
                />
                <div className="absolute top-4 left-4">
                  <span
                    className={`
                    px-3 py-1 rounded-full text-xs font-semibold uppercase
                    ${activity.type === 'tour' ? 'bg-primary text-white' : 'bg-secondary text-white'}
                  `}
                  >
                    {activity.type}
                  </span>
                </div>
              </div>
              <div className="p-5">
                <div className="flex items-center gap-2 mb-2">
                  <div className="flex items-center text-warning">
                    <svg className="w-4 h-4 fill-current" viewBox="0 0 20 20">
                      <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z" />
                    </svg>
                  </div>
                  <span className="font-semibold text-sm">{activity.rating}</span>
                  <span className="text-neutral-darker text-sm">({activity.reviewsCount})</span>
                </div>
                <h3 className="font-bold text-neutral-darker mb-2 group-hover:text-primary transition-colors line-clamp-2">
                  {activity.title}
                </h3>
                <p className="text-sm text-neutral-dark mb-4 line-clamp-2">{activity.summary}</p>
                <div className="flex justify-between items-center pt-4 border-t border-neutral">
                  {activity.duration && (
                    <span className="text-sm text-neutral-dark">{activity.duration}</span>
                  )}
                  <div className="text-right">
                    <span className="text-xs text-neutral-darker">{tCommon('from')}</span>
                    <span className="text-lg font-bold text-primary ml-1">{activity.price}</span>
                  </div>
                </div>
              </div>
            </Link>
          ))}
        </div>

        <div className="mt-8 text-center md:hidden">
          <Link
            href={`/${locale}/listings`}
            className="inline-flex items-center gap-2 text-primary font-semibold"
          >
            {tCommon('view_all')}
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M17 8l4 4m0 0l-4 4m4-4H3"
              />
            </svg>
          </Link>
        </div>
      </div>
    </section>
  );
}
