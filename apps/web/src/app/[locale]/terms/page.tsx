import type { Metadata } from 'next';
import { getTranslations, setRequestLocale } from 'next-intl/server';
import { Link } from '@/i18n/navigation';

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations('legal');
  return {
    title: t('terms_title'),
    description: t('terms_meta_description'),
  };
}

export default async function TermsPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations('legal');

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white border-b">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 py-4">
          <Link href="/" className="text-primary hover:underline">
            &larr; {t('back_to_home')}
          </Link>
        </div>
      </header>

      {/* Content */}
      <main className="max-w-4xl mx-auto px-4 sm:px-6 py-12">
        <article className="bg-white rounded-lg shadow-sm p-8 md:p-12">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">{t('terms_title')}</h1>
          <p className="text-gray-500 mb-8">
            {t('last_updated')}: {t('terms_last_updated')}
          </p>

          <div className="prose prose-gray max-w-none">
            {/* Introduction */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('terms.introduction_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('terms.introduction_text')}</p>
            </section>

            {/* Service Description */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('terms.service_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('terms.service_text')}</p>
            </section>

            {/* User Obligations */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('terms.obligations_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('terms.obligations_intro')}</p>
              <ul className="list-disc pl-6 text-gray-700 space-y-2">
                <li>{t('terms.obligations_accurate')}</li>
                <li>{t('terms.obligations_legal')}</li>
                <li>{t('terms.obligations_respect')}</li>
                <li>{t('terms.obligations_payment')}</li>
              </ul>
            </section>

            {/* Booking Terms */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('terms.booking_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('terms.booking_text')}</p>
            </section>

            {/* Cancellation Policy */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('terms.cancellation_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('terms.cancellation_text')}</p>
              <ul className="list-disc pl-6 text-gray-700 space-y-2">
                <li>{t('terms.cancellation_48h')}</li>
                <li>{t('terms.cancellation_24h')}</li>
                <li>{t('terms.cancellation_noshow')}</li>
              </ul>
            </section>

            {/* Liability */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('terms.liability_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('terms.liability_text')}</p>
            </section>

            {/* Intellectual Property */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">{t('terms.ip_title')}</h2>
              <p className="text-gray-700 mb-4">{t('terms.ip_text')}</p>
            </section>

            {/* Governing Law */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">{t('terms.law_title')}</h2>
              <p className="text-gray-700 mb-4">{t('terms.law_text')}</p>
            </section>

            {/* Contact */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('terms.contact_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('terms.contact_text')}</p>
              <p className="text-gray-700 mb-2">
                <strong>{t('terms.contact_email_label')}:</strong>{' '}
                <a href="mailto:contact@go-adventure.net" className="text-primary hover:underline">
                  contact@go-adventure.net
                </a>
              </p>
              <p className="text-gray-700">
                <strong>{t('terms.contact_phone_label')}:</strong>{' '}
                <a href="tel:+21652665202" className="text-primary hover:underline">
                  +216 52 665 202
                </a>
              </p>
            </section>
          </div>
        </article>
      </main>

      {/* Footer Links */}
      <footer className="max-w-4xl mx-auto px-4 sm:px-6 pb-12">
        <div className="flex flex-wrap gap-4 text-sm text-gray-600">
          <Link href="/privacy" className="hover:text-primary">
            {t('privacy_title')}
          </Link>
          <span className="text-gray-300">|</span>
          <Link href="/cookies" className="hover:text-primary">
            {t('cookies_title')}
          </Link>
        </div>
      </footer>
    </div>
  );
}
