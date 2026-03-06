'use client';

/* eslint-disable @typescript-eslint/no-explicit-any -- next-intl Link requires typed routes, using any for dynamic hrefs */
import { useState, useCallback } from 'react';
import Link from 'next/link';
import { useParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { MainLayout } from '@/components/templates/MainLayout';
import { FloatingInput, Button, Card } from '@djerba-fun/ui';
import { TurnstileWidget, useTurnstile } from '@/components/atoms/TurnstileWidget';
import { Mail, ArrowLeft, Check } from 'lucide-react';
import { AuthMascot, type MascotState } from '@/components/molecules/AuthMascot';
import { authApi } from '@/lib/api/client';

export default function ForgotPasswordPage() {
  const params = useParams();
  const locale = params.locale as string;
  const t = useTranslations('auth');
  const { handleVerify: handleTurnstileVerify, getToken: getTurnstileToken } = useTurnstile();

  const [email, setEmail] = useState('');
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [submitted, setSubmitted] = useState(false);
  const [mascotState, setMascotState] = useState<MascotState>('idle');

  const handleFocus = useCallback(() => {
    setMascotState('watching');
  }, []);

  const handleBlur = useCallback(() => {
    setMascotState('idle');
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setIsLoading(true);

    try {
      await authApi.forgotPassword(email);
      setMascotState('success');
      setSubmitted(true);
    } catch {
      setMascotState('error');
      setTimeout(() => setMascotState('idle'), 1500);
      setError(t('forgot_password_error'));
    } finally {
      setIsLoading(false);
    }
  };

  const watchDirection = Math.min(email.length / 30, 1);

  if (submitted) {
    return (
      <MainLayout locale={locale}>
        <div className="container mx-auto px-4 py-12">
          <div className="max-w-md mx-auto">
            <AuthMascot state="success" watchDirection={0.5} />
            <Card>
              <div className="p-8 text-center">
                <div className="flex justify-center mb-4">
                  <div className="w-16 h-16 bg-success-light rounded-full flex items-center justify-center">
                    <Check className="w-10 h-10 text-success" />
                  </div>
                </div>
                <h1 className="text-2xl font-bold text-neutral-900 mb-2">
                  {t('check_email_for_reset')}
                </h1>
                <p className="text-sm text-gray-600 mb-4">{t('reset_link_sent')}</p>
                <div className="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                  <p className="font-medium text-gray-900 break-all">{email}</p>
                </div>
                <div className="bg-success-light border border-success/20 rounded-lg p-4 mb-6">
                  <p className="text-sm text-success-dark">{t('reset_link_expires')}</p>
                </div>
                <div className="space-y-3">
                  <button
                    onClick={() => {
                      setSubmitted(false);
                      setMascotState('idle');
                    }}
                    className="w-full px-4 py-2 text-sm text-gray-700 border-2 border-gray-300 rounded-full hover:bg-gray-50 transition-colors font-medium"
                  >
                    {t('use_different_email')}
                  </button>
                  <Link
                    href={`/${locale}/auth/login` as any}
                    className="block w-full px-4 py-2 text-sm text-center text-primary border-2 border-primary rounded-full hover:bg-primary/5 transition-colors font-medium"
                  >
                    {t('back_to_login')}
                  </Link>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </MainLayout>
    );
  }

  return (
    <MainLayout locale={locale}>
      <div className="container mx-auto px-4 py-12">
        <div className="max-w-md mx-auto">
          <AuthMascot state={mascotState} watchDirection={watchDirection} />
          <Card>
            <div className="p-8">
              <Link
                href={`/${locale}/auth/login` as any}
                className="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-6"
              >
                <ArrowLeft className="w-4 h-4 mr-1" />
                {t('back_to_login')}
              </Link>

              <h1 className="text-3xl font-bold text-neutral-900 mb-2 text-center">
                {t('forgot_password_title')}
              </h1>
              <p className="text-sm text-gray-600 mb-6 text-center">
                {t('forgot_password_description')}
              </p>

              <form onSubmit={handleSubmit} className="space-y-4">
                {error && (
                  <div className="p-3 bg-error-light border border-error/20 rounded-lg text-error-dark text-sm">
                    {error}
                  </div>
                )}

                <FloatingInput
                  label={t('email')}
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  onFocus={handleFocus}
                  onBlur={handleBlur}
                  required
                  autoComplete="email"
                  icon={<Mail className="h-4 w-4" />}
                />

                <TurnstileWidget onVerify={handleTurnstileVerify} className="flex justify-center" />

                <Button
                  type="submit"
                  variant="primary"
                  size="lg"
                  className="w-full"
                  isLoading={isLoading}
                >
                  {t('send_reset_link')}
                </Button>
              </form>

              <div className="mt-6 text-center text-sm text-neutral-600">
                {t('remember_password')}{' '}
                <Link
                  href={`/${locale}/auth/login` as any}
                  className="text-primary font-semibold hover:underline"
                >
                  {t('login')}
                </Link>
              </div>
            </div>
          </Card>
        </div>
      </div>
    </MainLayout>
  );
}
