'use client';

/* eslint-disable @typescript-eslint/no-explicit-any -- next-intl Link requires typed routes */
import { useState, useEffect } from 'react';
import { useTranslations } from 'next-intl';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/lib/contexts/AuthContext';
import { useCartContext } from '@/lib/contexts/CartContext';
import { useInitiateCheckout, useProcessCartPayment, useUpdateCartItem } from '@/lib/api/hooks';
import { CheckoutAuthModal } from '@/components/booking/CheckoutAuthModal';
import { CurrencyNoticeModal } from '@/components/booking/CurrencyNoticeModal';
import { PrimaryContactForm, type PrimaryContactData } from './PrimaryContactForm';
import { CartPaymentStep } from './CartPaymentStep';
import { CartConfirmation } from './CartConfirmation';
import type { PaymentMethod } from '@/components/booking/PaymentMethodSelector';
import type { Booking } from '@djerba-fun/schemas';
import { Clock, ShoppingCart, AlertCircle } from 'lucide-react';
import { Button } from '@djerba-fun/ui';
import Link from 'next/link';

type Step = 'contact' | 'payment' | 'confirmation';

interface CartCheckoutWizardProps {
  locale: string;
}

export function CartCheckoutWizard({ locale }: CartCheckoutWizardProps) {
  const router = useRouter();
  const t = useTranslations('cart.checkout');
  const tCart = useTranslations('cart');
  const { user, isAuthenticated, isLoading: isAuthLoading } = useAuth();
  const { cart, isLoading: isCartLoading, extendHolds } = useCartContext();

  const [currentStep, setCurrentStep] = useState<Step>('contact');
  const [primaryContact, setPrimaryContact] = useState<PrimaryContactData | null>(null);
  const [paymentId, setPaymentId] = useState<string | null>(null);
  const [completedBookings, setCompletedBookings] = useState<Booking[]>([]);
  const [error, setError] = useState<string | null>(null);

  // Store cart info before payment completes (cart becomes empty after payment)
  const [completedCartInfo, setCompletedCartInfo] = useState<{
    totalAmount: number;
    currency: string;
  } | null>(null);

  // Consent state (for policy agreement checkbox)
  const [termsAccepted, setTermsAccepted] = useState(false);
  const [marketingAccepted, setMarketingAccepted] = useState(false);
  const [termsError, setTermsError] = useState<string | undefined>();
  const [highlightConsents, setHighlightConsents] = useState(false);

  // Auth modal state
  const [showAuthModal, setShowAuthModal] = useState(false);
  const [hasChosenAuthMethod, setHasChosenAuthMethod] = useState(false);

  // Currency notice modal state (for ClikToPay redirect)
  const [showCurrencyNotice, setShowCurrencyNotice] = useState(false);
  const [pendingPaymentAmount, setPendingPaymentAmount] = useState(0);
  const [pendingTndAmount, setPendingTndAmount] = useState(0);
  const [pendingPaymentCurrency, setPendingPaymentCurrency] = useState('EUR');

  const initiateCheckout = useInitiateCheckout();
  const processPayment = useProcessCartPayment();
  const updateCartItem = useUpdateCartItem();

  // Calculate time left
  const [timeLeft, setTimeLeft] = useState(cart?.expiresInSeconds || 0);

  useEffect(() => {
    if (cart?.expiresInSeconds) {
      setTimeLeft(cart.expiresInSeconds);
    }
  }, [cart?.expiresInSeconds]);

  useEffect(() => {
    const interval = setInterval(() => {
      setTimeLeft((prev) => Math.max(0, prev - 1));
    }, 1000);
    return () => clearInterval(interval);
  }, []);

  const minutes = Math.floor(timeLeft / 60);
  const seconds = timeLeft % 60;

  // Show auth modal if user is not logged in and hasn't chosen a method yet
  useEffect(() => {
    if (!isAuthLoading && !isAuthenticated && !hasChosenAuthMethod && cart && !cart.isExpired) {
      setShowAuthModal(true);
    }
  }, [isAuthLoading, isAuthenticated, hasChosenAuthMethod, cart]);

  // Pre-fill contact from authenticated user
  useEffect(() => {
    if (isAuthenticated && user && !primaryContact) {
      const profile = (
        user as { travelerProfile?: { firstName?: string; lastName?: string; phone?: string } }
      ).travelerProfile;

      setPrimaryContact({
        firstName: profile?.firstName || '',
        lastName: profile?.lastName || '',
        email: user.email || '',
        phone: profile?.phone || '',
        specialRequests: '',
      });
    }
  }, [isAuthenticated, user, primaryContact]);

  // Auth modal handlers
  const handleGuestCheckout = () => {
    setHasChosenAuthMethod(true);
    setShowAuthModal(false);
  };

  const handleEmailLogin = () => {
    // Redirect to login page with return URL
    router.push(`/${locale}/auth/login?returnUrl=/${locale}/cart/checkout`);
  };

  const handleCreateAccount = () => {
    // Redirect to register page with return URL
    router.push(`/${locale}/auth/register?returnUrl=/${locale}/cart/checkout`);
  };

  const handleContactSubmit = async (data: PrimaryContactData) => {
    setPrimaryContact(data);
    setError(null);

    // Update all cart items with primary contact info
    if (cart) {
      try {
        await Promise.all(
          cart.items.map((item) =>
            updateCartItem.mutateAsync({
              itemId: item.id,
              data: {
                primaryContact: {
                  first_name: data.firstName,
                  last_name: data.lastName,
                  email: data.email,
                  phone: data.phone,
                },
              },
            })
          )
        );
        setCurrentStep('payment');
      } catch (err) {
        console.error('Failed to update cart items:', err);
        setError(t('error_updating_contact'));
      }
    }
  };

  const processPaymentAndRedirect = async (paymentIdToProcess: string) => {
    try {
      const paymentResponse = await processPayment.mutateAsync({
        paymentId: paymentIdToProcess,
        paymentData: {},
      });

      // Handle redirect-based payments (Clictopay)
      if (paymentResponse.requires_redirect && paymentResponse.redirect_url) {
        window.location.href = paymentResponse.redirect_url;
        return;
      }

      // Handle direct payments (mock, offline)
      if (paymentResponse.success && paymentResponse.bookings) {
        if (cart) {
          setCompletedCartInfo({
            totalAmount: cart.subtotal,
            currency: cart.currency,
          });
        }
        setCompletedBookings(paymentResponse.bookings);
        setCurrentStep('confirmation');
      } else {
        setError(t('payment_failed'));
      }
    } catch (err) {
      console.error('Payment processing error:', err);
      const errorMessage = err instanceof Error ? err.message : t('payment_failed');
      setError(errorMessage);
    }
  };

  const handlePaymentSubmit = async (paymentMethod: PaymentMethod) => {
    setError(null);

    // For ClikToPay, show confirmation modal BEFORE initiating checkout
    // This prevents cart from becoming null (initiateCheckout changes status to checking_out)
    if (paymentMethod === 'click_to_pay' && cart) {
      setPendingPaymentAmount(cart.subtotal);
      setPendingPaymentCurrency(cart.currency);
      setPendingTndAmount(cart.tndSubtotal ?? cart.subtotal);
      setShowCurrencyNotice(true);
      return; // Wait for user to confirm in modal
    }

    // For other payment methods, proceed directly
    try {
      const checkoutResponse = await initiateCheckout.mutateAsync(paymentMethod);
      const newPaymentId = checkoutResponse.payment_id;
      setPaymentId(newPaymentId);
      await processPaymentAndRedirect(newPaymentId);
    } catch (err) {
      console.error('Checkout error:', err);
      const errorMessage = err instanceof Error ? err.message : t('payment_failed');
      setError(errorMessage);
    }
  };

  const handleCurrencyNoticeConfirm = async () => {
    setShowCurrencyNotice(false);
    setError(null);

    // Store cart info before initiateCheckout (cart becomes null after status changes)
    if (cart) {
      setCompletedCartInfo({
        totalAmount: cart.subtotal,
        currency: cart.currency,
      });
    }

    try {
      // NOW initiate checkout (after user confirmed in modal)
      const checkoutResponse = await initiateCheckout.mutateAsync('click_to_pay');
      const newPaymentId = checkoutResponse.payment_id;
      setPaymentId(newPaymentId);
      await processPaymentAndRedirect(newPaymentId);
    } catch (err) {
      console.error('Checkout error:', err);
      const errorMessage = err instanceof Error ? err.message : t('payment_failed');
      setError(errorMessage);
    }
  };

  const handleCurrencyNoticeCancel = () => {
    setShowCurrencyNotice(false);
  };

  const handleExtendTime = async () => {
    try {
      await extendHolds();
    } catch (err) {
      console.error('Failed to extend holds:', err);
    }
  };

  // Loading state
  if (isCartLoading || isAuthLoading) {
    return (
      <div className="max-w-2xl mx-auto py-12">
        <div className="animate-pulse space-y-6">
          <div className="h-8 bg-gray-200 rounded w-48"></div>
          <div className="h-64 bg-gray-200 rounded-lg"></div>
        </div>
      </div>
    );
  }

  // IMPORTANT: Check confirmation step FIRST
  // After successful payment, cart becomes empty but we still need to show confirmation
  if (currentStep === 'confirmation' && completedBookings.length > 0 && completedCartInfo) {
    return (
      <CartConfirmation
        bookings={completedBookings}
        totalAmount={completedCartInfo.totalAmount}
        currency={completedCartInfo.currency}
        locale={locale}
        primaryEmail={primaryContact?.email}
      />
    );
  }

  // Empty cart or expired (only check if NOT on confirmation step)
  if (!cart || cart.itemCount === 0) {
    return (
      <div className="max-w-2xl mx-auto py-12">
        <div className="text-center">
          <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
            <ShoppingCart className="w-8 h-8 text-gray-400" />
          </div>
          <h2 className="text-xl font-bold text-gray-900 mb-2">{tCart('empty_title')}</h2>
          <p className="text-gray-600 mb-6">{t('cart_empty_checkout')}</p>
          <Link href={`/${locale}/listings` as any}>
            <Button>{tCart('browse_experiences')}</Button>
          </Link>
        </div>
      </div>
    );
  }

  // Cart expired
  if (cart.isExpired || timeLeft === 0) {
    return (
      <div className="max-w-2xl mx-auto py-12">
        <div className="text-center">
          <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-error-light flex items-center justify-center">
            <AlertCircle className="w-8 h-8 text-error" />
          </div>
          <h2 className="text-xl font-bold text-gray-900 mb-2">{t('reservation_expired')}</h2>
          <p className="text-gray-600 mb-6">{t('reservation_expired_message')}</p>
          <Link href={`/${locale}/cart` as any}>
            <Button>{t('return_to_cart')}</Button>
          </Link>
        </div>
      </div>
    );
  }

  // Step indicator (only visible for contact and payment steps)
  const steps = [
    { key: 'contact', label: t('step_contact') },
    { key: 'payment', label: t('step_payment') },
  ];
  const currentStepIndex = steps.findIndex((s) => s.key === currentStep);

  const renderStepContent = () => {
    switch (currentStep) {
      case 'contact':
        return (
          <PrimaryContactForm
            onSubmit={handleContactSubmit}
            defaultValues={primaryContact || undefined}
            isLoading={updateCartItem.isPending}
          />
        );

      case 'payment':
        if (!primaryContact) {
          setCurrentStep('contact');
          return null;
        }
        return (
          <CartPaymentStep
            cart={cart}
            primaryContact={primaryContact}
            locale={locale}
            onBack={() => setCurrentStep('contact')}
            onSubmit={handlePaymentSubmit}
            isProcessing={initiateCheckout.isPending || processPayment.isPending}
            termsAccepted={termsAccepted}
            onTermsChange={setTermsAccepted}
            marketingAccepted={marketingAccepted}
            onMarketingChange={setMarketingAccepted}
            termsError={termsError}
            setTermsError={setTermsError}
            highlightConsents={highlightConsents}
            setHighlightConsents={setHighlightConsents}
          />
        );

      case 'confirmation':
        return (
          <CartConfirmation
            bookings={completedBookings}
            totalAmount={cart.subtotal}
            currency={cart.currency}
            locale={locale}
            primaryEmail={primaryContact?.email}
          />
        );

      default:
        return null;
    }
  };

  // Confirmation step has its own layout
  if (currentStep === 'confirmation') {
    return <div>{renderStepContent()}</div>;
  }

  return (
    <div className="space-y-6">
      {/* Timer */}
      <div className="flex items-center justify-between bg-white rounded-lg border border-gray-200 p-4">
        <div className="flex items-center gap-2 text-gray-600">
          <Clock className="w-5 h-5" />
          <span className="text-sm">{tCart('reservation_expires')}</span>
        </div>
        <div className="flex items-center gap-3">
          <span
            className={`font-mono font-bold text-lg ${
              timeLeft < 120 ? 'text-error' : 'text-gray-900'
            }`}
          >
            {String(minutes).padStart(2, '0')}:{String(seconds).padStart(2, '0')}
          </span>
          {timeLeft < 300 && (
            <Button variant="ghost" size="sm" onClick={handleExtendTime}>
              {tCart('extend')}
            </Button>
          )}
        </div>
      </div>

      {/* Progress Indicator */}
      <div className="bg-white rounded-lg border border-gray-200 p-6">
        <div className="flex items-center justify-center">
          {steps.map((step, index) => {
            const isActive = currentStep === step.key;
            const isCompleted = index < currentStepIndex;

            return (
              <div key={step.key} className="flex items-center">
                <div className="flex flex-col items-center">
                  <div
                    className={`w-10 h-10 rounded-full flex items-center justify-center font-semibold ${
                      isActive
                        ? 'bg-primary text-white'
                        : isCompleted
                          ? 'bg-success text-white'
                          : 'bg-gray-200 text-gray-600'
                    }`}
                  >
                    {isCompleted ? (
                      <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path
                          fillRule="evenodd"
                          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                          clipRule="evenodd"
                        />
                      </svg>
                    ) : (
                      index + 1
                    )}
                  </div>
                  <span
                    className={`mt-2 text-sm font-medium ${
                      isActive ? 'text-primary' : isCompleted ? 'text-success' : 'text-gray-500'
                    }`}
                  >
                    {step.label}
                  </span>
                </div>
                {index < steps.length - 1 && (
                  <div className={`w-20 h-1 mx-4 ${isCompleted ? 'bg-success' : 'bg-gray-200'}`} />
                )}
              </div>
            );
          })}
        </div>
      </div>

      {/* Step Content */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        {renderStepContent()}
      </div>

      {/* Error Display */}
      {error && (
        <div className="mt-4 p-4 bg-error-light border border-error/20 rounded-lg">
          <p className="text-sm text-error-dark">{error}</p>
        </div>
      )}

      {/* Auth Modal */}
      <CheckoutAuthModal
        isOpen={showAuthModal}
        onClose={() => setShowAuthModal(false)}
        onGuestCheckout={handleGuestCheckout}
        onEmailLogin={handleEmailLogin}
        onCreateAccount={handleCreateAccount}
      />

      {/* Currency Notice Modal - shown before ClikToPay redirect */}
      <CurrencyNoticeModal
        isOpen={showCurrencyNotice}
        onConfirm={handleCurrencyNoticeConfirm}
        onCancel={handleCurrencyNoticeCancel}
        amount={pendingPaymentAmount}
        currency={pendingPaymentCurrency}
        tndAmount={pendingTndAmount}
      />
    </div>
  );
}
