import { Metadata } from 'next';
import { notFound } from 'next/navigation';
import Image from 'next/image';
import { getTranslations } from 'next-intl/server';
import { getPage } from '@/lib/api/cms';
import type { CMSPage } from '@/types/cms';
import { Link } from '@/i18n/navigation';
import {
  HighlightsSection,
  KeyFactsBar,
  GallerySection,
  POISection,
  BlockRenderer,
} from '@/components/cms';

interface PageProps {
  params: Promise<{ locale: string; slug: string }>;
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { locale, slug } = await params;

  try {
    const page = await getPage({ slug, locale });

    return {
      title: page.seoTitle || page.title,
      description: page.seoDescription || page.description || undefined,
      openGraph: {
        title: page.seoTitle || page.title,
        description: page.seoDescription || page.description || undefined,
        images: page.heroImage ? [{ url: page.heroImage }] : undefined,
      },
    };
  } catch {
    return {
      title: 'Page Not Found',
    };
  }
}

export default async function DynamicPage({ params }: PageProps) {
  const { locale, slug } = await params;
  const t = await getTranslations('pages');

  let page: CMSPage;

  try {
    page = await getPage({ slug, locale });
  } catch {
    notFound();
  }

  return (
    <main className="min-h-screen">
      {/* Hero Section */}
      {page.heroImage && (
        <section className="relative h-[50vh] min-h-[400px] bg-gray-900">
          <Image
            src={page.heroImage}
            alt={page.heroImageTitle || page.title}
            fill
            priority
            sizes="100vw"
            className="object-cover opacity-80"
          />

          <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />

          <div className="absolute inset-0 flex flex-col items-center justify-center text-center text-white p-4">
            <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold mb-4 max-w-4xl">
              {page.title}
            </h1>

            {page.intro && (
              <p className="text-lg md:text-xl text-gray-200 max-w-2xl mb-8">{page.intro}</p>
            )}

            {/* Hero CTA Buttons */}
            {page.heroCallToActions && page.heroCallToActions.length > 0 && (
              <div className="flex flex-wrap gap-4 justify-center">
                {page.heroCallToActions.map((cta, index) => (
                  <Link
                    key={index}
                    href={cta.url as any}
                    target={cta.buttonOpenNewWindow ? '_blank' : undefined}
                    className={`px-6 py-3 rounded-lg font-semibold transition-colors ${
                      cta.buttonStyle === 'primary'
                        ? 'bg-primary-600 hover:bg-primary-700 text-white'
                        : 'bg-white/20 hover:bg-white/30 text-white border border-white/40'
                    }`}
                  >
                    {cta.buttonLabel}
                  </Link>
                ))}
              </div>
            )}
          </div>

          {/* Hero image copyright */}
          {page.heroImageCopyright && (
            <div className="absolute bottom-2 right-4 text-white/60 text-xs">
              {page.heroImageCopyright}
            </div>
          )}
        </section>
      )}

      {/* Page Title (if no hero image) */}
      {!page.heroImage && (
        <section className="py-16 bg-primary-800 text-white">
          <div className="container mx-auto px-4 text-center">
            <h1 className="text-4xl md:text-5xl font-bold mb-4">{page.title}</h1>
            {page.intro && (
              <p className="text-lg text-primary-100 max-w-2xl mx-auto">{page.intro}</p>
            )}
          </div>
        </section>
      )}

      {/* Key Facts Bar */}
      {page.keyFacts && page.keyFacts.length > 0 && <KeyFactsBar facts={page.keyFacts} />}

      {/* Description Section */}
      {page.description && (
        <section className="py-12">
          <div className="container mx-auto px-4">
            <div className="prose prose-lg max-w-4xl mx-auto">
              <div dangerouslySetInnerHTML={{ __html: page.description }} />
            </div>
          </div>
        </section>
      )}

      {/* Highlights Section */}
      {page.highlights && page.highlights.length > 0 && (
        <HighlightsSection highlights={page.highlights} title={t('whatAwaitsYou')} />
      )}

      {/* Gallery Section */}
      {page.gallery && page.gallery.length > 0 && (
        <GallerySection images={page.gallery} title={t('gallery')} />
      )}

      {/* Points of Interest Section */}
      {page.pointsOfInterest && page.pointsOfInterest.length > 0 && (
        <POISection pois={page.pointsOfInterest} title={t('mustSeePlaces')} />
      )}

      {/* Legacy Content Blocks */}
      {page.contentBlocks && page.contentBlocks.length > 0 && (
        <section className="py-12">
          <div className="container mx-auto px-4">
            <BlockRenderer blocks={page.contentBlocks} />
          </div>
        </section>
      )}

      {/* SEO Text Section */}
      {page.seoText && (
        <section className="py-12 bg-gray-50">
          <div className="container mx-auto px-4">
            <div className="prose prose-lg max-w-4xl mx-auto">
              <div dangerouslySetInnerHTML={{ __html: page.seoText }} />
            </div>
          </div>
        </section>
      )}

      {/* Link CTA */}
      {page.link && (
        <section className="py-12 bg-primary-800 text-white">
          <div className="container mx-auto px-4 text-center">
            <Link
              href={page.link as any}
              className="inline-block px-8 py-4 bg-white text-primary-800 font-semibold rounded-lg hover:bg-gray-100 transition-colors"
            >
              {t('exploreMore')}
            </Link>
          </div>
        </section>
      )}
    </main>
  );
}
