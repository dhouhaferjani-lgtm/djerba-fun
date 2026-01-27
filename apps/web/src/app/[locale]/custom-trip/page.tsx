import { setRequestLocale } from 'next-intl/server';
import { MainLayout } from '@/components/templates/MainLayout';
import { CustomTripWizard } from './components/CustomTripWizard';
import { HeroSection } from './components/HeroSection';
import { getContactInfo } from '@/lib/api/server';

export default async function CustomTripPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);

  // Fetch contact info from platform settings for WhatsApp and email links
  const contactInfo = await getContactInfo(locale);

  return (
    <MainLayout locale={locale}>
      <HeroSection />
      <div id="wizard-section" className="bg-gray-50 min-h-screen">
        <CustomTripWizard contactInfo={contactInfo} />
      </div>
    </MainLayout>
  );
}
