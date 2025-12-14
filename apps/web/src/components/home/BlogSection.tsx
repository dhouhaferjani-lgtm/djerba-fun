'use client';

/* eslint-disable @typescript-eslint/no-explicit-any -- typed routes for blog pages not yet defined */
import { useTranslations } from 'next-intl';
import Link from 'next/link';
import Image from 'next/image';
import { useLocale } from 'next-intl';

interface BlogPost {
  id: string;
  slug: string;
  title: string;
  excerpt: string;
  image: string;
  date: string;
  category: string;
  readTime: string;
}

interface BlogSectionProps {
  // locale: string; // Removed as it's fetched internally
}

const blogPosts: BlogPost[] = [
  {
    id: '1',
    slug: 'top-10-hidden-gems-tunisia',
    title: 'Top 10 Hidden Gems in Tunisia You Must Visit',
    excerpt:
      'Discover the lesser-known treasures of Tunisia that most tourists never see. From secret oases to ancient ruins...',
    image: 'https://images.unsplash.com/photo-1590059390047-f5e617690a0b?w=600',
    date: 'Dec 10, 2024',
    category: 'Travel Guide',
    readTime: '8 min read',
  },
  {
    id: '2',
    slug: 'sustainable-travel-tunisia',
    title: 'How to Travel Sustainably in Tunisia',
    excerpt:
      'Tips and practices for eco-conscious travelers who want to explore Tunisia while protecting its natural beauty...',
    image: 'https://images.unsplash.com/photo-1509099836639-18ba1795216d?w=600',
    date: 'Dec 5, 2024',
    category: 'Eco Travel',
    readTime: '6 min read',
  },
  {
    id: '3',
    slug: 'tunisian-cuisine-guide',
    title: "A Food Lover's Guide to Tunisian Cuisine",
    excerpt:
      'From couscous to brik, discover the rich flavors and culinary traditions that make Tunisian food so special...',
    image: 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=600',
    date: 'Nov 28, 2024',
    category: 'Food & Culture',
    readTime: '10 min read',
  },
];

export function BlogSection() {
  const t = useTranslations('home');
  const locale = useLocale();

  return (
    <section className="py-20 bg-neutral-light">
      <div className="container mx-auto px-4">
        <div className="flex justify-between items-end mb-12">
          <div>
            <h2 className="text-3xl md:text-4xl font-bold text-neutral-darker mb-4">
              {t('blog_title')}
            </h2>
            <p className="text-lg text-neutral-dark max-w-2xl">{t('blog_subtitle')}</p>
          </div>
          <Link
            href={`/${locale}/blog` as any}
            className="hidden md:flex items-center gap-2 text-primary font-semibold hover:underline"
          >
            View All Posts
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

        <div className="grid md:grid-cols-3 gap-8">
          {blogPosts.map((post) => (
            <article
              key={post.id}
              className="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-shadow group"
            >
              <Link href={`/${locale}/blog/${post.slug}` as any}>
                <div className="relative aspect-[16/10] overflow-hidden">
                  <Image
                    src={post.image}
                    alt={post.title}
                    fill
                    className="object-cover transition-transform duration-500 group-hover:scale-105"
                  />
                  <div className="absolute top-4 left-4">
                    <span className="bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-semibold text-primary">
                      {post.category}
                    </span>
                  </div>
                </div>
              </Link>
              <div className="p-6">
                <div className="flex items-center gap-4 text-sm text-neutral-darker mb-3">
                  <span>{post.date}</span>
                  <span className="w-1 h-1 bg-neutral-dark rounded-full" />
                  <span>{post.readTime}</span>
                </div>
                <Link href={`/${locale}/blog/${post.slug}` as any}>
                  <h3 className="font-bold text-lg text-neutral-darker mb-2 group-hover:text-primary transition-colors line-clamp-2">
                    {post.title}
                  </h3>
                </Link>
                <p className="text-neutral-dark text-sm line-clamp-2 mb-4">{post.excerpt}</p>
                <Link
                  href={`/${locale}/blog/${post.slug}` as any}
                  className="inline-flex items-center gap-2 text-primary font-semibold text-sm hover:gap-3 transition-all"
                >
                  {t('blog_read_more')}
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M17 8l4 4m0 0l-4 4m4-4H3"
                    />
                  </svg>
                </Link>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
