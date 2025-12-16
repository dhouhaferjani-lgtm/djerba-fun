'use client';

import { useState, useEffect } from 'react';
import { useTranslations } from 'next-intl';
import { TravelerInfoForm } from './TravelerInfoForm';
import { ExtrasSelection } from './ExtrasSelection';
import { BookingReview } from './BookingReview';
import { PaymentMethodSelector, type PaymentMethod } from './PaymentMethodSelector';
import { BookingConfirmation } from './BookingConfirmation';
import { CheckoutAuth } from './CheckoutAuth';
import HoldTimer from '@/components/availability/HoldTimer';
import { useCreateBooking, useProcessPayment } from '@/lib/api/hooks';
import { getGuestSessionId } from '@/lib/utils/session';
import { useAuth } from '@/lib/contexts/AuthContext';
import type {
  TravelerInfo,
  BookingHold,
  Booking,
  ListingSummary,
  AvailabilitySlot,
} from '@go-adventure/schemas';

interface Extra {
  id: string;
  name: string;
  description?: string;
  price: number;
  currency: string;
}

interface SelectedExtra {
  extraId: string;
  quantity: number;
}

interface BookingWizardProps {
  hold: BookingHold;
  listing: ListingSummary;
  slot: AvailabilitySlot;
  availableExtras?: Extra[];
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

  // Start at auth step if not authenticated, otherwise skip to traveler
  const [currentStep, setCurrentStep] = useState<Step>('auth');
  const [travelerInfo, setTravelerInfo] = useState<TravelerInfo | null>(null);
  const [selectedExtras, setSelectedExtras] = useState<SelectedExtra[]>([]);
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod | undefined>();
  const [completedBooking, setCompletedBooking] = useState<Booking | null>(null);

  const createBookingMutation = useCreateBooking();
  const processPaymentMutation = useProcessPayment();

  // Skip auth step if already authenticated
  useEffect(() => {
    if (!isAuthLoading && isAuthenticated && currentStep === 'auth') {
      setCurrentStep('traveler');
    }
  }, [isAuthenticated, isAuthLoading, currentStep]);

  // Pre-fill traveler info from authenticated user
  useEffect(() => {
    if (isAuthenticated && user && !travelerInfo) {
      const userProfile = user as any;
      setTravelerInfo({
        firstName: userProfile.firstName || userProfile.first_name || '',
        lastName: userProfile.lastName || userProfile.last_name || '',
        email: userProfile.email || '',
        phone: userProfile.phone || '',
      });
    }
  }, [isAuthenticated, user, travelerInfo]);

  // Only show these steps in progress indicator (auth is a pre-step)
  const steps: { key: Step; label: string }[] = [
    { key: 'traveler', label: t('step_traveler') },
    { key: 'extras', label: t('step_extras') },
    { key: 'review', label: t('step_review') },
  ];

  const currentStepIndex = steps.findIndex((s) => s.key === currentStep);

  const handleAuthComplete = () => {
    setCurrentStep('traveler');
  };

  const handleTravelerSubmit = (data: TravelerInfo) => {
    setTravelerInfo(data);
    setCurrentStep('extras');
  };

  const handleExtrasSubmit = (extras: SelectedExtra[]) => {
    setSelectedExtras(extras);
    setCurrentStep('review');
  };

  const handleConfirmBooking = async () => {
    if (!travelerInfo) return;

    try {
      // Get session ID for guest checkout (from hold or local storage)
      const sessionId = (hold as any).sessionId || getGuestSessionId();

      // Create booking with session_id for guest checkout
      const bookingResponse = await createBookingMutation.mutateAsync({
        holdId: hold.id,
        travelers: [travelerInfo],
        specialRequests: travelerInfo.specialRequests || undefined,
        sessionId,
      } as any);

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
        const extra = availableExtras.find((e) => e.id === selected.extraId);
        if (!extra) return null;
        return {
          id: extra.id,
          name: extra.name,
          quantity: selected.quantity,
          price: extra.price,
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
        return (
          <TravelerInfoForm
            onSubmit={handleTravelerSubmit}
            defaultValues={travelerInfo || undefined}
          />
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
        if (!travelerInfo) {
          setCurrentStep('traveler');
          return null;
        }
        return (
          <div className="space-y-6">
            <BookingReview
              listing={listing}
              slot={slot}
              travelerInfo={travelerInfo}
              extras={getExtrasWithDetails()}
              currency={slot.currency}
              onEditTraveler={() => setCurrentStep('traveler')}
              onEditExtras={() => setCurrentStep('extras')}
              onConfirm={handleConfirmBooking}
              onBack={() => setCurrentStep('extras')}
              isProcessing={createBookingMutation.isPending || processPaymentMutation.isPending}
            />
            <div className="border-t pt-6">
              <PaymentMethodSelector
                availableMethods={['mock', 'offline', 'click_to_pay']}
                onSelect={setPaymentMethod}
                selectedMethod={paymentMethod}
              />
            </div>
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
