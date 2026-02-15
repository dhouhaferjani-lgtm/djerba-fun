'use client';

/* eslint-disable @typescript-eslint/no-explicit-any -- next-intl Link requires typed routes, using any for dynamic hrefs */
import { useState, useCallback } from 'react';
import Link from 'next/link';
import { useRouter, useParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { MainLayout } from '@/components/templates/MainLayout';
import { FloatingInput, Button, Card } from '@go-adventure/ui';
import { useAuth } from '@/lib/contexts/AuthContext';
import { TurnstileWidget, useTurnstile } from '@/components/atoms/TurnstileWidget';
import { Mail, Lock, User } from 'lucide-react';
import { AuthMascot, type MascotState } from '@/components/molecules/AuthMascot';

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
  const [focusedField, setFocusedField] = useState<string | null>(null);
  const [mascotState, setMascotState] = useState<MascotState>('idle');

  const handleFocus = useCallback((field: string) => {
    setFocusedField(field);
    setMascotState(field === 'password' || field === 'confirmPassword' ? 'hiding' : 'watching');
  }, []);

  const handleBlur = useCallback(() => {
    setFocusedField(null);
    setMascotState('idle');
  }, []);

  // Calculate watch direction based on which text field is focused and its value length
  const getWatchDirection = () => {
    if (!focusedField) return 0.5;
    const values: Record<string, string> = { firstName, lastName, email };
    const value = values[focusedField] || '';
    return Math.min(value.length / 30, 1);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (password !== confirmPassword) {
      setMascotState('error');
      setError(t('passwords_do_not_match'));
      setTimeout(() => setMascotState('idle'), 1500);
      return;
    }

    if (password.length < 8) {
      setMascotState('error');
      setError(t('password_too_short'));
      setTimeout(() => setMascotState('idle'), 1500);
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
      setMascotState('success');
      setTimeout(
        () => router.push(`/${locale}/auth/verify-email?email=${encodeURIComponent(email)}`),
        800
      );
    } catch (err) {
      setMascotState('error');
      setTimeout(() => {
        if (focusedField) {
          setMascotState(
            focusedField === 'password' || focusedField === 'confirmPassword'
              ? 'hiding'
              : 'watching'
          );
        } else {
          setMascotState('idle');
        }
      }, 1500);
      setError(err instanceof Error ? err.message : t('registration_error'));
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <MainLayout locale={locale}>
      <div className="container mx-auto px-4 py-12">
        <div className="max-w-md mx-auto">
          <AuthMascot state={mascotState} watchDirection={getWatchDirection()} />
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
                    label={t('first_name')}
                    type="text"
                    value={firstName}
                    onChange={(e) => setFirstName(e.target.value)}
                    onFocus={() => handleFocus('firstName')}
                    onBlur={handleBlur}
                    required
                    autoComplete="given-name"
                    icon={<User className="h-4 w-4" />}
                  />

                  <FloatingInput
                    label={t('last_name')}
                    type="text"
                    value={lastName}
                    onChange={(e) => setLastName(e.target.value)}
                    onFocus={() => handleFocus('lastName')}
                    onBlur={handleBlur}
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
                  onFocus={() => handleFocus('email')}
                  onBlur={handleBlur}
                  required
                  autoComplete="email"
                  icon={<Mail className="h-4 w-4" />}
                />

                <FloatingInput
                  label={t('password')}
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  onFocus={() => handleFocus('password')}
                  onBlur={handleBlur}
                  required
                  autoComplete="new-password"
                  helperText={t('password_min_length')}
                  icon={<Lock className="h-4 w-4" />}
                />

                <FloatingInput
                  label={t('confirm_password')}
                  type="password"
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                  onFocus={() => handleFocus('confirmPassword')}
                  onBlur={handleBlur}
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

              <div className="mt-6 text-center text-sm text-neutral-600">
                {t('already_have_account')}{' '}
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
