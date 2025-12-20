'use client';

import { useState, useEffect } from 'react';
import { useTranslations } from 'next-intl';
import { TravelerInfoForm } from './TravelerInfoForm';
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
  TravelerInfo,
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

type Step = 'auth' | 'traveler' | 'extras' | 'review' | 'payment' | 'confirmation';

export function BookingWizard({
  hold,
  listing,
  slot,
  availableExtras = [],
  onExpired,
}: BookingWizardProps) {
  const t = useTranslations('booking');
  const { user, isAuthenticated, isLoading: isAuthLoading } = useAuth();

  // Start at auth step if not authenticated, otherwise skip to billing
  const [currentStep, setCurrentStep] = useState<Step>('auth');
  const [billingContact, setBillingContact] = useState<TravelerInfo | null>(null);
  const [selectedExtras, setSelectedExtras] = useState<SelectedExtra[]>([]);
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod | undefined>();
  const [completedBooking, setCompletedBooking] = useState<Booking | null>(null);

  // Consent state
  const [termsAccepted, setTermsAccepted] = useState(false);
  const [marketingAccepted, setMarketingAccepted] = useState(false);
  const [termsError, setTermsError] = useState<string | undefined>();

  const createBookingMutation = useCreateBooking();
  const processPaymentMutation = useProcessPayment();

  // Skip auth step if already authenticated
  useEffect(() => {
    if (!isAuthLoading && isAuthenticated && currentStep === 'auth') {
      setCurrentStep('traveler');
    }
  }, [isAuthenticated, isAuthLoading, currentStep]);

  // Pre-fill billing contact from authenticated user
  useEffect(() => {
    if (isAuthenticated && user && !billingContact) {
      const profile = (
        user as { travelerProfile?: { firstName?: string; lastName?: string; phone?: string } }
      ).travelerProfile;
      setBillingContact({
        firstName: profile?.firstName || '',
        lastName: profile?.lastName || '',
        email: user.email || '',
        phone: profile?.phone || '',
      });
    }
  }, [isAuthenticated, user, billingContact]);

  // Only show these steps in progress indicator (auth is a pre-step)
  const steps: { key: Step; label: string }[] = [
    { key: 'traveler', label: t('step_billing') || 'Billing' },
    { key: 'extras', label: t('step_extras') },
    { key: 'review', label: t('step_review') },
  ];

  const currentStepIndex = steps.findIndex((s) => s.key === currentStep);

  const handleAuthComplete = () => {
    setCurrentStep('traveler');
  };

  // Handle billing contact form submission
  const handleBillingSubmit = (data: TravelerInfo) => {
    setBillingContact(data);
    setCurrentStep('extras');
  };

  const handleExtrasSubmit = (extras: SelectedExtra[]) => {
    setSelectedExtras(extras);
    setCurrentStep('review');
  };

  const handleConfirmBooking = async () => {
    if (!billingContact) return;

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
            email: billingContact.email,
            context: 'checkout',
          }
        );
      } catch (consentError) {
        console.error('Failed to record consents:', consentError);
        // Continue with booking even if consent recording fails
        // The consent is still given (checkbox checked), just not recorded in DB
      }

      // Send only billing contact - participants are created server-side
      // and can be filled in post-checkout
      const bookingResponse = await createBookingMutation.mutateAsync({
        holdId: hold.id,
        travelers: [billingContact], // Billing contact becomes first participant
        specialRequests: billingContact.specialRequests || undefined,
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
      case 'auth':
        return (
          <CheckoutAuth
            onContinueAsGuest={handleAuthComplete}
            onLoginSuccess={handleAuthComplete}
          />
        );

      case 'traveler':
        // Simplified: Only collect billing contact
        // Participant names are entered post-checkout
        return (
          <div>
            <div className="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <p className="text-sm text-blue-800">
                {t('billing_contact_info') ||
                  "Enter the billing contact details. You'll be able to add participant names after completing the booking."}
              </p>
            </div>
            <TravelerInfoForm
              onSubmit={handleBillingSubmit}
              defaultValues={
                billingContact
                  ? {
                      firstName: billingContact.firstName,
                      lastName: billingContact.lastName,
                      email: billingContact.email,
                      phone: billingContact.phone || '',
                      specialRequests: billingContact.specialRequests || undefined,
                    }
                  : undefined
              }
            />
          </div>
        );

      case 'extras':
        return (
          <ExtrasSelection
            extras={availableExtras}
            currency={slot.currency}
            onSubmit={handleExtrasSubmit}
            onBack={() => setCurrentStep('traveler')}
            defaultSelections={selectedExtras}
          />
        );

      case 'review':
        if (!billingContact) {
          setCurrentStep('traveler');
          return null;
        }
        return (
          <div className="space-y-6">
            <BookingReview
              listing={listing}
              slot={slot}
              travelerInfo={billingContact}
              extras={getExtrasWithDetails()}
              currency={slot.currency}
              quantity={hold.quantity || 1}
              personTypeBreakdown={hold.personTypeBreakdown}
              onEditTraveler={() => setCurrentStep('traveler')}
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

  // Auth step has simpler layout (no progress indicator)
  if (currentStep === 'auth') {
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
