import { setRequestLocale } from 'next-intl/server';
import { MainLayout } from '@/components/templates/MainLayout';
import { CartPage } from '@/components/cart/CartPage';

export default async function CartRoute({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);

  return (
    <MainLayout locale={locale}>
      <CartPage locale={locale} />
    </MainLayout>
  );
}
