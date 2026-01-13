'use client';

import { useState } from 'react';
import { useParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { ProgressBar } from './ProgressBar';
import { TripSummary } from './TripSummary';
import { TripBasicsStep } from './steps/TripBasicsStep';
import { InterestsStep } from './steps/InterestsStep';
import { BudgetStyleStep } from './steps/BudgetStyleStep';
import { ContactStep } from './steps/ContactStep';
import { SuccessPage } from './SuccessPage';
import { customTripApi, type CustomTripRequestData } from '@/lib/api/client';

export type WizardStep = 'basics' | 'interests' | 'budget' | 'contact' | 'success';

export interface TripBasicsData {
  travelDates: {
    start: string;
    end: string;
  };
  datesFlexible: boolean;
  adults: number;
  children: number;
  durationDays: number;
}

export interface InterestsData {
  interests: string[];
}

export interface BudgetStyleData {
  budgetPerPerson: number;
  accommodationStyle: 'budget' | 'mid-range' | 'luxury';
  travelPace: 'relaxed' | 'moderate' | 'active';
  specialOccasions: string[];
}

export interface ContactData {
  name: string;
  email: string;
  phone: string;
  whatsapp: string;
  sameAsPhone: boolean;
  country: string;
  specialRequests: string;
  preferredContact: 'email' | 'phone' | 'whatsapp';
  newsletterConsent: boolean;
}

export interface WizardData {
  basics: TripBasicsData | null;
  interests: InterestsData | null;
  budgetStyle: BudgetStyleData | null;
  contact: ContactData | null;
}

export interface SubmissionResult {
  reference: string;
  email: string;
}

export function CustomTripWizard() {
  const params = useParams();
  const locale = (params?.locale as string) || 'en';
  const t = useTranslations('customTrip');

  const [currentStep, setCurrentStep] = useState<WizardStep>('basics');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submissionError, setSubmissionError] = useState<string | null>(null);
  const [submissionResult, setSubmissionResult] = useState<SubmissionResult | null>(null);

  const [wizardData, setWizardData] = useState<WizardData>({
    basics: null,
    interests: null,
    budgetStyle: null,
    contact: null,
  });

  const steps: { key: WizardStep; label: string }[] = [
    { key: 'basics', label: t('steps.basics') },
    { key: 'interests', label: t('steps.interests') },
    { key: 'budget', label: t('steps.budget') },
    { key: 'contact', label: t('steps.contact') },
  ];

  const currentStepIndex = steps.findIndex((s) => s.key === currentStep);

  const handleBasicsComplete = (data: TripBasicsData) => {
    setWizardData((prev) => ({ ...prev, basics: data }));
    setCurrentStep('interests');
  };

  const handleInterestsComplete = (data: InterestsData) => {
    setWizardData((prev) => ({ ...prev, interests: data }));
    setCurrentStep('budget');
  };

  const handleBudgetStyleComplete = (data: BudgetStyleData) => {
    setWizardData((prev) => ({ ...prev, budgetStyle: data }));
    setCurrentStep('contact');
  };

  const handleContactComplete = async (data: ContactData) => {
    setWizardData((prev) => ({ ...prev, contact: data }));
    setIsSubmitting(true);
    setSubmissionError(null);

    try {
      // Build the API request
      const requestData: CustomTripRequestData = {
        travel_dates: {
          start: wizardData.basics!.travelDates.start,
          end: wizardData.basics!.travelDates.end,
          flexible: wizardData.basics!.datesFlexible,
        },
        travelers: {
          adults: wizardData.basics!.adults,
          children: wizardData.basics!.children,
        },
        duration_days: wizardData.basics!.durationDays,
        interests: wizardData.interests!.interests,
        budget: {
          per_person: wizardData.budgetStyle!.budgetPerPerson,
          currency: 'EUR',
        },
        accommodation_style: wizardData.budgetStyle!.accommodationStyle,
        travel_pace: wizardData.budgetStyle!.travelPace,
        special_occasions:
          wizardData.budgetStyle!.specialOccasions.length > 0
            ? wizardData.budgetStyle!.specialOccasions
            : undefined,
        contact: {
          name: data.name,
          email: data.email,
          phone: data.phone,
          whatsapp: data.sameAsPhone ? data.phone : data.whatsapp || null,
          country: data.country,
          preferred_method: data.preferredContact,
        },
        special_requests: data.specialRequests || null,
        newsletter_consent: data.newsletterConsent,
        locale: locale as 'en' | 'fr',
      };

      const response = await customTripApi.submit(requestData);

      setSubmissionResult({
        reference: response.data.reference,
        email: data.email,
      });
      setCurrentStep('success');
    } catch (error) {
      console.error('Failed to submit custom trip request:', error);
      setSubmissionError(t('errors.submission_failed'));
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleBack = () => {
    const stepOrder: WizardStep[] = ['basics', 'interests', 'budget', 'contact'];
    const currentIndex = stepOrder.indexOf(currentStep);
    if (currentIndex > 0) {
      setCurrentStep(stepOrder[currentIndex - 1]);
    }
  };

  if (currentStep === 'success' && submissionResult) {
    return <SuccessPage reference={submissionResult.reference} email={submissionResult.email} />;
  }

  return (
    <div className="container mx-auto px-4 py-8">
      {/* Progress Bar */}
      <ProgressBar steps={steps} currentStep={currentStep} />

      {/* Main Content Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8">
        {/* Form Section */}
        <div className="lg:col-span-2">
          <div className="bg-white rounded-xl shadow-sm p-6 md:p-8">
            {currentStep === 'basics' && (
              <TripBasicsStep initialData={wizardData.basics} onComplete={handleBasicsComplete} />
            )}
            {currentStep === 'interests' && (
              <InterestsStep
                initialData={wizardData.interests}
                onComplete={handleInterestsComplete}
                onBack={handleBack}
              />
            )}
            {currentStep === 'budget' && (
              <BudgetStyleStep
                initialData={wizardData.budgetStyle}
                onComplete={handleBudgetStyleComplete}
                onBack={handleBack}
              />
            )}
            {currentStep === 'contact' && (
              <ContactStep
                initialData={wizardData.contact}
                onComplete={handleContactComplete}
                onBack={handleBack}
                isSubmitting={isSubmitting}
                error={submissionError}
              />
            )}
          </div>
        </div>

        {/* Summary Panel */}
        <div className="lg:col-span-1">
          <div className="sticky top-24">
            <TripSummary wizardData={wizardData} currentStep={currentStep} />
          </div>
        </div>
      </div>
    </div>
  );
}
