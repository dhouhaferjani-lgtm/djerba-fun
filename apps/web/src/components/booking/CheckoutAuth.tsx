'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { Button } from '@djerba-fun/ui';
import { Mail } from 'lucide-react';

interface CheckoutAuthProps {
  onEmailSubmit: (email: string) => void;
  defaultEmail?: string;
}

/**
 * Email-only checkout component
 * Collects just the email address for booking confirmation
 * No account creation or authentication - true guest checkout
 */
export function CheckoutAuth({ onEmailSubmit, defaultEmail }: CheckoutAuthProps) {
  const t = useTranslations('booking');
  const [email, setEmail] = useState(defaultEmail || '');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (email.trim()) {
      onEmailSubmit(email.trim());
    }
  };

  return (
    <div className="space-y-6">
      <div className="text-center">
        <h2 className="text-2xl font-bold text-neutral-900">
          {t('email_step_title') || 'Where should we send your confirmation?'}
        </h2>
        <p className="text-sm text-neutral-600 mt-2">
          {t('email_step_subtitle') ||
            "We'll email your booking confirmation and access link. No account required."}
        </p>
      </div>

      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label
            htmlFor="checkout-email"
            className="block text-sm font-medium text-neutral-700 mb-2"
          >
            {t('email_label') || 'Email address'}
          </label>
          <div className="relative">
            <Mail className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-neutral-400" />
            <input
              id="checkout-email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full pl-10 pr-4 py-3 text-lg border border-neutral-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
              placeholder="your@email.com"
              required
              autoFocus
              data-testid="traveler-email"
            />
          </div>
        </div>

        <Button
          type="submit"
          variant="primary"
          size="lg"
          className="w-full"
          data-testid="continue-to-extras"
        >
          {t('continue_to_checkout') || 'Continue to Checkout'}
        </Button>

        <p className="text-xs text-neutral-500 text-center">
          {t('no_account_needed') ||
            "No account needed. We'll send you a magic link to manage your booking."}
        </p>
      </form>
    </div>
  );
}
