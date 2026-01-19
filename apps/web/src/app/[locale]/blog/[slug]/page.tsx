import { Suspense } from 'react';
import { notFound } from 'next/navigation';
import Image from 'next/image';
import { getBlogPost, getRelatedBlogPosts } from '@/lib/api/blog';
import { MainLayout } from '@/components/templates/MainLayout';
import { SanitizedHtml } from '@/components/atoms/SanitizedHtml';
import { BlogImageGallery, KeyTakeaways } from '@/components/blog';
import Link from 'next/link';
import { ArrowLeft } from 'lucide-react';

interface PageProps {
  params: { slug: string; locale: string };
}

export async function generateMetadata({ params }: PageProps) {
  try {
    const { slug, locale } = await params;
    const { data: post } = await getBlogPost(slug, locale);

    return {
      title: post?.seo?.title ?? 'Blog Post',
      description: post?.seo?.description ?? '',
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

  return (
    <>
      {/* Article Header */}
      <div className="bg-gray-900 text-white py-20">
        <div className="container mx-auto px-4 max-w-4xl text-center">
          {post.category && (
            <span
              className="inline-block px-4 py-2 rounded-full text-sm font-semibold mb-4"
              style={{ backgroundColor: post.category.color }}
            >
              {post.category.name}
            </span>
          )}

          <h1 className="text-4xl md:text-5xl font-display font-bold mb-6">{post.title}</h1>

          <div className="flex items-center justify-center gap-6 text-sm text-gray-300">
            <span>{post.author.name}</span>
            <span>•</span>
            <span>{new Date(post.publishedAt).toLocaleDateString()}</span>
            <span>•</span>
            <span>{post.readTimeMinutes} min read</span>
          </div>
        </div>
      </div>

      {/* Header Image/Gallery */}
      {post.headerStyle === 'image' && post.featuredImage && (
        <div className="container mx-auto px-4 -mt-10">
          <div className="max-w-4xl mx-auto">
            <div className="relative aspect-[16/9] rounded-xl overflow-hidden shadow-2xl">
              <Image
                src={post.featuredImage}
                alt={post.title}
                fill
                className="object-cover"
                priority
              />
            </div>
          </div>
        </div>
      )}

      {post.headerStyle === 'gallery' && post.galleryImages.length > 0 && (
        <div className="container mx-auto px-4 -mt-10">
          <div className="max-w-5xl mx-auto">
            <BlogImageGallery images={post.galleryImages} alt={post.title} />
          </div>
        </div>
      )}

      {/* Article Content */}
      <div className="container mx-auto px-4 py-16">
        <div className="max-w-3xl mx-auto">
          {/* Key Takeaways */}
          {post.keyTakeaways && post.keyTakeaways.length > 0 && (
            <KeyTakeaways takeaways={post.keyTakeaways} className="mb-12" />
          )}

          {/* Main Content - Sanitized to prevent XSS */}
          <SanitizedHtml html={post.content} className="prose prose-lg max-w-none" />

          {/* Tags */}
          {post.tags && post.tags.length > 0 && (
            <div className="mt-12 pt-8 border-t">
              <h3 className="text-sm font-semibold text-gray-500 uppercase mb-4">Tags</h3>
              <div className="flex flex-wrap gap-2">
                {post.tags.map((tag) => (
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
              {relatedPosts.data.map((related) => (
                <Link
                  key={related.id}
                  href={`/${locale}/blog/${related.slug}`}
                  className="group block"
                >
                  <div className="bg-white rounded-lg shadow-sm hover:shadow-xl transition-all p-6">
                    <h3 className="font-display font-bold text-lg mb-2 group-hover:text-primary transition-colors">
                      {related.title}
                    </h3>
                    <p className="text-gray-600 text-sm line-clamp-2">{related.excerpt}</p>
                  </div>
                </Link>
              ))}
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
