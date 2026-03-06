'use client';

/* eslint-disable @typescript-eslint/no-explicit-any -- next-intl Link requires typed routes, using any for dynamic hrefs */
import { useState, useCallback } from 'react';
import Link from 'next/link';
import { useParams, useSearchParams, useRouter } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { MainLayout } from '@/components/templates/MainLayout';
import { FloatingInput, Button, Card } from '@djerba-fun/ui';
import { Lock, Check } from 'lucide-react';
import { AuthMascot, type MascotState } from '@/components/molecules/AuthMascot';
import { authApi } from '@/lib/api/client';

export default function ResetPasswordPage() {
  const params = useParams();
  const searchParams = useSearchParams();
  const router = useRouter();
  const locale = params.locale as string;
  const token = searchParams.get('token');
  const t = useTranslations('auth');

  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [success, setSuccess] = useState(false);
  const [mascotState, setMascotState] = useState<MascotState>('idle');

  const handleFocus = useCallback(() => {
    setMascotState('hiding');
  }, []);

  const handleBlur = useCallback(() => {
    setMascotState('idle');
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (password.length < 8) {
      setError(t('password_too_short'));
      setMascotState('error');
      setTimeout(() => setMascotState('idle'), 1500);
      return;
    }

    if (password !== passwordConfirmation) {
      setError(t('passwords_do_not_match'));
      setMascotState('error');
      setTimeout(() => setMascotState('idle'), 1500);
      return;
    }

    if (!token) {
      setError(t('reset_password_expired'));
      return;
    }

    setIsLoading(true);

    try {
      await authApi.resetPassword(token, password, passwordConfirmation);
      setMascotState('success');
      setSuccess(true);
      setTimeout(() => router.push(`/${locale}/auth/login`), 2000);
    } catch {
      setMascotState('error');
      setTimeout(() => setMascotState('idle'), 1500);
      setError(t('reset_password_expired'));
    } finally {
      setIsLoading(false);
    }
  };

  if (!token) {
    return (
      <MainLayout locale={locale}>
        <div className="container mx-auto px-4 py-12">
          <div className="max-w-md mx-auto">
            <AuthMascot state="error" watchDirection={0.5} />
            <Card>
              <div className="p-8 text-center">
                <h1 className="text-2xl font-bold text-neutral-900 mb-4">
                  {t('reset_password_expired')}
                </h1>
                <p className="text-sm text-gray-600 mb-6">
                  {t('reset_password_expired_description')}
                </p>
                <Link
                  href={`/${locale}/auth/forgot-password` as any}
                  className="inline-block px-6 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors"
                >
                  {t('request_new_reset_link')}
                </Link>
              </div>
            </Card>
          </div>
        </div>
      </MainLayout>
    );
  }

  if (success) {
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
                  {t('password_reset_success')}
                </h1>
                <p className="text-sm text-gray-600">{t('redirecting_to_login')}</p>
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
          <AuthMascot state={mascotState} watchDirection={0.5} />
          <Card>
            <div className="p-8">
              <h1 className="text-3xl font-bold text-neutral-900 mb-2 text-center">
                {t('reset_password_title')}
              </h1>
              <p className="text-sm text-gray-600 mb-6 text-center">
                {t('reset_password_description')}
              </p>

              <form onSubmit={handleSubmit} className="space-y-4">
                {error && (
                  <div className="p-3 bg-error-light border border-error/20 rounded-lg text-error-dark text-sm">
                    {error}
                  </div>
                )}

                <FloatingInput
                  label={t('new_password')}
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  onFocus={handleFocus}
                  onBlur={handleBlur}
                  required
                  autoComplete="new-password"
                  icon={<Lock className="h-4 w-4" />}
                />

                <FloatingInput
                  label={t('confirm_new_password')}
                  type="password"
                  value={passwordConfirmation}
                  onChange={(e) => setPasswordConfirmation(e.target.value)}
                  onFocus={handleFocus}
                  onBlur={handleBlur}
                  required
                  autoComplete="new-password"
                  icon={<Lock className="h-4 w-4" />}
                />

                <p className="text-xs text-gray-500">{t('password_min_length')}</p>

                <Button
                  type="submit"
                  variant="primary"
                  size="lg"
                  className="w-full"
                  isLoading={isLoading}
                >
                  {t('reset_password_button')}
                </Button>
              </form>
            </div>
          </Card>
        </div>
      </div>
    </MainLayout>
  );
}
