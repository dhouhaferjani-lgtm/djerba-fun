import type { Metadata } from 'next';
import { getTranslations, setRequestLocale } from 'next-intl/server';
import { Link } from '@/i18n/navigation';

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations('legal');
  return {
    title: t('privacy_title'),
    description: t('privacy_meta_description'),
  };
}

export default async function PrivacyPage({ params }: { params: Promise<{ locale: string }> }) {
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
          <h1 className="text-3xl font-bold text-gray-900 mb-2">{t('privacy_title')}</h1>
          <p className="text-gray-500 mb-8">
            {t('last_updated')}: {t('privacy_last_updated')}
          </p>

          <div className="prose prose-gray max-w-none">
            {/* Introduction */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('privacy.introduction_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('privacy.introduction_text')}</p>
            </section>

            {/* Data We Collect */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('privacy.data_collected_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('privacy.data_collected_intro')}</p>
              <ul className="list-disc pl-6 text-gray-700 space-y-2">
                <li>{t('privacy.data_collected_name')}</li>
                <li>{t('privacy.data_collected_email')}</li>
                <li>{t('privacy.data_collected_phone')}</li>
                <li>{t('privacy.data_collected_payment')}</li>
                <li>{t('privacy.data_collected_booking')}</li>
                <li>{t('privacy.data_collected_usage')}</li>
              </ul>
            </section>

            {/* How We Use Data */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('privacy.data_usage_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('privacy.data_usage_intro')}</p>
              <ul className="list-disc pl-6 text-gray-700 space-y-2">
                <li>{t('privacy.data_usage_booking')}</li>
                <li>{t('privacy.data_usage_communication')}</li>
                <li>{t('privacy.data_usage_improvement')}</li>
                <li>{t('privacy.data_usage_marketing')}</li>
                <li>{t('privacy.data_usage_legal')}</li>
              </ul>
            </section>

            {/* Data Sharing */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('privacy.data_sharing_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('privacy.data_sharing_intro')}</p>
              <ul className="list-disc pl-6 text-gray-700 space-y-2">
                <li>{t('privacy.data_sharing_vendors')}</li>
                <li>{t('privacy.data_sharing_payment')}</li>
                <li>{t('privacy.data_sharing_legal')}</li>
              </ul>
            </section>

            {/* Data Retention */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('privacy.retention_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('privacy.retention_text')}</p>
              <ul className="list-disc pl-6 text-gray-700 space-y-2">
                <li>{t('privacy.retention_bookings')}</li>
                <li>{t('privacy.retention_cancelled')}</li>
                <li>{t('privacy.retention_abandoned')}</li>
              </ul>
            </section>

            {/* Your Rights */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('privacy.rights_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('privacy.rights_intro')}</p>
              <ul className="list-disc pl-6 text-gray-700 space-y-2">
                <li>{t('privacy.rights_access')}</li>
                <li>{t('privacy.rights_rectification')}</li>
                <li>{t('privacy.rights_erasure')}</li>
                <li>{t('privacy.rights_restriction')}</li>
                <li>{t('privacy.rights_portability')}</li>
                <li>{t('privacy.rights_object')}</li>
              </ul>
            </section>

            {/* Contact */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('privacy.contact_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('privacy.contact_text')}</p>
              <p className="text-gray-700">
                <strong>{t('privacy.contact_email_label')}:</strong>{' '}
                <a href="mailto:privacy@djerba.fun" className="text-primary hover:underline">
                  privacy@djerba.fun
                </a>
              </p>
            </section>
          </div>
        </article>
      </main>

      {/* Footer Links */}
      <footer className="max-w-4xl mx-auto px-4 sm:px-6 pb-12">
        <div className="flex flex-wrap gap-4 text-sm text-gray-600">
          <Link href="/terms" className="hover:text-primary">
            {t('terms_title')}
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
