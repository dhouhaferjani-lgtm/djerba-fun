'use client';

import { useState, useEffect } from 'react';
import { useTranslations } from 'next-intl';
import { useAuth } from '@/lib/contexts/AuthContext';
import { Button } from '@go-adventure/ui';
import { User, Mail, Lock, LogIn } from 'lucide-react';

interface CheckoutAuthProps {
  onContinueAsGuest: () => void;
  onLoginSuccess: () => void;
}

export function CheckoutAuth({ onContinueAsGuest, onLoginSuccess }: CheckoutAuthProps) {
  const t = useTranslations('booking');
  const tAuth = useTranslations('auth');
  const { login, isAuthenticated } = useAuth();
  const [showLoginForm, setShowLoginForm] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  // If already authenticated, skip to next step (use effect to avoid setState during render)
  useEffect(() => {
    if (isAuthenticated) {
      onLoginSuccess();
    }
  }, [isAuthenticated, onLoginSuccess]);

  // Show nothing while redirecting authenticated users
  if (isAuthenticated) {
    return null;
  }

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setIsLoading(true);

    try {
      await login(email, password);
      onLoginSuccess();
    } catch (err) {
      setError(t('login_error') || 'Invalid email or password');
    } finally {
      setIsLoading(false);
    }
  };

  if (showLoginForm) {
    return (
      <div className="space-y-6">
        <div className="text-center">
          <h3 className="text-lg font-semibold text-neutral-900">
            {t('login_to_continue') || 'Log in to your account'}
          </h3>
          <p className="text-sm text-neutral-500 mt-1">
            {t('login_benefits') || 'Track your bookings and get personalized recommendations'}
          </p>
        </div>

        <form onSubmit={handleLogin} className="space-y-4">
          <div>
            <label htmlFor="email" className="block text-sm font-medium text-neutral-700 mb-1">
              {tAuth('email')}
            </label>
            <div className="relative">
              <Mail className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-neutral-400" />
              <input
                id="email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                placeholder="your@email.com"
                required
              />
            </div>
          </div>

          <div>
            <label htmlFor="password" className="block text-sm font-medium text-neutral-700 mb-1">
              {tAuth('password')}
            </label>
            <div className="relative">
              <Lock className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-neutral-400" />
              <input
                id="password"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                placeholder="********"
                required
              />
            </div>
          </div>

          {error && <p className="text-sm text-red-600 bg-red-50 p-3 rounded-lg">{error}</p>}

          <Button
            type="submit"
            variant="primary"
            size="lg"
            className="w-full"
            isLoading={isLoading}
          >
            <LogIn className="h-5 w-5 mr-2" />
            {tAuth('login')}
          </Button>
        </form>

        <div className="relative">
          <div className="absolute inset-0 flex items-center">
            <div className="w-full border-t border-neutral-200" />
          </div>
          <div className="relative flex justify-center text-sm">
            <span className="px-2 bg-white text-neutral-500">or</span>
          </div>
        </div>

        <button
          onClick={() => setShowLoginForm(false)}
          className="w-full text-center text-sm text-primary hover:underline"
        >
          {t('back_to_options') || 'Back to options'}
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="text-center">
        <h3 className="text-lg font-semibold text-neutral-900">
          {t('how_to_continue') || 'How would you like to continue?'}
        </h3>
        <p className="text-sm text-neutral-500 mt-1">
          {t('checkout_options_desc') || 'Log in for a faster checkout or continue as a guest'}
        </p>
      </div>

      <div className="space-y-3">
        <Button
          variant="primary"
          size="lg"
          className="w-full"
          onClick={() => setShowLoginForm(true)}
        >
          <LogIn className="h-5 w-5 mr-2" />
          {t('login_existing_account') || 'Log in to my account'}
        </Button>

        <Button variant="outline" size="lg" className="w-full" onClick={onContinueAsGuest}>
          <User className="h-5 w-5 mr-2" />
          {t('continue_as_guest') || 'Continue as guest'}
        </Button>
      </div>

      <p className="text-xs text-neutral-500 text-center">
        {t('guest_checkout_note') ||
          "You'll be able to create an account after completing your booking"}
      </p>
    </div>
  );
}
