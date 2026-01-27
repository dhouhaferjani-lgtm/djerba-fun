'use client';

import { useState, useRef } from 'react';
import { useTranslations } from 'next-intl';
import { ExtrasSelection } from './ExtrasSelection';
import { BookingReview } from './BookingReview';
import { PaymentMethodSelector, type PaymentMethod } from './PaymentMethodSelector';
import { BookingConfirmation } from './BookingConfirmation';
import { CheckoutAuth } from './CheckoutAuth';
import { BillingAddressStep, type BillingAddress } from './BillingAddressStep';
import {
  PricingDisclosureModal,
  type PriceChangeInfo,
} from '@/components/pricing/PricingDisclosureModal';
import CheckoutConsents from '@/components/consent/CheckoutConsents';
import HoldTimer from '@/components/availability/HoldTimer';
import { useCreateBooking, useProcessPayment } from '@/lib/api/hooks';
import { getGuestSessionId } from '@/lib/utils/session';
import { useAuth } from '@/lib/contexts/AuthContext';
import { consentApi } from '@/lib/api/client';
import type {
  BookingHold,
  Booking,
  ListingSummary,
  AvailabilitySlot,
  ListingExtraForBooking,
} from '@go-adventure/schemas';

interface SelectedExtra {
  id: string; // listing_extra_id
  quantity: number;
}

interface BookingWizardProps {
  hold: BookingHold;
  listing: ListingSummary;
  slot: AvailabilitySlot;
  availableExtras?: ListingExtraForBooking[];
  onExpired?: () => void;
  userIpCountry?: string; // From IP geolocation
  userIpCountryName?: string;
}

type Step = 'email' | 'extras' | 'billing' | 'review' | 'confirmation';

export function BookingWizard({
  hold,
  listing,
  slot,
  availableExtras = [],
  onExpired,
  userIpCountry,
  userIpCountryName,
}: BookingWizardProps) {
  const t = useTranslations('booking');
  const { user } = useAuth();

  // Simplified: Start with email collection
  const [currentStep, setCurrentStep] = useState<Step>('email');
  const [email, setEmail] = useState<string | null>(null);
  const [selectedExtras, setSelectedExtras] = useState<SelectedExtra[]>([]);
  const [billingAddress, setBillingAddress] = useState<BillingAddress | null>(null);
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod | undefined>();
  const [completedBooking, setCompletedBooking] = useState<Booking | null>(null);

  // Pricing state
  const [currentCurrency, setCurrentCurrency] = useState(slot.currency);
  const [priceDisclosureShown, setPriceDisclosureShown] = useState(false);
  const [showDisclosureModal, setShowDisclosureModal] = useState(false);
  const [priceChangeInfo, setPriceChangeInfo] = useState<PriceChangeInfo | null>(null);

  // Consent state
  const [termsAccepted, setTermsAccepted] = useState(false);
  const [marketingAccepted, setMarketingAccepted] = useState(false);
  const [termsError, setTermsError] = useState<string | undefined>();
  const [highlightConsents, setHighlightConsents] = useState(false);
  const consentsRef = useRef<HTMLDivElement>(null);

  const createBookingMutation = useCreateBooking();
  const processPaymentMutation = useProcessPayment();

  // Progress steps (email is not shown in progress, it's the entry point)
  const steps: { key: Step; label: string }[] = [
    { key: 'extras', label: t('step_extras') },
    { key: 'billing', label: t('step_billing') || 'Billing' },
    { key: 'review', label: t('step_review') },
  ];

  const currentStepIndex = steps.findIndex((s) => s.key === currentStep);

  // Handle email submission
  const handleEmailSubmit = (submittedEmail: string) => {
    setEmail(submittedEmail);
    setCurrentStep('extras');
  };

  const handleExtrasSubmit = (extras: SelectedExtra[]) => {
    setSelectedExtras(extras);
    setCurrentStep('billing');
  };

  const handleBillingSubmit = (address: BillingAddress) => {
    setBillingAddress(address);

    // Check if billing country matches IP country
    const billingCountry = address.country_code;
    const ipCountry = userIpCountry || 'TN'; // Default to Tunisia for testing

    if (billingCountry !== ipCountry && !priceDisclosureShown) {
      // Price mismatch detected - show disclosure modal
      // In a real implementation, this would trigger an API call to get new pricing
      const newCurrency = getCurrencyForCountry(billingCountry);
      const newPrice = calculatePriceForCurrency(newCurrency);
      const oldPrice = calculatePriceForCurrency(currentCurrency);

      setPriceChangeInfo({
        oldPrice,
        newPrice,
        oldCurrency: currentCurrency,
        newCurrency,
        billingCountry,
        billingCountryName: getCountryName(billingCountry),
      });
      setShowDisclosureModal(true);
    } else {
      // No mismatch or already shown - proceed
      setCurrentStep('review');
    }
  };

  const handleDisclosureAccept = () => {
    if (priceChangeInfo) {
      setCurrentCurrency(priceChangeInfo.newCurrency);
      setPriceDisclosureShown(true);
      setShowDisclosureModal(false);
      setCurrentStep('review');
    }
  };

  const handleDisclosureCancel = () => {
    setShowDisclosureModal(false);
    // Stay on billing step to allow user to change address
  };

  const handleConfirmBooking = async () => {
    if (!email || !billingAddress) return;

    // Validate terms acceptance
    if (!termsAccepted) {
      setTermsError(t('terms_required') || 'You must accept the terms and conditions to continue.');

      // Scroll to consents section and highlight it for visibility (especially for older users)
      if (consentsRef.current) {
        consentsRef.current.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setHighlightConsents(true);
        // Remove highlight after 3 seconds
        setTimeout(() => setHighlightConsents(false), 3000);
      }
      return;
    }
    setTermsError(undefined);
    setHighlightConsents(false);

    try {
      // Get session ID for guest checkout (from hold or local storage)
      const sessionId = hold.sessionId || getGuestSessionId();

      // Record consents before creating booking
      try {
        await consentApi.recordConsents(
          {
            terms: true,
            privacy: true,
            marketing: marketingAccepted,
          },
          {
            sessionId,
            email: email,
            context: 'checkout',
          }
        );
      } catch (consentError) {
        console.error('Failed to record consents:', consentError);
        // Continue with booking even if consent recording fails
      }

      const bookingResponse = await createBookingMutation.mutateAsync({
        holdId: hold.id,
        travelers: [
          {
            email: email || '',
            firstName: 'Guest',
            lastName: 'Traveler',
          },
        ],
        sessionId,
      });

      const booking = bookingResponse.data;

      // If payment method is selected, process payment
      if (paymentMethod) {
        const paymentResponse = await processPaymentMutation.mutateAsync({
          bookingId: booking.id,
          request: {
            paymentMethod,
            paymentData: {},
            sessionId,
          },
        });
        setCompletedBooking(paymentResponse.data);
      } else {
        setCompletedBooking(booking);
      }

      setCurrentStep('confirmation');
    } catch (error) {
      console.error('Booking error:', error);
      // Error handling is done by the mutation hooks
    }
  };

  const getExtrasWithDetails = () => {
    return selectedExtras
      .map((selected) => {
        const extra = availableExtras.find((e) => e.id === selected.id);
        if (!extra) return null;
        const price =
          extra.displayPrice ?? (currentCurrency === 'TND' ? extra.priceTnd : extra.priceEur);
        return {
          id: extra.id,
          name: extra.name,
          quantity: selected.quantity,
          price,
        };
      })
      .filter((e): e is NonNullable<typeof e> => e !== null);
  };

  // Helper functions for currency/pricing (would be API calls in real implementation)
  const getCurrencyForCountry = (countryCode: string): string => {
    const currencyMap: Record<string, string> = {
      TN: 'TND',
      FR: 'EUR',
      DE: 'EUR',
      ES: 'EUR',
      IT: 'EUR',
      US: 'USD',
      GB: 'GBP',
    };
    return currencyMap[countryCode] || 'EUR';
  };

  const getCountryName = (countryCode: string): string => {
    const countryNames: Record<string, string> = {
      TN: 'Tunisia',
      FR: 'France',
      DE: 'Germany',
      ES: 'Spain',
      IT: 'Italy',
      US: 'United States',
      GB: 'United Kingdom',
    };
    return countryNames[countryCode] || countryCode;
  };

  const calculatePriceForCurrency = (currency: string): number => {
    // This would be an API call in real implementation
    // For now, use slot pricing
    if (currency === 'TND') {
      return slot.tndPrice || slot.displayPrice || 100;
    }
    return slot.eurPrice || slot.displayPrice || 100;
  };

  const renderStepContent = () => {
    switch (currentStep) {
      case 'email':
        return (
          <CheckoutAuth
            onEmailSubmit={handleEmailSubmit}
            defaultEmail={user?.email || email || undefined}
          />
        );

      case 'extras':
        return (
          <ExtrasSelection
            extras={availableExtras}
            currency={currentCurrency}
            onSubmit={handleExtrasSubmit}
            onBack={() => setCurrentStep('email')}
            defaultSelections={selectedExtras}
          />
        );

      case 'billing':
        if (!email) {
          setCurrentStep('email');
          return null;
        }
        return (
          <BillingAddressStep
            onSubmit={handleBillingSubmit}
            onBack={() => setCurrentStep('extras')}
            defaultAddress={billingAddress || undefined}
          />
        );

      case 'review':
        if (!email || !billingAddress) {
          setCurrentStep('email');
          return null;
        }
        return (
          <div className="space-y-6">
            <BookingReview
              listing={listing}
              slot={slot}
              travelerInfo={{ email, firstName: '', lastName: '', phone: '' }}
              extras={getExtrasWithDetails()}
              currency={currentCurrency}
              quantity={hold.quantity || 1}
              personTypeBreakdown={hold.personTypeBreakdown}
              onEditTraveler={() => setCurrentStep('email')}
              onEditExtras={() => setCurrentStep('extras')}
              onConfirm={handleConfirmBooking}
              onBack={() => setCurrentStep('billing')}
              isProcessing={createBookingMutation.isPending || processPaymentMutation.isPending}
              isBillingOnly={true}
            />
            <div className="border-t pt-6">
              <PaymentMethodSelector
                availableMethods={['mock', 'offline', 'click_to_pay']}
                onSelect={setPaymentMethod}
                selectedMethod={paymentMethod}
              />
            </div>
            <CheckoutConsents
              ref={consentsRef}
              termsAccepted={termsAccepted}
              onTermsChange={(accepted) => {
                setTermsAccepted(accepted);
                if (accepted) {
                  setHighlightConsents(false);
                  setTermsError(undefined);
                }
              }}
              marketingAccepted={marketingAccepted}
              onMarketingChange={setMarketingAccepted}
              termsError={termsError}
              highlight={highlightConsents}
            />
          </div>
        );

      case 'confirmation':
        return completedBooking ? <BookingConfirmation booking={completedBooking} /> : null;

      default:
        return null;
    }
  };

  if (currentStep === 'confirmation') {
    return <div className="py-8">{renderStepContent()}</div>;
  }

  // Email step has simpler layout (no progress indicator - it's the entry point)
  if (currentStep === 'email') {
    return (
      <div className="max-w-md mx-auto">
        {/* Hold Timer */}
        <div className="mb-6" data-testid="hold-timer">
          <HoldTimer expiresAt={hold.expiresAt} onExpire={onExpired} />
        </div>
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          {renderStepContent()}
        </div>
      </div>
    );
  }

  return (
    <>
      {/* Pricing Disclosure Modal */}
      {priceChangeInfo && (
        <PricingDisclosureModal
          isOpen={showDisclosureModal}
          priceChange={priceChangeInfo}
          onAccept={handleDisclosureAccept}
          onCancel={handleDisclosureCancel}
        />
      )}

      <div className="max-w-3xl mx-auto">
        {/* Hold Timer */}
        <div className="mb-6" data-testid="hold-timer">
          <HoldTimer expiresAt={hold.expiresAt} onExpire={onExpired} />
        </div>

        {/* Progress Indicator */}
        <div className="mb-8">
          <div className="flex items-center justify-between">
            {steps.map((step, index) => {
              const isActive = currentStep === step.key;
              const isCompleted = index < currentStepIndex;

              return (
                <div key={step.key} className="flex items-center flex-1">
                  <div className="flex flex-col items-center flex-1">
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
                    <div
                      className={`h-1 flex-1 mx-2 ${isCompleted ? 'bg-success' : 'bg-gray-200'}`}
                    />
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
        {(createBookingMutation.isError || processPaymentMutation.isError) && (
          <div className="mt-4 p-4 bg-error-light border border-error/20 rounded-lg">
            <p className="text-sm text-error-dark">
              {createBookingMutation.error?.message ||
                processPaymentMutation.error?.message ||
                t('booking_error')}
            </p>
          </div>
        )}
      </div>
    </>
  );
}
