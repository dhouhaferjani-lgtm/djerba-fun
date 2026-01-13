'use client';

import { useParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { MainLayout } from '@/components/templates/MainLayout';
import { CustomTripWizard } from './components/CustomTripWizard';
import { HeroSection } from './components/HeroSection';

export default function CustomTripPage() {
  const params = useParams();
  const locale = params?.locale as string;
  const t = useTranslations('customTrip');

  return (
    <MainLayout locale={locale}>
      <HeroSection />
      <div id="wizard-section" className="bg-gray-50 min-h-screen">
        <CustomTripWizard />
      </div>
    </MainLayout>
  );
}
