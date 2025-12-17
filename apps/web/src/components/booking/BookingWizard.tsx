'use client';

import { useState, useEffect, useMemo } from 'react';
import { useTranslations } from 'next-intl';
import { TravelerInfoForm } from './TravelerInfoForm';
import { MultiTravelerForm } from './MultiTravelerForm';
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

// Person type interface for multi-traveler form
interface PersonType {
  key: string;
  label: { en: string; fr: string } | string;
  price: number;
  minAge: number | null;
  maxAge: number | null;
}

// Extended traveler info with person type
interface ExtendedTraveler extends TravelerInfo {
  personType?: string;
}

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
  const [allTravelers, setAllTravelers] = useState<ExtendedTraveler[]>([]);
  const [selectedExtras, setSelectedExtras] = useState<SelectedExtra[]>([]);
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod | undefined>();
  const [completedBooking, setCompletedBooking] = useState<Booking | null>(null);

  const createBookingMutation = useCreateBooking();
  const processPaymentMutation = useProcessPayment();

  // Get total number of guests from hold
  const totalGuests = hold.quantity || 1;
  const personTypeBreakdown = hold.personTypeBreakdown;

  // Get person types from listing pricing
  const personTypes = useMemo((): PersonType[] => {
    const pricing = listing.pricing || {};
    const types = pricing.personTypes;

    if (types && Array.isArray(types) && types.length > 0) {
      return types;
    }

    // Return defaults based on base price
    const basePrice = pricing.basePrice || 0;
    const numericPrice = typeof basePrice === 'string' ? parseFloat(basePrice) : basePrice;

    return [
      {
        key: 'adult',
        label: { en: 'Adult', fr: 'Adulte' },
        price: numericPrice,
        minAge: 18,
        maxAge: null,
      },
      {
        key: 'child',
        label: { en: 'Child', fr: 'Enfant' },
        price: Math.round(numericPrice * 0.5),
        minAge: 4,
        maxAge: 17,
      },
      { key: 'infant', label: { en: 'Infant', fr: 'Bébé' }, price: 0, minAge: 0, maxAge: 3 },
    ];
  }, [listing]);

  // Skip auth step if already authenticated
  useEffect(() => {
    if (!isAuthLoading && isAuthenticated && currentStep === 'auth') {
      setCurrentStep('traveler');
    }
  }, [isAuthenticated, isAuthLoading, currentStep]);

  // Pre-fill traveler info from authenticated user
  useEffect(() => {
    if (isAuthenticated && user && !travelerInfo) {
      // User may have travelerProfile nested with firstName/lastName
      const profile = (
        user as { travelerProfile?: { firstName?: string; lastName?: string; phone?: string } }
      ).travelerProfile;
      setTravelerInfo({
        firstName: profile?.firstName || '',
        lastName: profile?.lastName || '',
        email: user.email || '',
        phone: profile?.phone || '',
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

  // Handle single traveler form submission
  const handleTravelerSubmit = (data: TravelerInfo) => {
    setTravelerInfo(data);
    setAllTravelers([data]);
    setCurrentStep('extras');
  };

  // Handle multi-traveler form submission
  const handleMultiTravelerSubmit = (data: {
    primaryTraveler: {
      firstName: string;
      lastName: string;
      email: string;
      phone?: string;
      specialRequests?: string;
    };
    additionalTravelers?: Array<{
      firstName: string;
      lastName: string;
      personType?: string;
    }>;
  }) => {
    // Convert primary traveler to TravelerInfo
    const primary: ExtendedTraveler = {
      firstName: data.primaryTraveler.firstName,
      lastName: data.primaryTraveler.lastName,
      email: data.primaryTraveler.email,
      phone: data.primaryTraveler.phone,
      specialRequests: data.primaryTraveler.specialRequests,
    };

    // Build all travelers list
    const travelers: ExtendedTraveler[] = [primary];

    if (data.additionalTravelers) {
      for (const additional of data.additionalTravelers) {
        travelers.push({
          firstName: additional.firstName,
          lastName: additional.lastName,
          email: '', // Additional travelers don't need email
          personType: additional.personType,
        });
      }
    }

    setTravelerInfo(primary);
    setAllTravelers(travelers);
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
      const sessionId = hold.sessionId || getGuestSessionId();

      // Prepare travelers list (use allTravelers if available, otherwise just primary)
      const travelersToSend = allTravelers.length > 0 ? allTravelers : [travelerInfo];

      // Create booking with session_id for guest checkout
      const bookingResponse = await createBookingMutation.mutateAsync({
        holdId: hold.id,
        travelers: travelersToSend,
        specialRequests: travelerInfo.specialRequests || undefined,
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
        // Use MultiTravelerForm when there are multiple guests
        if (totalGuests > 1) {
          return (
            <MultiTravelerForm
              totalGuests={totalGuests}
              personTypeBreakdown={personTypeBreakdown}
              personTypes={personTypes}
              onSubmit={handleMultiTravelerSubmit}
              defaultValues={
                travelerInfo
                  ? {
                      primaryTraveler: {
                        firstName: travelerInfo.firstName,
                        lastName: travelerInfo.lastName,
                        email: travelerInfo.email,
                        phone: travelerInfo.phone || undefined,
                        specialRequests: travelerInfo.specialRequests || undefined,
                      },
                      additionalTravelers: allTravelers.slice(1).map((t) => ({
                        firstName: t.firstName,
                        lastName: t.lastName,
                        personType: t.personType,
                      })),
                    }
                  : undefined
              }
            />
          );
        }

        // Use simple TravelerInfoForm for single guest
        return (
          <TravelerInfoForm
            onSubmit={handleTravelerSubmit}
            defaultValues={
              travelerInfo
                ? {
                    firstName: travelerInfo.firstName,
                    lastName: travelerInfo.lastName,
                    email: travelerInfo.email,
                    phone: travelerInfo.phone || '',
                    specialRequests: travelerInfo.specialRequests || undefined,
                  }
                : undefined
            }
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
              allTravelers={allTravelers.length > 0 ? allTravelers : undefined}
              extras={getExtrasWithDetails()}
              currency={slot.currency}
              quantity={hold.quantity || 1}
              personTypeBreakdown={hold.personTypeBreakdown}
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
