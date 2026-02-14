'use client';

/* eslint-disable @typescript-eslint/no-explicit-any -- next-intl Link requires typed routes, using any for dynamic hrefs */
import { useState } from 'react';
import Link from 'next/link';
import { useRouter, useParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { MainLayout } from '@/components/templates/MainLayout';
import { FloatingInput, Button, Card } from '@go-adventure/ui';
import { useAuth } from '@/lib/contexts/AuthContext';
import { TurnstileWidget, useTurnstile } from '@/components/atoms/TurnstileWidget';
import { Mail, Lock } from 'lucide-react';

export default function LoginPage() {
  const params = useParams();
  const locale = params.locale as string;
  const t = useTranslations('auth');
  const router = useRouter();
  const { login } = useAuth();
  const { handleVerify: handleTurnstileVerify, getToken: getTurnstileToken } = useTurnstile();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setIsLoading(true);

    try {
      await login(email, password, getTurnstileToken());
      router.push(`/${locale}`);
    } catch (err: unknown) {
      // Redirect unverified users to the verify-email page
      const apiError = err as { code?: string };
      if (apiError.code === 'EMAIL_NOT_VERIFIED') {
        router.push(`/${locale}/auth/verify-email?email=${encodeURIComponent(email)}`);
        return;
      }
      setError(err instanceof Error ? err.message : 'Login failed');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <MainLayout locale={locale}>
      <div className="container mx-auto px-4 py-12">
        <div className="max-w-md mx-auto">
          <Card>
            <div className="p-8">
              <h1 className="text-3xl font-bold text-neutral-900 mb-6 text-center">{t('login')}</h1>

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
                  required
                  autoComplete="email"
                  icon={<Mail className="h-4 w-4" />}
                />

                <FloatingInput
                  label={t('password')}
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                  autoComplete="current-password"
                  icon={<Lock className="h-4 w-4" />}
                />

                <div className="flex items-center justify-between text-sm">
                  <Link href={`/${locale}` as any} className="text-[#0D642E] hover:underline">
                    {t('forgot_password')}
                  </Link>
                </div>

                <TurnstileWidget onVerify={handleTurnstileVerify} className="flex justify-center" />

                <Button
                  type="submit"
                  variant="primary"
                  size="lg"
                  className="w-full"
                  isLoading={isLoading}
                >
                  {t('login')}
                </Button>
              </form>

              {/* Passwordless Login Link */}
              <Link
                href={`/${locale}/auth/passwordless` as any}
                className="block w-full text-center mt-4 px-4 py-2 border-2 border-gray-300 rounded-full text-sm text-gray-700 font-medium hover:bg-gray-50 transition-colors"
              >
                {t('login_without_password') || 'Login without password'} →
              </Link>

              <div className="mt-6 text-center text-sm text-neutral-600">
                Don&apos;t have an account?{' '}
                <Link
                  href={`/${locale}/auth/register` as any}
                  className="text-[#0D642E] font-semibold hover:underline"
                >
                  {t('register')}
                </Link>
              </div>
            </div>
          </Card>
        </div>
      </div>
    </MainLayout>
  );
}
