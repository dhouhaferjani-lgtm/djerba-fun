import { Suspense } from 'react';
import { getBlogPosts } from '@/lib/api/blog';
import { MainLayout } from '@/components/templates/MainLayout';
import Link from 'next/link';
import Image from 'next/image';
import { ArrowLeft } from 'lucide-react';

interface PageProps {
  params: { locale: string };
}

export const metadata = {
  title: 'Blog',
  description: 'Guides, stories, and inspiration from the dunes of Tunisia',
};

async function BlogPostsGrid({ locale }: { locale: string }) {
  const response = await getBlogPosts({ per_page: 12, locale });
  const posts = response.data;

  if (!posts || posts.length === 0) {
    return (
      <div className="text-center py-20">
        <p className="text-gray-500 text-lg">No blog posts found. Check back soon!</p>
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
              {/* Image placeholder */}
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
              {post.title}
            </h3>

            <p className="text-gray-600 text-sm mb-4 line-clamp-3">{post.excerpt}</p>

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

export default async function BlogPage({ params }: PageProps) {
  const { locale } = await params;

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
            {locale === 'fr' ? "Retour à l'accueil" : 'Back to Home'}
          </Link>
        </div>
      </div>

      <section className="bg-primary py-16">
        <div className="container mx-auto px-4 text-center">
          <p className="text-secondary text-sm font-semibold uppercase tracking-wide mb-2">
            Our Journal
          </p>
          <h1 className="text-4xl md:text-5xl font-display font-bold text-white mb-4">
            Tales from the Dunes
          </h1>
          <p className="text-white/90 text-lg max-w-2xl mx-auto">
            Guides, stories, and inspiration for your Tunisian adventure
          </p>
        </div>
      </section>

      <section className="container mx-auto px-4 py-16">
        <Suspense fallback={<div className="text-center">Loading...</div>}>
          <BlogPostsGrid locale={locale} />
        </Suspense>
      </section>
    </MainLayout>
  );
}
