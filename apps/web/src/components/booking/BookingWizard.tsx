'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { ExtrasSelection } from './ExtrasSelection';
import { BookingReview } from './BookingReview';
import { PaymentMethodSelector, type PaymentMethod } from './PaymentMethodSelector';
import { BookingConfirmation } from './BookingConfirmation';
import { CheckoutAuth } from './CheckoutAuth';
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
}

type Step = 'email' | 'extras' | 'review' | 'confirmation';

export function BookingWizard({
  hold,
  listing,
  slot,
  availableExtras = [],
  onExpired,
}: BookingWizardProps) {
  const t = useTranslations('booking');
  const { user } = useAuth();

  // Simplified: Start with email collection
  const [currentStep, setCurrentStep] = useState<Step>('email');
  const [email, setEmail] = useState<string | null>(null);
  const [selectedExtras, setSelectedExtras] = useState<SelectedExtra[]>([]);
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod | undefined>();
  const [completedBooking, setCompletedBooking] = useState<Booking | null>(null);

  // Consent state
  const [termsAccepted, setTermsAccepted] = useState(false);
  const [marketingAccepted, setMarketingAccepted] = useState(false);
  const [termsError, setTermsError] = useState<string | undefined>();

  const createBookingMutation = useCreateBooking();
  const processPaymentMutation = useProcessPayment();

  // Progress steps (email is not shown in progress, it's the entry point)
  const steps: { key: Step; label: string }[] = [
    { key: 'extras', label: t('step_extras') },
    { key: 'review', label: t('step_review') },
  ];

  const currentStepIndex = steps.findIndex((s) => s.key === currentStep);

  // Handle email submission
  const handleEmailSubmit = (submittedEmail: string) => {
    setEmail(submittedEmail);
    // Skip extras step if no extras are available
    if (!availableExtras || availableExtras.length === 0) {
      setCurrentStep('review');
    } else {
      setCurrentStep('extras');
    }
  };

  const handleExtrasSubmit = (extras: SelectedExtra[]) => {
    setSelectedExtras(extras);
    setCurrentStep('review');
  };

  const handleConfirmBooking = async () => {
    if (!email) return;

    // Validate terms acceptance
    if (!termsAccepted) {
      setTermsError(t('terms_required') || 'You must accept the terms and conditions to continue.');
      return;
    }
    setTermsError(undefined);

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
        // The consent is still given (checkbox checked), just not recorded in DB
      }

      // Email-only checkout: send ONLY email in travelers array
      // Empty participant records will be created server-side
      // and can be filled in post-payment if required by the listing
      const extrasForBooking = getExtrasWithDetails();
      const bookingResponse = await createBookingMutation.mutateAsync({
        holdId: hold.id,
        travelers: [{ email }], // MINIMAL DATA - email only
        extras: extrasForBooking.map((e) => ({
          name: e.name,
          price: e.price,
          quantity: e.quantity,
        })),
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
          extra.displayPrice ?? (slot.currency === 'TND' ? extra.priceTnd : extra.priceEur);
        return {
          id: extra.id,
          name: extra.name,
          quantity: selected.quantity,
          price,
        };
      })
      .filter((e): e is NonNullable<typeof e> => e !== null);
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
            currency={slot.currency}
            onSubmit={handleExtrasSubmit}
            onBack={() => setCurrentStep('email')}
            defaultSelections={selectedExtras}
          />
        );

      case 'review':
        if (!email) {
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
              currency={slot.currency}
              quantity={hold.quantity || 1}
              personTypeBreakdown={hold.personTypeBreakdown}
              onEditTraveler={() => setCurrentStep('email')}
              onEditExtras={() => setCurrentStep('extras')}
              onConfirm={handleConfirmBooking}
              onBack={() => setCurrentStep('extras')}
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
              termsAccepted={termsAccepted}
              onTermsChange={setTermsAccepted}
              marketingAccepted={marketingAccepted}
              onMarketingChange={setMarketingAccepted}
              termsError={termsError}
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
        <div className="mb-6">
          <HoldTimer expiresAt={hold.expiresAt} onExpire={onExpired} />
        </div>
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          {renderStepContent()}
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-3xl mx-auto">
      {/* Hold Timer */}
      <div className="mb-6">
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
                    className={`h-1 flex-1 mx-2 ${isCompleted ? 'bg-green-500' : 'bg-gray-200'}`}
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
        <div className="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
          <p className="text-sm text-red-800">
            {createBookingMutation.error?.message ||
              processPaymentMutation.error?.message ||
              t('booking_error')}
          </p>
        </div>
      )}
    </div>
  );
}
