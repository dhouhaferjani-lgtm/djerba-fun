import { Suspense } from 'react';
import { getBlogPosts } from '@/lib/api/blog';
import { MainLayout } from '@/components/templates/MainLayout';
import Link from 'next/link';
import Image from 'next/image';
import { ArrowLeft } from 'lucide-react';
import { getTranslations, setRequestLocale } from 'next-intl/server';
import { HeroCarousel } from './components/HeroCarousel';
import { BlogFilters } from './components/BlogFilters';
import { resolveTranslation, type TranslatableField } from '@/lib/utils/translate';

interface PageProps {
  params: Promise<{ locale: string }>;
  searchParams: Promise<{ sort?: string; tag?: string }>;
}

export async function generateMetadata({ params }: PageProps) {
  const { locale } = await params;
  const t = await getTranslations({ locale, namespace: 'blog' });
  return {
    title: t('page_title'),
    description: t('meta_description'),
  };
}

interface BlogPost {
  id: number;
  title: TranslatableField;
  slug: string;
  excerpt: TranslatableField;
  featuredImage: string | null;
  tags: string[];
  readTimeMinutes: number;
  publishedAt: string;
  author: { name: string };
  category: { name: string; color: string } | null;
}

function BlogPostsGrid({
  posts,
  locale,
  noPostsText,
}: {
  posts: BlogPost[];
  locale: string;
  noPostsText: string;
}) {
  // Helper to resolve translations with fallback
  const tr = (field: TranslatableField) => resolveTranslation(field, locale);

  if (!posts || posts.length === 0) {
    return (
      <div className="text-center py-20">
        <p className="text-gray-500 text-lg">{noPostsText}</p>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      {posts.map((post) => (
        <Link
          key={post.id}
          href={`/${locale}/blog/${post.slug}`}
          className="group block bg-white rounded-lg shadow-sm hover:shadow-xl transition-all duration-300"
        >
          {post.featuredImage && (
            <div className="relative h-48 w-full overflow-hidden rounded-t-lg bg-gray-200">
              <Image
                src={post.featuredImage}
                alt={tr(post.title)}
                fill
                className="object-cover group-hover:scale-105 transition-transform duration-300"
              />
            </div>
          )}

          <div className="p-6">
            {post.category && (
              <span
                className="inline-block px-3 py-1 text-xs font-semibold rounded-full mb-3"
                style={{
                  backgroundColor: `${post.category.color}20`,
                  color: post.category.color,
                }}
              >
                {post.category.name}
              </span>
            )}

            <h3 className="text-xl font-display font-bold text-gray-900 mb-2 group-hover:text-primary transition-colors">
              {tr(post.title)}
            </h3>

            <p className="text-gray-600 text-sm mb-4 line-clamp-3">{tr(post.excerpt)}</p>

            <div className="flex items-center justify-between text-xs text-gray-500">
              <span>{post.author.name}</span>
              <span>{post.readTimeMinutes} min read</span>
            </div>
          </div>
        </Link>
      ))}
    </div>
  );
}

export default async function BlogPage({ params, searchParams }: PageProps) {
  const { locale } = await params;
  const { sort = 'newest', tag = '' } = await searchParams;
  setRequestLocale(locale);
  const t = await getTranslations('blog');

  // Fetch posts with tag filter if provided
  const response = await getBlogPosts({
    per_page: 50,
    locale,
    tag: tag || undefined,
  });
  let posts = (response.data || []) as BlogPost[];

  // Extract unique tags from all posts for the filter dropdown
  const allTags = [...new Set(posts.flatMap((post) => post.tags || []))].sort();

  // Sort posts by date
  if (sort === 'oldest') {
    posts = [...posts].sort(
      (a, b) => new Date(a.publishedAt).getTime() - new Date(b.publishedAt).getTime()
    );
  } else {
    // Default: newest first
    posts = [...posts].sort(
      (a, b) => new Date(b.publishedAt).getTime() - new Date(a.publishedAt).getTime()
    );
  }

  // Get hero images from posts
  const heroImages = posts
    .filter((post) => post.featuredImage)
    .map((post) => post.featuredImage as string)
    .slice(0, 5);

  // Filter translations for the BlogFilters component
  const filterTranslations = {
    filterByDate: t('filter_by_date'),
    filterByTag: t('filter_by_tag'),
    allTags: t('all_tags'),
    newestFirst: t('newest_first'),
    oldestFirst: t('oldest_first'),
    clearFilters: t('clear_filters'),
  };

  return (
    <MainLayout locale={locale}>
      {/* Back to Home */}
      <div className="bg-accent">
        <div className="container mx-auto px-4 py-4">
          <Link
            href={`/${locale}`}
            className="inline-flex items-center gap-2 text-primary hover:text-primary/80 transition-colors"
          >
            <ArrowLeft className="w-4 h-4" />
            {t('back_home')}
          </Link>
        </div>
      </div>

      {/* Hero with Image Carousel */}
      <HeroCarousel
        images={heroImages}
        heroLabel={t('hero_label')}
        heroTitle={t('hero_title')}
        heroSubtitle={t('hero_subtitle')}
      />

      <section className="container mx-auto px-4 py-16">
        {/* Filters */}
        <Suspense fallback={null}>
          <BlogFilters locale={locale} tags={allTags} translations={filterTranslations} />
        </Suspense>

        {/* Posts Grid */}
        <BlogPostsGrid posts={posts} locale={locale} noPostsText={t('no_posts')} />
      </section>
    </MainLayout>
  );
}
