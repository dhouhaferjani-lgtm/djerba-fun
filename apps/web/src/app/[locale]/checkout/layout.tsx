'use client';

import { MainLayout } from '@/components/templates/MainLayout';
import { useLocale } from 'next-intl';

export default function CheckoutLayout({ children }: { children: React.ReactNode }) {
  const locale = useLocale();
  return <MainLayout locale={locale}>{children}</MainLayout>;
}
