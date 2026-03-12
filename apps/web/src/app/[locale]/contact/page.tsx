import type { Metadata } from 'next';
import { getTranslations, setRequestLocale } from 'next-intl/server';
import { getContactPageData } from '@/lib/api/server';
import { ContactPageContent } from './ContactPageContent';

export async function generateMetadata({
  params,
}: {
  params: Promise<{ locale: string }>;
}): Promise<Metadata> {
  const { locale } = await params;
  const t = await getTranslations({ locale, namespace: 'contact_page' });
  return {
    title: t('page_title'),
    description: t('meta_description'),
  };
}

export default async function ContactPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);

  // Fetch CMS data for contact page
  const cmsData = await getContactPageData(locale);

  return <ContactPageContent cmsData={cmsData} />;
}
