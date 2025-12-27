'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { Link } from '@/i18n/navigation';
import { Button } from '@go-adventure/ui';
import { Mail, ArrowLeft, Check } from 'lucide-react';
import { useSendMagicLink } from '@/lib/api/hooks';

/**
 * Passwordless login page
 * Sends magic link to user's email for instant authentication
 */
export default function PasswordlessLoginPage() {
  const t = useTranslations('auth');
  const [email, setEmail] = useState('');
  const [submitted, setSubmitted] = useState(false);
  const sendMagicLinkMutation = useSendMagicLink();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    try {
      await sendMagicLinkMutation.mutateAsync(email);
      setSubmitted(true);
    } catch (error) {
      // Error is handled by mutation
      console.error('Failed to send magic link:', error);
    }
  };

  if (submitted) {
    return (
      <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
        <div className="max-w-md w-full bg-white rounded-lg shadow-md p-8">
          {/* Success Icon */}
          <div className="flex justify-center mb-6">
            <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
              <Check className="w-10 h-10 text-green-600" />
            </div>
          </div>

          {/* Title */}
          <h1 className="text-2xl font-bold text-center text-gray-900 mb-2">
            {t('check_your_email') || 'Check your email'}
          </h1>
          <p className="text-center text-gray-600 mb-6">
            {t('magic_link_sent_to') || "We've sent a magic link to"}
          </p>

          {/* Email Badge */}
          <div className="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
            <p className="text-center font-medium text-gray-900 break-all">{email}</p>
          </div>

          {/* Instructions */}
          <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <p className="text-sm text-green-800">
              {t('link_expires_15_min') || 'This link expires in 15 minutes.'}
            </p>
            <p className="text-sm text-green-800 mt-2">
              Click the link in your email to log in instantly - no password needed!
            </p>
          </div>

          {/* Actions */}
          <div className="space-y-3">
            <button
              onClick={() => setSubmitted(false)}
              className="w-full px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            >
              {t('use_different_email') || 'Use a different email'}
            </button>
            <Link
              href="/auth/login"
              className="block w-full px-4 py-2 text-sm text-center text-primary border border-primary rounded-lg hover:bg-primary/5 transition-colors"
            >
              {t('use_password_instead') || 'Use password instead'}
            </Link>
          </div>

          {/* Didn't receive link */}
          <p className="text-xs text-center text-gray-500 mt-6">
            Didn&apos;t receive the email? Check your spam folder or{' '}
            <button onClick={() => setSubmitted(false)} className="text-primary hover:underline">
              try again
            </button>
            .
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
      <div className="max-w-md w-full bg-white rounded-lg shadow-md p-8">
        {/* Back Link */}
        <Link
          href="/auth/login"
          className="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-6"
        >
          <ArrowLeft className="w-4 h-4 mr-1" />
          {t('back_to_login') || 'Back to login'}
        </Link>

        {/* Header */}
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-14 h-14 bg-primary/10 rounded-full mb-4">
            <Mail className="w-7 h-7 text-primary" />
          </div>
          <h1 className="text-2xl font-bold text-gray-900 mb-2">
            {t('passwordless_login') || 'Login without password'}
          </h1>
          <p className="text-gray-600">
            {t('passwordless_description') || "We'll send you a secure link to log in instantly"}
          </p>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="space-y-6">
          <div>
            <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">
              {t('email') || 'Email address'}
            </label>
            <div className="relative">
              <Mail className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
              <input
                id="email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                placeholder="your@email.com"
                required
                autoFocus
              />
            </div>
          </div>

          <Button
            type="submit"
            variant="primary"
            size="lg"
            className="w-full"
            isLoading={sendMagicLinkMutation.isPending}
          >
            {t('send_magic_link') || 'Send magic link'}
          </Button>

          {/* Error Display */}
          {sendMagicLinkMutation.isError && (
            <div className="p-4 bg-red-50 border border-red-200 rounded-lg">
              <p className="text-sm text-red-800">
                {sendMagicLinkMutation.error?.message ||
                  t('send_magic_link_error') ||
                  'Failed to send magic link. Please try again.'}
              </p>
            </div>
          )}
        </form>

        {/* Divider */}
        <div className="relative my-6">
          <div className="absolute inset-0 flex items-center">
            <div className="w-full border-t border-gray-200" />
          </div>
          <div className="relative flex justify-center text-sm">
            <span className="px-2 bg-white text-gray-500">or</span>
          </div>
        </div>

        {/* Alternative Login Link */}
        <Link
          href="/auth/login"
          className="block w-full px-4 py-3 text-center text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
        >
          {t('login_with_password') || 'Login with password'}
        </Link>

        {/* Info Note */}
        <div className="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
          <p className="text-xs text-green-800">
            <strong>How it works:</strong> Enter your email and we&apos;ll send you a secure link.
            Click the link to log in instantly - no password needed! The link expires in 15 minutes
            for security.
          </p>
        </div>
      </div>
    </div>
  );
}
