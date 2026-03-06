'use client';

import { useState, useEffect } from 'react';
import { useSearchParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Link } from '@/i18n/navigation';
import { FloatingInput, Button } from '@djerba-fun/ui';
import { User, Mail, Phone, Check, Sparkles } from 'lucide-react';
import { useRegisterPasswordless } from '@/lib/api/hooks';
import { TurnstileWidget, useTurnstile } from '@/components/atoms/TurnstileWidget';

// Form validation schema
const registerSchema = z.object({
  email: z.string().email('Invalid email address'),
  firstName: z.string().min(1, 'First name is required').max(100),
  lastName: z.string().min(1, 'Last name is required').max(100),
  phone: z.string().optional(),
  preferredLocale: z.enum(['en', 'fr']),
});

type RegisterFormData = z.infer<typeof registerSchema>;

/**
 * Quick registration page - passwordless account creation
 * Typically accessed after booking completion for guest users
 * Pre-fills email from query params and links bookings after verification
 */
export default function QuickRegisterPage() {
  const searchParams = useSearchParams();
  const t = useTranslations('auth');
  const [submitted, setSubmitted] = useState(false);
  const [submittedEmail, setSubmittedEmail] = useState('');

  const registerMutation = useRegisterPasswordless();
  const { handleVerify: handleTurnstileVerify, getToken: getTurnstileToken } = useTurnstile();

  // Get email and bookingId from query params
  const emailParam = searchParams.get('email');
  const bookingIdParam = searchParams.get('bookingId');

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    control,
  } = useForm<RegisterFormData>({
    resolver: zodResolver(registerSchema),
    defaultValues: {
      email: emailParam || '',
      preferredLocale: 'en',
    },
  });

  // Set email from query param when it changes
  useEffect(() => {
    if (emailParam) {
      setValue('email', emailParam);
    }
  }, [emailParam, setValue]);

  const onSubmit = async (data: RegisterFormData) => {
    try {
      await registerMutation.mutateAsync({
        email: data.email,
        firstName: data.firstName,
        lastName: data.lastName,
        phone: data.phone,
        preferredLocale: data.preferredLocale,
        cfTurnstileResponse: getTurnstileToken(),
      });

      setSubmittedEmail(data.email);
      setSubmitted(true);
    } catch (error) {
      // Error handled by mutation
      console.error('Registration failed:', error);
    }
  };

  if (submitted) {
    return (
      <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
        <div className="max-w-md w-full bg-white rounded-lg shadow-md p-8">
          {/* Success Icon */}
          <div className="flex justify-center mb-6">
            <div className="w-16 h-16 bg-success-light rounded-full flex items-center justify-center">
              <Check className="w-10 h-10 text-success" />
            </div>
          </div>

          {/* Title */}
          <h1 className="text-2xl font-bold text-center text-gray-900 mb-2">
            {t('check_your_email') || 'Check your email'}
          </h1>
          <p className="text-center text-gray-600 mb-6">
            {t('verification_email_sent') || "We've sent a verification email to"}
          </p>

          {/* Email Badge */}
          <div className="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
            <p className="text-center font-medium text-gray-900 break-all">{submittedEmail}</p>
          </div>

          {/* Instructions */}
          <div className="bg-success-light border border-success/20 rounded-lg p-4 mb-6">
            <p className="text-sm text-success-dark mb-2">
              Click the verification link in your email to activate your account.
            </p>
            {bookingIdParam && (
              <p className="text-sm text-success-dark">
                <strong>We found your recent booking!</strong> After verification, we&apos;ll link
                it to your account automatically.
              </p>
            )}
          </div>

          {/* Info */}
          <div className="bg-primary/5 border border-primary/20 rounded-lg p-4 mb-6">
            <h3 className="font-semibold text-gray-900 mb-2 flex items-center gap-2">
              <Sparkles className="w-4 h-4 text-primary" />
              What&apos;s next?
            </h3>
            <ul className="space-y-2 text-sm text-gray-700">
              <li className="flex items-start gap-2">
                <span className="text-primary mt-0.5">1.</span>
                <span>Check your inbox (and spam folder)</span>
              </li>
              <li className="flex items-start gap-2">
                <span className="text-primary mt-0.5">2.</span>
                <span>Click the verification link</span>
              </li>
              <li className="flex items-start gap-2">
                <span className="text-primary mt-0.5">3.</span>
                <span>Your account will be activated instantly</span>
              </li>
              {bookingIdParam && (
                <li className="flex items-start gap-2">
                  <span className="text-primary mt-0.5">4.</span>
                  <span>View your booking in your new dashboard</span>
                </li>
              )}
            </ul>
          </div>

          {/* Action */}
          <Link
            href="/"
            className="block w-full px-4 py-2 text-sm text-center text-gray-700 border-2 border-gray-300 rounded-full hover:bg-gray-50 transition-colors font-medium"
          >
            {t('back_home') || 'Back to home'}
          </Link>

          <p className="text-xs text-center text-gray-500 mt-6">
            Didn&apos;t receive the email? Check your spam folder or contact support.
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
      <div className="max-w-md w-full bg-white rounded-lg shadow-md p-8">
        {/* Header */}
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-14 h-14 bg-primary/10 rounded-full mb-4">
            <Sparkles className="w-7 h-7 text-primary" />
          </div>
          <h1 className="text-2xl font-bold text-gray-900 mb-2">
            {t('create_your_account') || 'Create Your Account'}
          </h1>
          <p className="text-gray-600">
            {t('create_account_subtitle') ||
              'Join to track bookings, save favorites, and get exclusive offers'}
          </p>
        </div>

        {/* Benefits - only show if coming from booking */}
        {bookingIdParam && (
          <div className="bg-gradient-to-r from-primary/10 to-primary-light/10 border border-primary/30 rounded-lg p-4 mb-6">
            <p className="text-sm text-gray-800 mb-2">
              <strong>✨ Your booking is ready to link!</strong>
            </p>
            <p className="text-xs text-gray-700">
              After verification, we&apos;ll automatically link your recent booking to your new
              account.
            </p>
          </div>
        )}

        {/* Form */}
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
          {/* Email */}
          <Controller
            name="email"
            control={control}
            render={({ field }) => (
              <FloatingInput
                {...field}
                id="email"
                type="email"
                label={`${t('email') || 'Email'} *`}
                error={errors.email?.message}
                disabled={!!emailParam}
                icon={<Mail className="h-4 w-4" />}
              />
            )}
          />

          {/* First Name */}
          <Controller
            name="firstName"
            control={control}
            render={({ field }) => (
              <FloatingInput
                {...field}
                id="firstName"
                type="text"
                label={`${t('first_name') || 'First Name'} *`}
                error={errors.firstName?.message}
                icon={<User className="h-4 w-4" />}
              />
            )}
          />

          {/* Last Name */}
          <Controller
            name="lastName"
            control={control}
            render={({ field }) => (
              <FloatingInput
                {...field}
                id="lastName"
                type="text"
                label={`${t('last_name') || 'Last Name'} *`}
                error={errors.lastName?.message}
                icon={<User className="h-4 w-4" />}
              />
            )}
          />

          {/* Phone (optional) */}
          <Controller
            name="phone"
            control={control}
            render={({ field }) => (
              <FloatingInput
                {...field}
                id="phone"
                type="tel"
                label={t('phone') || 'Phone (optional)'}
                icon={<Phone className="h-4 w-4" />}
              />
            )}
          />

          {/* Preferred Language */}
          <div>
            <label
              htmlFor="preferredLocale"
              className="block text-sm font-medium text-gray-700 mb-1"
            >
              {t('preferred_language') || 'Preferred Language'}
            </label>
            <select
              id="preferredLocale"
              {...register('preferredLocale')}
              className="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
            >
              <option value="en">English</option>
              <option value="fr">Français</option>
            </select>
          </div>

          {/* Turnstile Widget */}
          <TurnstileWidget onVerify={handleTurnstileVerify} className="flex justify-center" />

          {/* Submit Button */}
          <Button
            type="submit"
            variant="primary"
            size="lg"
            className="w-full"
            isLoading={registerMutation.isPending}
          >
            {t('create_account') || 'Create Account'}
          </Button>

          {/* Error Display */}
          {registerMutation.isError && (
            <div className="p-4 bg-error-light border border-error/20 rounded-lg">
              <p className="text-sm text-error-dark">
                {registerMutation.error?.message ||
                  t('registration_error') ||
                  'Registration failed. Please try again.'}
              </p>
            </div>
          )}
        </form>

        {/* Info Note */}
        <div className="mt-6 p-4 bg-success-light border border-success/20 rounded-lg">
          <p className="text-xs text-success-dark">
            <strong>No password needed!</strong> We&apos;ll send you a verification email. Click the
            link to activate your account - it&apos;s that simple.
          </p>
        </div>

        {/* Already have account */}
        <p className="text-sm text-center text-gray-600 mt-6">
          Already have an account?{' '}
          <Link href="/auth/login" className="text-primary hover:underline font-medium">
            {t('login') || 'Log in'}
          </Link>
        </p>
      </div>
    </div>
  );
}
