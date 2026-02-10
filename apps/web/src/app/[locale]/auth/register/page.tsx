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
import { Mail, Lock, User } from 'lucide-react';
import { SocialLoginButtons } from '@/components/molecules/SocialLoginButtons';

export default function RegisterPage() {
  const params = useParams();
  const locale = params.locale as string;
  const t = useTranslations('auth');
  const router = useRouter();
  const { register } = useAuth();
  const { handleVerify: handleTurnstileVerify, getToken: getTurnstileToken } = useTurnstile();

  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
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
      await register({
        email,
        password,
        passwordConfirmation: confirmPassword,
        firstName,
        lastName,
        displayName: `${firstName} ${lastName}`.trim(),
        role: 'traveler',
        cfTurnstileResponse: getTurnstileToken(),
      });
      router.push(`/${locale}/auth/verify-email?email=${encodeURIComponent(email)}`);
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
                  <div className="p-3 bg-error-light border border-error/20 rounded-lg text-error-dark text-sm">
                    {error}
                  </div>
                )}

                <div className="grid grid-cols-2 gap-4">
                  <FloatingInput
                    label="First Name"
                    type="text"
                    value={firstName}
                    onChange={(e) => setFirstName(e.target.value)}
                    required
                    autoComplete="given-name"
                    icon={<User className="h-4 w-4" />}
                  />

                  <FloatingInput
                    label="Last Name"
                    type="text"
                    value={lastName}
                    onChange={(e) => setLastName(e.target.value)}
                    required
                    autoComplete="family-name"
                    icon={<User className="h-4 w-4" />}
                  />
                </div>

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
                  autoComplete="new-password"
                  helperText="Minimum 8 characters"
                  icon={<Lock className="h-4 w-4" />}
                />

                <FloatingInput
                  label="Confirm Password"
                  type="password"
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                  required
                  autoComplete="new-password"
                  icon={<Lock className="h-4 w-4" />}
                />

                <TurnstileWidget onVerify={handleTurnstileVerify} className="flex justify-center" />

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

              {/* Divider */}
              <div className="relative my-6">
                <div className="absolute inset-0 flex items-center">
                  <div className="w-full border-t border-gray-200" />
                </div>
                <div className="relative flex justify-center text-sm">
                  <span className="px-2 bg-white text-gray-500">
                    {t('or_continue_with') || 'or'}
                  </span>
                </div>
              </div>

              {/* Social Login */}
              <SocialLoginButtons locale={locale} />

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
