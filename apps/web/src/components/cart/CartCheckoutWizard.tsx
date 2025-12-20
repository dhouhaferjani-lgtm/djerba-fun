'use client';

/* eslint-disable @typescript-eslint/no-explicit-any -- next-intl Link requires typed routes */
import { useState, useEffect } from 'react';
import { useTranslations } from 'next-intl';
import { useAuth } from '@/lib/contexts/AuthContext';
import { useCartContext } from '@/lib/contexts/CartContext';
import { useInitiateCheckout, useProcessCartPayment, useUpdateCartItem } from '@/lib/api/hooks';
import { CheckoutAuth } from '@/components/booking/CheckoutAuth';
import { PrimaryContactForm, type PrimaryContactData } from './PrimaryContactForm';
import { CartPaymentStep } from './CartPaymentStep';
import { CartConfirmation } from './CartConfirmation';
import type { PaymentMethod } from '@/components/booking/PaymentMethodSelector';
import type { Booking } from '@go-adventure/schemas';
import { Clock, ShoppingCart, AlertCircle } from 'lucide-react';
import { Button } from '@go-adventure/ui';
import Link from 'next/link';

type Step = 'auth' | 'contact' | 'payment' | 'confirmation';

interface CartCheckoutWizardProps {
  locale: string;
}

export function CartCheckoutWizard({ locale }: CartCheckoutWizardProps) {
  const t = useTranslations('cart.checkout');
  const tCart = useTranslations('cart');
  const { user, isAuthenticated, isLoading: isAuthLoading } = useAuth();
  const { cart, isLoading: isCartLoading, extendHolds } = useCartContext();

  const [currentStep, setCurrentStep] = useState<Step>('auth');
  const [primaryContact, setPrimaryContact] = useState<PrimaryContactData | null>(null);
  const [paymentId, setPaymentId] = useState<string | null>(null);
  const [completedBookings, setCompletedBookings] = useState<Booking[]>([]);
  const [error, setError] = useState<string | null>(null);

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

  // Skip auth step if already authenticated
  useEffect(() => {
    if (!isAuthLoading && isAuthenticated && currentStep === 'auth') {
      setCurrentStep('contact');
    }
  }, [isAuthenticated, isAuthLoading, currentStep]);

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

  const handleAuthComplete = () => {
    setCurrentStep('contact');
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

  const handlePaymentSubmit = async (paymentMethod: PaymentMethod) => {
    setError(null);

    try {
      // Initiate checkout
      const checkoutResponse = await initiateCheckout.mutateAsync(paymentMethod);
      const newPaymentId = checkoutResponse.payment_id;
      setPaymentId(newPaymentId);

      // Process payment
      const paymentResponse = await processPayment.mutateAsync({
        paymentId: newPaymentId,
        paymentData: {},
      });

      if (paymentResponse.success && paymentResponse.bookings) {
        setCompletedBookings(paymentResponse.bookings);
        setCurrentStep('confirmation');
      } else {
        setError(t('payment_failed'));
      }
    } catch (err) {
      console.error('Checkout error:', err);
      const errorMessage = err instanceof Error ? err.message : t('payment_failed');
      setError(errorMessage);
    }
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

  // Empty cart or expired
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
          <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center">
            <AlertCircle className="w-8 h-8 text-red-500" />
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
      case 'auth':
        return (
          <CheckoutAuth
            onContinueAsGuest={handleAuthComplete}
            onLoginSuccess={handleAuthComplete}
          />
        );

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
    return <div className="py-8">{renderStepContent()}</div>;
  }

  return (
    <div className="max-w-2xl mx-auto">
      {/* Timer */}
      <div className="mb-6 flex items-center justify-between bg-white rounded-lg border border-gray-200 p-4">
        <div className="flex items-center gap-2 text-gray-600">
          <Clock className="w-5 h-5" />
          <span className="text-sm">{tCart('reservation_expires')}</span>
        </div>
        <div className="flex items-center gap-3">
          <span
            className={`font-mono font-bold text-lg ${
              timeLeft < 120 ? 'text-red-600' : 'text-gray-900'
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

      {/* Progress Indicator (not for auth step) */}
      {currentStep !== 'auth' && (
        <div className="mb-8">
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
                            ? 'bg-green-500 text-white'
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
                        isActive ? 'text-primary' : isCompleted ? 'text-green-600' : 'text-gray-500'
                      }`}
                    >
                      {step.label}
                    </span>
                  </div>
                  {index < steps.length - 1 && (
                    <div
                      className={`w-20 h-1 mx-4 ${isCompleted ? 'bg-green-500' : 'bg-gray-200'}`}
                    />
                  )}
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Step Content */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        {renderStepContent()}
      </div>

      {/* Error Display */}
      {error && (
        <div className="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
          <p className="text-sm text-red-800">{error}</p>
        </div>
      )}
    </div>
  );
}
