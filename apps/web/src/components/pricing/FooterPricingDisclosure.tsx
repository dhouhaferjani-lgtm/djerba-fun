'use client';

import { useTranslations } from 'next-intl';

export function FooterPricingDisclosure() {
  const t = useTranslations('footer');

  return (
    <div className="bg-primary border-t border-white/20" data-testid="footer-pricing-disclosure">
      <div className="container mx-auto px-4 py-4">
        <p className="text-white/80 text-sm text-center">
          {t('ppp_disclosure') ||
            'Prices may vary depending on country, currency, and billing address. We adapt pricing to ensure fair access across regions. The final price is confirmed at checkout.'}
        </p>
      </div>
    </div>
  );
}
