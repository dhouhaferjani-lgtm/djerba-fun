import { Suspense } from 'react';
import { notFound } from 'next/navigation';
import { getBlogPost, getRelatedBlogPosts } from '@/lib/api/blog';
import { MainLayout } from '@/components/templates/MainLayout';
import { SanitizedHtml } from '@/components/atoms/SanitizedHtml';
import { BlogHeroSection } from './components/BlogHeroSection';
import Link from 'next/link';
import { ArrowLeft } from 'lucide-react';
import { resolveTranslation, type TranslatableField } from '@/lib/utils/translate';

interface PageProps {
  params: { slug: string; locale: string };
}

export async function generateMetadata({ params }: PageProps) {
  try {
    const { slug, locale } = await params;
    const { data: post } = await getBlogPost(slug, locale);

    // Resolve translations for SEO fields
    const tr = (field: TranslatableField) => resolveTranslation(field, locale);

    return {
      title: tr(post?.seo?.title) || tr(post?.title) || 'Blog Post',
      description: tr(post?.seo?.description) || tr(post?.excerpt) || '',
    };
  } catch {
    return {
      title: 'Post Not Found',
      description: 'The requested blog post could not be found.',
    };
  }
}

async function BlogPostContent({ slug, locale }: { slug: string; locale: string }) {
  let post;
  let relatedPosts;

  try {
    const response = await getBlogPost(slug, locale);
    post = response.data;
    if (!post) {
      notFound();
    }
    relatedPosts = await getRelatedBlogPosts(slug, locale);
  } catch {
    notFound();
  }

  // Helper to resolve translations with fallback
  const tr = (field: TranslatableField) => resolveTranslation(field, locale);

  // Get hero images - prefer heroImages array, fallback to featuredImage for backward compatibility
  const heroImages =
    post.heroImages?.length > 0 ? post.heroImages : post.featuredImage ? [post.featuredImage] : [];

  return (
    <>
      {/* Article Header with Hero Carousel */}
      <BlogHeroSection
        images={heroImages}
        title={tr(post.title)}
        category={post.category}
        author={post.author}
        publishedAt={post.publishedAt}
        readTimeMinutes={post.readTimeMinutes}
      />

      {/* Article Content */}
      <div className="container mx-auto px-4 py-16">
        <div className="max-w-3xl mx-auto">
          {/* Main Content - Sanitized to prevent XSS */}
          <SanitizedHtml html={tr(post.content)} className="prose prose-lg max-w-none" />

          {/* Tags */}
          {post.tags && post.tags.length > 0 && (
            <div className="mt-12 pt-8 border-t">
              <h3 className="text-sm font-semibold text-gray-500 uppercase mb-4">Tags</h3>
              <div className="flex flex-wrap gap-2">
                {post.tags.map((tag: string) => (
                  <span
                    key={tag}
                    className="px-3 py-1 bg-gray-100 text-gray-700 text-sm rounded-full"
                  >
                    {tag}
                  </span>
                ))}
              </div>
            </div>
          )}

          {/* Author Bio */}
          <div className="mt-12 p-6 bg-gray-50 rounded-lg">
            <h3 className="font-semibold text-lg mb-2">About the Author</h3>
            <p className="text-gray-600">{post.author.name}</p>
          </div>
        </div>

        {/* Related Posts */}
        {relatedPosts.data && relatedPosts.data.length > 0 && (
          <div className="max-w-6xl mx-auto mt-20">
            <h2 className="text-3xl font-display font-bold mb-8">Related Stories</h2>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
              {relatedPosts.data.map(
                (related: {
                  id: number;
                  slug: string;
                  title: TranslatableField;
                  excerpt: TranslatableField;
                }) => (
                  <Link
                    key={related.id}
                    href={`/${locale}/blog/${related.slug}`}
                    className="group block"
                  >
                    <div className="bg-white rounded-lg shadow-sm hover:shadow-xl transition-all p-6">
                      <h3 className="font-display font-bold text-lg mb-2 group-hover:text-primary transition-colors">
                        {tr(related.title)}
                      </h3>
                      <p className="text-gray-600 text-sm line-clamp-2">{tr(related.excerpt)}</p>
                    </div>
                  </Link>
                )
              )}
            </div>
          </div>
        )}
      </div>
    </>
  );
}

export default async function BlogPostPage({ params }: PageProps) {
  const { locale, slug } = await params;

  return (
    <MainLayout locale={locale}>
      {/* Back to Blog */}
      <div className="bg-accent">
        <div className="container mx-auto px-4 py-4">
          <Link
            href={`/${locale}/blog`}
            className="inline-flex items-center gap-2 text-primary hover:text-primary/80 transition-colors"
          >
            <ArrowLeft className="w-4 h-4" />
            Back to Blog
          </Link>
        </div>
      </div>

      <Suspense fallback={<div className="text-center py-20">Loading...</div>}>
        <BlogPostContent slug={slug} locale={locale} />
      </Suspense>
    </MainLayout>
  );
}
