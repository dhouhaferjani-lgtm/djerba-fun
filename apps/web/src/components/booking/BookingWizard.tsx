'use client';

import { useState, useMemo } from 'react';
import { useTranslations } from 'next-intl';
import { ExtrasSelection } from './ExtrasSelection';
import { BookingReview } from './BookingReview';
import { PaymentMethodSelector, type PaymentMethod } from './PaymentMethodSelector';
import { BookingConfirmation } from './BookingConfirmation';
import { CheckoutContactForm, type ContactInfo } from './CheckoutContactForm';
import { CurrencyNoticeModal } from './CurrencyNoticeModal';
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

type Step = 'contact' | 'extras' | 'review' | 'confirmation';

export function BookingWizard({
  hold,
  listing,
  slot,
  availableExtras = [],
  onExpired,
}: BookingWizardProps) {
  const t = useTranslations('booking');
  const { user } = useAuth();

  // Start with contact information collection
  const [currentStep, setCurrentStep] = useState<Step>('contact');
  const [contactInfo, setContactInfo] = useState<ContactInfo | null>(null);
  const [selectedExtras, setSelectedExtras] = useState<SelectedExtra[]>([]);
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod | undefined>();
  const [completedBooking, setCompletedBooking] = useState<Booking | null>(null);

  // Consent state
  const [termsAccepted, setTermsAccepted] = useState(false);
  const [marketingAccepted, setMarketingAccepted] = useState(false);
  const [termsError, setTermsError] = useState<string | undefined>();

  // Currency notice modal state (for Clictopay redirect)
  const [showCurrencyNotice, setShowCurrencyNotice] = useState(false);
  const [pendingRedirectUrl, setPendingRedirectUrl] = useState<string | null>(null);
  const [pendingPaymentAmount, setPendingPaymentAmount] = useState<number>(0);
  const [pendingTndAmount, setPendingTndAmount] = useState<number>(0);

  const createBookingMutation = useCreateBooking();
  const processPaymentMutation = useProcessPayment();

  // Memoize contact form default values to prevent unnecessary re-renders
  const contactFormDefaultValues = useMemo(
    () => ({
      email: user?.email || contactInfo?.email || '',
      phone: user?.phone || contactInfo?.phone || '',
      firstName: user?.firstName || contactInfo?.firstName || '',
      lastName: user?.lastName || contactInfo?.lastName || '',
    }),
    [user?.email, user?.phone, user?.firstName, user?.lastName, contactInfo]
  );

  // Progress steps - Show ALL steps for clarity
  const steps: { key: Step; label: string }[] = [
    { key: 'contact', label: t('step_contact') || 'Contact' },
    { key: 'extras', label: t('step_extras') || 'Extras' },
    { key: 'review', label: t('step_review') || 'Review & Payment' },
  ];

  const currentStepIndex = steps.findIndex((s) => s.key === currentStep);

  // Handle contact information submission
  const handleContactSubmit = (info: ContactInfo) => {
    setContactInfo(info);
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
    if (!contactInfo) return;

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
            email: contactInfo.email,
            context: 'checkout',
          }
        );
      } catch (consentError) {
        console.error('Failed to record consents:', consentError);
        // Continue with booking even if consent recording fails
        // The consent is still given (checkbox checked), just not recorded in DB
      }

      // Send full contact information in travelers array
      const bookingResponse = await createBookingMutation.mutateAsync({
        holdId: hold.id,
        travelers: [
          {
            email: contactInfo.email,
            firstName: contactInfo.firstName,
            lastName: contactInfo.lastName,
            phone: contactInfo.phone,
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

        // Check if this is a redirect-based payment (Clictopay)
        if (paymentResponse.requires_redirect && paymentResponse.redirect_url) {
          // Store booking info in sessionStorage for post-redirect recovery
          sessionStorage.setItem(
            'pending_payment',
            JSON.stringify({
              bookingId: booking.id,
              bookingNumber: booking.bookingNumber,
              intentId: paymentResponse.payment_intent?.id,
            })
          );

          // Calculate TND equivalent for the currency notice modal
          const bookingAmount = paymentResponse.data.totalAmount || 0;
          const tndEquivalent = paymentResponse.data.tndAmount || bookingAmount * 3.1; // Fallback rate ~1 EUR = 3.1 TND

          // Store redirect info and show currency notice modal
          setPendingRedirectUrl(paymentResponse.redirect_url);
          setPendingPaymentAmount(bookingAmount);
          setPendingTndAmount(tndEquivalent);
          setShowCurrencyNotice(true);

          return; // Don't continue - wait for user to confirm in modal
        }

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

  // Handler for currency notice modal confirmation
  const handleCurrencyNoticeConfirm = () => {
    if (pendingRedirectUrl) {
      window.location.href = pendingRedirectUrl;
    }
  };

  // Handler for currency notice modal cancel
  const handleCurrencyNoticeCancel = () => {
    setShowCurrencyNotice(false);
    setPendingRedirectUrl(null);
    // User can choose a different payment method
  };

  const renderStepContent = () => {
    switch (currentStep) {
      case 'contact':
        return (
          <CheckoutContactForm
            onSubmit={handleContactSubmit}
            defaultValues={contactFormDefaultValues}
          />
        );

      case 'extras':
        return (
          <ExtrasSelection
            extras={availableExtras}
            currency={slot.currency}
            onSubmit={handleExtrasSubmit}
            onBack={() => setCurrentStep('contact')}
            defaultSelections={selectedExtras}
          />
        );

      case 'review':
        if (!contactInfo) {
          setCurrentStep('contact');
          return null;
        }
        return (
          <div className="space-y-6">
            <BookingReview
              listing={listing}
              slot={slot}
              travelerInfo={contactInfo}
              extras={getExtrasWithDetails()}
              currency={slot.currency}
              quantity={hold.quantity || 1}
              personTypeBreakdown={hold.personTypeBreakdown}
              onEditTraveler={() => setCurrentStep('contact')}
              onEditExtras={() => setCurrentStep('extras')}
              onConfirm={handleConfirmBooking}
              onBack={() => setCurrentStep('extras')}
              isProcessing={createBookingMutation.isPending || processPaymentMutation.isPending}
              isBillingOnly={true}
            />
            <div className="border-t pt-6">
              <h3 className="text-lg font-semibold text-neutral-900 mb-4">
                {t('payment_method') || 'Payment Method'}
              </h3>
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

  // Contact step has simpler layout (no progress indicator - it's the entry point)
  if (currentStep === 'contact') {
    return (
      <div>
        {/* Hold Timer */}
        <div className="mb-6">
          <HoldTimer expiresAt={hold.expiresAt} onExpire={onExpired} />
        </div>
        <div className="bg-white rounded-lg shadow-sm border border-neutral-200 p-6">
          {renderStepContent()}
        </div>
      </div>
    );
  }

  return (
    <div>
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
                        ? 'bg-primary-600 text-white'
                        : isCompleted
                          ? 'bg-success text-white'
                          : 'bg-neutral-200 text-neutral-600'
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
                      isActive
                        ? 'text-primary-700'
                        : isCompleted
                          ? 'text-success'
                          : 'text-neutral-500'
                    }`}
                  >
                    {step.label}
                  </span>
                </div>
                {index < steps.length - 1 && (
                  <div
                    className={`h-1 flex-1 mx-2 ${isCompleted ? 'bg-success' : 'bg-neutral-200'}`}
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
          <p className="text-sm text-error-dark flex items-center gap-2">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="h-5 w-5"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fillRule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                clipRule="evenodd"
              />
            </svg>
            {createBookingMutation.error?.message ||
              processPaymentMutation.error?.message ||
              t('booking_error')}
          </p>
        </div>
      )}

      {/* Currency Notice Modal - shown before Clictopay redirect */}
      <CurrencyNoticeModal
        isOpen={showCurrencyNotice}
        onConfirm={handleCurrencyNoticeConfirm}
        onCancel={handleCurrencyNoticeCancel}
        amount={pendingPaymentAmount}
        currency={slot.currency}
        tndAmount={pendingTndAmount}
      />
    </div>
  );
}
