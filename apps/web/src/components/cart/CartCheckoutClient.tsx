'use client';

import { useTranslations } from 'next-intl';
import { useRouter } from 'next/navigation';
import { ArrowLeft } from 'lucide-react';
import Link from 'next/link';
import { CartCheckoutWizard } from './CartCheckoutWizard';
import { CartCheckoutSummary } from './CartCheckoutSummary';
import { useCartContext } from '@/lib/contexts/CartContext';

interface CartCheckoutClientProps {
  locale: string;
}

export function CartCheckoutClient({ locale }: CartCheckoutClientProps) {
  const t = useTranslations('cart.checkout');
  const tCart = useTranslations('cart');
  const { cart } = useCartContext();

  return (
    <div className="bg-gray-50 min-h-screen">
      {/* Header */}
      <div className="bg-white border-b border-gray-200">
        <div className="container mx-auto px-4 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <Link
                href={`/${locale}/cart`}
                className="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
              >
                <ArrowLeft className="h-5 w-5" />
                <span className="hidden sm:inline">{t('back') || 'Back to Cart'}</span>
              </Link>
              <div className="h-6 w-px bg-gray-300 hidden sm:block" />
              <h1 className="text-lg font-semibold text-gray-900">
                {t('page_title') || 'Checkout'}
              </h1>
            </div>
          </div>
        </div>
      </div>

      {/* Checkout Content - Two Column Layout */}
      <div className="container mx-auto px-4 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Left Column - Checkout Form (2/3 width on large screens) */}
          <div className="lg:col-span-2">
            <CartCheckoutWizard locale={locale} />
          </div>

          {/* Right Column - Order Summary (1/3 width on large screens, sticky) */}
          <div className="lg:col-span-1">
            <div className="sticky top-24">
              <CartCheckoutSummary cart={cart} currency={cart?.currency || 'EUR'} />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
