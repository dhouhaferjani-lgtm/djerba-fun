import type { Metadata } from 'next';
import { getTranslations, setRequestLocale } from 'next-intl/server';
import { Link } from '@/i18n/navigation';

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations('legal');
  return {
    title: t('cookies_title'),
    description: t('cookies_meta_description'),
  };
}

export default async function CookiesPage({ params }: { params: Promise<{ locale: string }> }) {
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
          <h1 className="text-3xl font-bold text-gray-900 mb-2">{t('cookies_title')}</h1>
          <p className="text-gray-500 mb-8">
            {t('last_updated')}: {t('cookies_last_updated')}
          </p>

          <div className="prose prose-gray max-w-none">
            {/* Introduction */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('cookies_policy.introduction_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('cookies_policy.introduction_text')}</p>
            </section>

            {/* What Are Cookies */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('cookies_policy.what_are_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('cookies_policy.what_are_text')}</p>
            </section>

            {/* Essential Cookies */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('cookies_policy.essential_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('cookies_policy.essential_text')}</p>
              <div className="bg-gray-50 rounded-lg p-4 mt-4">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b">
                      <th className="text-left py-2 font-medium">
                        {t('cookies_policy.table_name')}
                      </th>
                      <th className="text-left py-2 font-medium">
                        {t('cookies_policy.table_purpose')}
                      </th>
                      <th className="text-left py-2 font-medium">
                        {t('cookies_policy.table_duration')}
                      </th>
                    </tr>
                  </thead>
                  <tbody className="text-gray-600">
                    <tr className="border-b">
                      <td className="py-2">session_id</td>
                      <td className="py-2">{t('cookies_policy.cookie_session')}</td>
                      <td className="py-2">{t('cookies_policy.duration_session')}</td>
                    </tr>
                    <tr className="border-b">
                      <td className="py-2">csrf_token</td>
                      <td className="py-2">{t('cookies_policy.cookie_csrf')}</td>
                      <td className="py-2">{t('cookies_policy.duration_session')}</td>
                    </tr>
                    <tr>
                      <td className="py-2">cookie_consent</td>
                      <td className="py-2">{t('cookies_policy.cookie_consent')}</td>
                      <td className="py-2">{t('cookies_policy.duration_1year')}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>

            {/* Analytics Cookies */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('cookies_policy.analytics_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('cookies_policy.analytics_text')}</p>
              <div className="bg-gray-50 rounded-lg p-4 mt-4">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b">
                      <th className="text-left py-2 font-medium">
                        {t('cookies_policy.table_name')}
                      </th>
                      <th className="text-left py-2 font-medium">
                        {t('cookies_policy.table_purpose')}
                      </th>
                      <th className="text-left py-2 font-medium">
                        {t('cookies_policy.table_duration')}
                      </th>
                    </tr>
                  </thead>
                  <tbody className="text-gray-600">
                    <tr className="border-b">
                      <td className="py-2">_ga</td>
                      <td className="py-2">{t('cookies_policy.cookie_ga')}</td>
                      <td className="py-2">{t('cookies_policy.duration_2years')}</td>
                    </tr>
                    <tr>
                      <td className="py-2">_ga_*</td>
                      <td className="py-2">{t('cookies_policy.cookie_ga_id')}</td>
                      <td className="py-2">{t('cookies_policy.duration_2years')}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>

            {/* Marketing Cookies */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('cookies_policy.marketing_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('cookies_policy.marketing_text')}</p>
            </section>

            {/* Managing Cookies */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('cookies_policy.manage_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('cookies_policy.manage_text')}</p>
              <ul className="list-disc pl-6 text-gray-700 space-y-2">
                <li>{t('cookies_policy.manage_banner')}</li>
                <li>{t('cookies_policy.manage_browser')}</li>
              </ul>
            </section>

            {/* Third Party Cookies */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('cookies_policy.third_party_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('cookies_policy.third_party_text')}</p>
            </section>

            {/* Contact */}
            <section className="mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">
                {t('cookies_policy.contact_title')}
              </h2>
              <p className="text-gray-700 mb-4">{t('cookies_policy.contact_text')}</p>
              <p className="text-gray-700">
                <strong>{t('cookies_policy.contact_email_label')}:</strong>{' '}
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
          <Link href="/privacy" className="hover:text-primary">
            {t('privacy_title')}
          </Link>
          <span className="text-gray-300">|</span>
          <Link href="/terms" className="hover:text-primary">
            {t('terms_title')}
          </Link>
        </div>
      </footer>
    </div>
  );
}
