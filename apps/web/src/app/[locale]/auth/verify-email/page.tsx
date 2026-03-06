'use client';

import { useState, useEffect } from 'react';
import { useSearchParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { useParams } from 'next/navigation';
import Link from 'next/link';
import { MainLayout } from '@/components/templates/MainLayout';
import { Button, Card } from '@djerba-fun/ui';
import { Mail, ArrowLeft } from 'lucide-react';
import { authApi } from '@/lib/api/client';

export default function VerifyEmailPage() {
  const params = useParams();
  const locale = params.locale as string;
  const searchParams = useSearchParams();
  const t = useTranslations('auth');

  const email = searchParams.get('email') || '';
  const [resendCooldown, setResendCooldown] = useState(0);
  const [resendSuccess, setResendSuccess] = useState(false);
  const [resendError, setResendError] = useState('');

  // Countdown timer for resend cooldown
  useEffect(() => {
    if (resendCooldown <= 0) return;
    const timer = setInterval(() => {
      setResendCooldown((prev) => prev - 1);
    }, 1000);
    return () => clearInterval(timer);
  }, [resendCooldown]);

  const handleResend = async () => {
    if (resendCooldown > 0 || !email) return;

    setResendSuccess(false);
    setResendError('');

    try {
      await authApi.resendVerification(email);
      setResendSuccess(true);
      setResendCooldown(60);
    } catch {
      setResendError(t('verify_email_resend_error') || 'Failed to resend. Please try again.');
    }
  };

  return (
    <MainLayout locale={locale}>
      <div className="container mx-auto px-4 py-12">
        <div className="max-w-md mx-auto">
          <Card>
            <div className="p-8 text-center">
              {/* Mail icon */}
              <div className="mx-auto w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-6">
                <Mail className="w-8 h-8 text-primary" />
              </div>

              <h1 className="text-2xl font-bold text-neutral-900 mb-2">
                {t('verify_email_title') || 'Check your email'}
              </h1>

              <p className="text-neutral-600 mb-6">
                {t('verify_email_message') || "We've sent a verification link to"}{' '}
                {email && <span className="font-semibold text-neutral-900">{email}</span>}
              </p>

              {/* Resend button */}
              <div className="space-y-3 mb-6">
                {resendSuccess && (
                  <div className="p-3 bg-success-light border border-success/20 rounded-lg text-success-dark text-sm">
                    {t('verify_email_resent') || 'Verification email sent!'}
                  </div>
                )}

                {resendError && (
                  <div className="p-3 bg-error-light border border-error/20 rounded-lg text-error-dark text-sm">
                    {resendError}
                  </div>
                )}

                <Button
                  variant="outline"
                  className="w-full"
                  onClick={handleResend}
                  disabled={resendCooldown > 0 || !email}
                >
                  {resendCooldown > 0
                    ? `${t('verify_email_resend_wait') || 'Resend in'} ${resendCooldown}s`
                    : t('verify_email_resend') || 'Resend verification email'}
                </Button>
              </div>

              {/* Back to login */}
              {/* eslint-disable-next-line @typescript-eslint/no-explicit-any */}
              <Link
                href={`/${locale}/auth/login` as any}
                className="inline-flex items-center gap-2 text-sm text-neutral-600 hover:text-primary transition-colors"
              >
                <ArrowLeft className="w-4 h-4" />
                {t('back_to_login') || 'Back to login'}
              </Link>
            </div>
          </Card>
        </div>
      </div>
    </MainLayout>
  );
}
