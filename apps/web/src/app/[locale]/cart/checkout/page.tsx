import { getTranslations, setRequestLocale } from 'next-intl/server';
import { MainLayout } from '@/components/templates/MainLayout';
import { CartCheckoutClient } from '@/components/cart/CartCheckoutClient';

interface CheckoutPageProps {
  params: Promise<{ locale: string }>;
}

export async function generateMetadata({ params }: CheckoutPageProps) {
  const { locale } = await params;
  const t = await getTranslations({ locale, namespace: 'cart.checkout' });

  return {
    title: t('page_title'),
    description: t('page_description'),
  };
}

export default async function CartCheckoutPage({ params }: CheckoutPageProps) {
  const { locale } = await params;
  setRequestLocale(locale);

  return (
    <MainLayout locale={locale}>
      <CartCheckoutClient locale={locale} />
    </MainLayout>
  );
}
