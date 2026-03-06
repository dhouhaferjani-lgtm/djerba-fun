import { getTranslations } from 'next-intl/server';
import Link from 'next/link';
import Image from 'next/image';
import { getFeaturedBlogPosts, BlogPost } from '@/lib/api/blog';
import { resolveTranslation, type TranslatableField } from '@/lib/utils/translate';

interface CmsData {
  enabled: boolean;
  title: string | null;
  subtitle: string | null;
  postLimit: number;
}

interface BlogSectionProps {
  locale: string;
  cmsData?: CmsData;
}

export async function BlogSection({ locale, cmsData }: BlogSectionProps) {
  const t = await getTranslations('home');
  const postLimit = cmsData?.postLimit ?? 3;

  // Helper to resolve translations with fallback
  const tr = (field: TranslatableField) => resolveTranslation(field, locale);

  // Fetch featured blog posts from API
  let posts: BlogPost[] = [];
  try {
    const response = await getFeaturedBlogPosts(postLimit, locale);
    posts = response.data || [];
  } catch (error) {
    console.error('Failed to fetch blog posts:', error);
  }

  // Don't render section if no posts
  if (posts.length === 0) {
    return null;
  }

  return (
    <section className="py-20 bg-neutral-light">
      <div className="container mx-auto px-4">
        <div className="flex justify-between items-end mb-12">
          <div>
            <h2 className="text-3xl md:text-4xl font-bold text-neutral-darker mb-4">
              {cmsData?.title || t('blog_title')}
            </h2>
            <p className="text-lg text-neutral-dark max-w-2xl">
              {cmsData?.subtitle || t('blog_subtitle')}
            </p>
          </div>
          <Link
            href={`/${locale}/blog`}
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
          {posts.map((post) => (
            <article
              key={post.id}
              className="green-click-shadow bg-white rounded-lg overflow-hidden shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group"
            >
              <Link href={`/${locale}/blog/${post.slug}`}>
                <div className="relative h-48 overflow-hidden">
                  {post.featuredImage ? (
                    <Image
                      src={post.featuredImage}
                      alt={tr(post.title)}
                      fill
                      className="object-cover transition-transform duration-300 group-hover:scale-110"
                    />
                  ) : (
                    <div className="w-full h-full bg-neutral-200 flex items-center justify-center">
                      <span className="text-neutral-400">No image</span>
                    </div>
                  )}
                </div>
              </Link>

              <div className="p-6">
                {post.category && (
                  <div
                    className="text-xs font-bold uppercase tracking-wide mb-2"
                    style={{ color: post.category.color }}
                  >
                    {post.category.name}
                  </div>
                )}

                <Link href={`/${locale}/blog/${post.slug}`}>
                  <h3 className="font-bold text-xl text-neutral-900 mb-3 group-hover:text-primary transition-colors line-clamp-2">
                    {tr(post.title)}
                  </h3>
                </Link>

                <Link
                  href={`/${locale}/blog/${post.slug}`}
                  className="inline-flex items-center gap-2 text-primary font-semibold text-sm hover:underline"
                >
                  {t('blog_read_more')} &rarr;
                </Link>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
