'use client';

import { useTranslations } from 'next-intl';
import { Link } from '@/i18n/navigation';
import ConsentCheckbox from './ConsentCheckbox';

interface CheckoutConsentsProps {
  termsAccepted: boolean;
  onTermsChange: (accepted: boolean) => void;
  marketingAccepted: boolean;
  onMarketingChange: (accepted: boolean) => void;
  termsError?: string;
}

export default function CheckoutConsents({
  termsAccepted,
  onTermsChange,
  marketingAccepted,
  onMarketingChange,
  termsError,
}: CheckoutConsentsProps) {
  const t = useTranslations('checkout');

  return (
    <div className="space-y-4 mt-6 pt-6 border-t border-gray-200">
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
