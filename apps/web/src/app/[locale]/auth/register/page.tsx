'use client';

/* eslint-disable @typescript-eslint/no-explicit-any -- next-intl Link requires typed routes, using any for dynamic hrefs */
import { useState, use } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { MainLayout } from '@/components/templates/MainLayout';
import { Input, Button, Card } from '@go-adventure/ui';
import { useAuth } from '@/lib/contexts/AuthContext';

export default function RegisterPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = use(params);
  const t = useTranslations('auth');
  const router = useRouter();
  const { register } = useAuth();

  const [displayName, setDisplayName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (password !== confirmPassword) {
      setError('Passwords do not match');
      return;
    }

    if (password.length < 8) {
      setError('Password must be at least 8 characters');
      return;
    }

    setIsLoading(true);

    try {
      await register(email, password, displayName);
      router.push(`/${locale}`);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Registration failed');
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
              <h1 className="text-3xl font-bold text-neutral-900 mb-6 text-center">
                {t('create_account')}
              </h1>

              <form onSubmit={handleSubmit} className="space-y-4">
                {error && (
                  <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                    {error}
                  </div>
                )}

                <Input
                  label="Display Name"
                  type="text"
                  value={displayName}
                  onChange={(e) => setDisplayName(e.target.value)}
                  required
                  autoComplete="name"
                />

                <Input
                  label={t('email')}
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                  autoComplete="email"
                />

                <Input
                  label={t('password')}
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                  autoComplete="new-password"
                  helperText="Minimum 8 characters"
                />

                <Input
                  label="Confirm Password"
                  type="password"
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                  required
                  autoComplete="new-password"
                />

                <Button
                  type="submit"
                  variant="primary"
                  size="lg"
                  className="w-full"
                  isLoading={isLoading}
                >
                  {t('create_account')}
                </Button>
              </form>

              <div className="mt-6 text-center text-sm text-neutral-600">
                Already have an account?{' '}
                <Link
                  href={`/${locale}/auth/login` as any}
                  className="text-[#0D642E] font-semibold hover:underline"
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
