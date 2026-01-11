'use client';

/* eslint-disable @typescript-eslint/no-explicit-any -- typed routes for blog pages not yet defined */
import { useTranslations } from 'next-intl';
import Link from 'next/link';
import Image from 'next/image';
import { useLocale } from 'next-intl';

interface BlogPostKey {
  id: string;
  slug: string;
  titleKey: string;
  excerptKey: string;
  image: string;
  dateKey: string;
  categoryKey: string;
}

// Blog posts use translation keys for dynamic content
const blogPostKeys = [
  {
    id: '1',
    slug: 'top-10-hidden-gems-tunisia',
    titleKey: 'blog_post_1_title',
    excerptKey: 'blog_post_1_excerpt',
    image: 'https://images.unsplash.com/photo-1590059390047-f5e617690a0b?w=600',
    dateKey: 'blog_post_1_date',
    categoryKey: 'blog_post_1_category',
  },
  {
    id: '2',
    slug: 'sustainable-travel-tunisia',
    titleKey: 'blog_post_2_title',
    excerptKey: 'blog_post_2_excerpt',
    image: 'https://images.unsplash.com/photo-1509099836639-18ba1795216d?w=600',
    dateKey: 'blog_post_2_date',
    categoryKey: 'blog_post_2_category',
  },
  {
    id: '3',
    slug: 'tunisian-cuisine-guide',
    titleKey: 'blog_post_3_title',
    excerptKey: 'blog_post_3_excerpt',
    image: 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=600',
    dateKey: 'blog_post_3_date',
    categoryKey: 'blog_post_3_category',
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
            {t('blog_view_all')}
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
          {blogPostKeys.map((post) => (
            <article
              key={post.id}
              className="green-click-shadow bg-white rounded-lg overflow-hidden shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group"
            >
              <Link href={`/${locale}/blog/${post.slug}` as any}>
                {/* Image Top - h-48 */}
                <div className="relative h-48 overflow-hidden">
                  <Image
                    src={post.image}
                    alt={t(post.titleKey)}
                    fill
                    className="object-cover transition-transform duration-300 group-hover:scale-110"
                  />
                </div>
              </Link>

              {/* Content */}
              <div className="p-6">
                {/* Category Kicker */}
                <div className="text-xs font-bold text-secondary uppercase tracking-wide mb-2">
                  {t(post.categoryKey)}
                </div>

                {/* Title */}
                <Link href={`/${locale}/blog/${post.slug}` as any}>
                  <h3 className="font-bold text-xl text-neutral-900 mb-3 group-hover:text-primary transition-colors line-clamp-2">
                    {t(post.titleKey)}
                  </h3>
                </Link>

                {/* Link */}
                <Link
                  href={`/${locale}/blog/${post.slug}` as any}
                  className="inline-flex items-center gap-2 text-primary font-semibold text-sm hover:underline"
                >
                  {t('blog_read_more')} →
                </Link>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
