'use client';

import { forwardRef } from 'react';
import { useTranslations } from 'next-intl';
import { Link } from '@/i18n/navigation';
import ConsentCheckbox from './ConsentCheckbox';

interface CheckoutConsentsProps {
  termsAccepted: boolean;
  onTermsChange: (accepted: boolean) => void;
  marketingAccepted: boolean;
  onMarketingChange: (accepted: boolean) => void;
  termsError?: string;
  highlight?: boolean;
}

const CheckoutConsents = forwardRef<HTMLDivElement, CheckoutConsentsProps>(
  (
    { termsAccepted, onTermsChange, marketingAccepted, onMarketingChange, termsError, highlight },
    ref
  ) => {
    const t = useTranslations('checkout');

    return (
      <div
        ref={ref}
        className={`
          space-y-4 mt-6 pt-6 border-t border-gray-200
          transition-all duration-300 ease-in-out
          ${highlight ? 'scale-[1.02] bg-green-50 p-4 -mx-4 rounded-lg border-2 border-green-500 shadow-lg ring-2 ring-green-400/30 animate-pulse' : ''}
        `}
      >
        <h3 className="font-medium text-gray-900">{t('legal_agreements') || 'Legal Agreements'}</h3>

        <ConsentCheckbox
          id="terms-consent"
          checked={termsAccepted}
          onChange={onTermsChange}
          required
          error={termsError}
          label={
            <span>
              {t('terms_consent_text') || 'I accept the'}{' '}
              <Link href="/terms" target="_blank" className="text-primary hover:underline">
                {t('terms_of_service') || 'Terms of Service'}
              </Link>{' '}
              {t('and') || 'and'}{' '}
              <Link href="/privacy" target="_blank" className="text-primary hover:underline">
                {t('privacy_policy') || 'Privacy Policy'}
              </Link>
            </span>
          }
        />

        <ConsentCheckbox
          id="marketing-consent"
          checked={marketingAccepted}
          onChange={onMarketingChange}
          label={t('marketing_consent_text') || 'Send me travel inspiration and special offers'}
          description={t('marketing_consent_description') || 'You can unsubscribe at any time.'}
        />
      </div>
    );
  }
);

CheckoutConsents.displayName = 'CheckoutConsents';

export default CheckoutConsents;
