'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { Button, Dialog } from '@djerba-fun/ui';
import { Mail, LogIn, UserPlus } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

interface CheckoutAuthModalProps {
  isOpen: boolean;
  onClose: () => void;
  onGuestCheckout: () => void;
  onEmailLogin: () => void;
  onCreateAccount: () => void;
}

export function CheckoutAuthModal({
  isOpen,
  onClose,
  onGuestCheckout,
  onEmailLogin,
  onCreateAccount,
}: CheckoutAuthModalProps) {
  const t = useTranslations('booking');
  const tAuth = useTranslations('auth');

  return (
    <AnimatePresence>
      {isOpen && (
        <>
          {/* Backdrop with blur */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.2 }}
            className="fixed inset-0 bg-black/40 backdrop-blur-sm z-50"
            onClick={onClose}
          />

          {/* Modal */}
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 20 }}
              transition={{ duration: 0.2, ease: 'easeOut' }}
              className="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 relative"
              onClick={(e) => e.stopPropagation()}
            >
              {/* Close button */}
              <button
                onClick={onClose}
                className="absolute top-4 right-4 p-2 rounded-lg hover:bg-neutral-100 transition-colors"
                aria-label="Close"
              >
                <svg
                  className="w-5 h-5 text-neutral-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>

              {/* Header */}
              <div className="mb-6 text-center">
                <h3 className="text-2xl font-bold text-neutral-900 mb-2">
                  {t('complete_booking_title') || 'Complete Your Booking'}
                </h3>
                <p className="text-sm text-neutral-600">
                  {t('complete_booking_subtitle') || "Choose how you'd like to continue"}
                </p>
              </div>

              {/* Primary CTA - Guest Checkout */}
              <Button
                variant="primary"
                size="lg"
                className="w-full mb-3"
                onClick={onGuestCheckout}
                data-testid="guest-checkout-button"
              >
                <UserPlus className="w-5 h-5 mr-2" />
                {t('continue_as_guest') || 'Continue as Guest'}
              </Button>

              {/* Divider */}
              <div className="relative my-6">
                <div className="absolute inset-0 flex items-center">
                  <div className="w-full border-t border-neutral-300" />
                </div>
                <div className="relative flex justify-center text-xs uppercase">
                  <span className="bg-white px-2 text-neutral-500">
                    {tAuth('or_sign_in') || 'or sign in'}
                  </span>
                </div>
              </div>

              {/* Secondary Actions */}
              <div className="space-y-2">
                <Button
                  variant="outline"
                  size="lg"
                  className="w-full"
                  onClick={onEmailLogin}
                  data-testid="email-login-button"
                >
                  <LogIn className="w-5 h-5 mr-2" />
                  {tAuth('sign_in_email') || 'Sign in with Email'}
                </Button>

                <Button
                  variant="outline"
                  size="lg"
                  className="w-full"
                  onClick={onCreateAccount}
                  data-testid="create-account-button"
                >
                  <Mail className="w-5 h-5 mr-2" />
                  {tAuth('create_account') || 'Create Account'}
                </Button>
              </div>

              {/* Benefits of signing in */}
              <div className="mt-6 p-4 bg-success-light rounded-lg border border-success/20">
                <p className="text-xs text-success-dark">
                  <strong>{t('sign_in_benefits_title') || 'Benefits of signing in:'}:</strong>{' '}
                  {t('sign_in_benefits') || 'Save your bookings, faster checkout, exclusive offers'}
                </p>
              </div>
            </motion.div>
          </div>
        </>
      )}
    </AnimatePresence>
  );
}
