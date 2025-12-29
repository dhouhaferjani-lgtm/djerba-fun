'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { Link } from '@/i18n/navigation';
import { Check, XCircle, Loader2 } from 'lucide-react';
import { useVerifyMagicLink } from '@/lib/api/hooks';

/**
 * Magic link verification page
 * Verifies the token and logs the user in
 */
export default function VerifyMagicLinkPage() {
  const params = useParams();
  const router = useRouter();
  const t = useTranslations('auth');
  const token = params.token as string;

  const [verificationStatus, setVerificationStatus] = useState<'verifying' | 'success' | 'error'>(
    'verifying'
  );
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const verifyMutation = useVerifyMagicLink();

  useEffect(() => {
    if (!token) {
      setVerificationStatus('error');
      setErrorMessage(t('invalid_token') || 'Invalid verification link');
      return;
    }

    // Verify the magic link token
    const verifyToken = async () => {
      try {
        await verifyMutation.mutateAsync({ token });
        setVerificationStatus('success');

        // Redirect to dashboard after 2 seconds
        setTimeout(() => {
          router.push('/dashboard');
        }, 2000);
      } catch (error: any) {
        setVerificationStatus('error');

        // Parse error message
        const message = error?.response?.data?.error?.message || error?.message;

        if (message?.includes('expired')) {
          setErrorMessage(
            t('token_expired') ||
              'This link has expired. Magic links are valid for 15 minutes. Please request a new one.'
          );
        } else if (message?.includes('already used') || message?.includes('used')) {
          setErrorMessage(
            t('token_already_used') ||
              'This link has already been used. Please log in or request a new magic link.'
          );
        } else if (message?.includes('invalid') || message?.includes('not found')) {
          setErrorMessage(
            t('token_invalid') ||
              'This verification link is invalid. Please check your email or request a new link.'
          );
        } else {
          setErrorMessage(
            t('verification_failed') ||
              'Verification failed. Please try again or contact support if the problem persists.'
          );
        }
      }
    };

    verifyToken();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [token]);

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
      <div className="max-w-md w-full bg-white rounded-lg shadow-md p-8">
        {/* Verifying State */}
        {verificationStatus === 'verifying' && (
          <div className="text-center">
            <div className="flex justify-center mb-6">
              <div className="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center">
                <Loader2 className="w-10 h-10 text-primary animate-spin" />
              </div>
            </div>
            <h1 className="text-2xl font-bold text-gray-900 mb-2">
              {t('verifying') || 'Verifying your link...'}
            </h1>
            <p className="text-gray-600">
              {t('please_wait') || 'Please wait while we verify your identity'}
            </p>
          </div>
        )}

        {/* Success State */}
        {verificationStatus === 'success' && (
          <div className="text-center">
            <div className="flex justify-center mb-6">
              <div className="w-16 h-16 bg-success-light rounded-full flex items-center justify-center">
                <Check className="w-10 h-10 text-success" />
              </div>
            </div>
            <h1 className="text-2xl font-bold text-gray-900 mb-2">
              {t('login_successful') || 'Login successful!'}
            </h1>
            <p className="text-gray-600 mb-6">
              {t('redirecting') || 'Redirecting to your dashboard...'}
            </p>

            {/* Progress Indicator */}
            <div className="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
              <div
                className="bg-primary h-full transition-all duration-[2000ms] ease-linear"
                style={{ width: '100%' }}
              />
            </div>

            <p className="text-sm text-gray-500 mt-4">
              If you&apos;re not redirected,{' '}
              <Link href="/dashboard" className="text-primary hover:underline">
                click here
              </Link>
              .
            </p>
          </div>
        )}

        {/* Error State */}
        {verificationStatus === 'error' && (
          <div className="text-center">
            <div className="flex justify-center mb-6">
              <div className="w-16 h-16 bg-error-light rounded-full flex items-center justify-center">
                <XCircle className="w-10 h-10 text-error" />
              </div>
            </div>
            <h1 className="text-2xl font-bold text-gray-900 mb-2">
              {t('verification_failed_title') || 'Verification failed'}
            </h1>
            <div className="bg-error-light border border-error/20 rounded-lg p-4 mb-6">
              <p className="text-sm text-error-dark">{errorMessage}</p>
            </div>

            {/* Action Buttons */}
            <div className="space-y-3">
              {errorMessage?.includes('expired') ||
              errorMessage?.includes('invalid') ||
              errorMessage?.includes('already used') ? (
                <Link
                  href="/auth/passwordless"
                  className="block w-full px-4 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors"
                >
                  {t('request_new_link') || 'Request a new link'}
                </Link>
              ) : null}

              {errorMessage?.includes('already used') ? (
                <Link
                  href="/auth/login"
                  className="block w-full px-5 py-2.5 border-2 border-gray-300 rounded-full font-medium text-gray-700 hover:bg-gray-50 transition-colors text-center"
                >
                  {t('login') || 'Go to login'}
                </Link>
              ) : null}

              <Link
                href="/"
                className="block w-full px-4 py-3 text-sm text-center text-gray-600 hover:text-gray-900 transition-colors"
              >
                {t('back_home') || 'Back to home'}
              </Link>
            </div>

            {/* Help Text */}
            <p className="text-xs text-gray-500 mt-6">
              Still having trouble?{' '}
              <a href="mailto:support@goadventure.com" className="text-primary hover:underline">
                Contact support
              </a>
            </p>
          </div>
        )}
      </div>
    </div>
  );
}
