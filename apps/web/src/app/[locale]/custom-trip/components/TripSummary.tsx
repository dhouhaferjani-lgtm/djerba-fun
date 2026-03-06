'use client';

import { useTranslations } from 'next-intl';
import {
  Calendar,
  Users,
  Clock,
  Target,
  Wallet,
  Building2,
  Gauge,
  MessageCircle,
} from 'lucide-react';
import type { WizardData, WizardStep } from './CustomTripWizard';

interface TripSummaryProps {
  wizardData: WizardData;
  currentStep: WizardStep;
}

const INTEREST_LABELS: Record<string, string> = {
  'history-culture': 'History & Culture',
  'desert-adventures': 'Desert Adventures',
  'beach-relaxation': 'Beach & Relaxation',
  'food-gastronomy': 'Food & Gastronomy',
  'hiking-nature': 'Hiking & Nature',
  photography: 'Photography',
  'local-festivals': 'Local Festivals',
  'star-wars-sites': 'Star Wars Sites',
};

const ACCOMMODATION_LABELS: Record<string, string> = {
  budget: 'Budget',
  'mid-range': 'Mid-range',
  luxury: 'Luxury',
};

const PACE_LABELS: Record<string, string> = {
  relaxed: 'Relaxed',
  moderate: 'Moderate',
  active: 'Active',
};

export function TripSummary({ wizardData, currentStep }: TripSummaryProps) {
  const t = useTranslations('customTrip.summary');
  const { basics, interests, budgetStyle } = wizardData;

  const formatDate = (dateStr: string) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  };

  const formatTravelersLabel = () => {
    if (!basics) return '';
    const parts: string[] = [];
    if (basics.adults > 0) {
      parts.push(`${basics.adults} ${basics.adults === 1 ? 'Adult' : 'Adults'}`);
    }
    if (basics.children > 0) {
      parts.push(`${basics.children} ${basics.children === 1 ? 'Child' : 'Children'}`);
    }
    return parts.join(', ');
  };

  const calculateEstimate = () => {
    if (!budgetStyle || !basics) return null;
    const totalTravelers = basics.adults + basics.children;
    const total = budgetStyle.budgetPerPerson * totalTravelers;
    return total.toLocaleString();
  };

  return (
    <div className="bg-cream rounded-xl shadow-sm p-6">
      <h3 className="text-lg font-semibold text-gray-900 mb-4">{t('title')}</h3>

      <div className="space-y-4">
        {/* Travel Dates */}
        {basics && (
          <div className="flex items-start gap-3">
            <Calendar className="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
            <div>
              <p className="text-sm font-medium text-gray-900">
                {formatDate(basics.travelDates.start)} - {formatDate(basics.travelDates.end)}
              </p>
              {basics.datesFlexible && (
                <p className="text-xs text-gray-500">{t('dates_flexible')}</p>
              )}
            </div>
          </div>
        )}

        {/* Travelers */}
        {basics && (
          <div className="flex items-center gap-3">
            <Users className="h-5 w-5 text-primary flex-shrink-0" />
            <p className="text-sm font-medium text-gray-900">{formatTravelersLabel()}</p>
          </div>
        )}

        {/* Duration */}
        {basics && (
          <div className="flex items-center gap-3">
            <Clock className="h-5 w-5 text-primary flex-shrink-0" />
            <p className="text-sm font-medium text-gray-900">
              {basics.durationDays} {basics.durationDays === 1 ? 'day' : 'days'}
            </p>
          </div>
        )}

        {/* Interests */}
        {interests && interests.interests.length > 0 && (
          <div className="flex items-start gap-3">
            <Target className="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
            <div className="flex flex-wrap gap-1">
              {interests.interests.map((interest) => (
                <span
                  key={interest}
                  className="inline-block px-2 py-0.5 bg-primary/10 text-primary text-xs font-medium rounded-full"
                >
                  {INTEREST_LABELS[interest] || interest}
                </span>
              ))}
            </div>
          </div>
        )}

        {/* Budget */}
        {budgetStyle && (
          <>
            <div className="flex items-center gap-3">
              <Wallet className="h-5 w-5 text-primary flex-shrink-0" />
              <p className="text-sm font-medium text-gray-900">
                {budgetStyle.budgetPerPerson.toLocaleString()} EUR / {t('per_person')}
              </p>
            </div>

            <div className="flex items-center gap-3">
              <Building2 className="h-5 w-5 text-primary flex-shrink-0" />
              <p className="text-sm font-medium text-gray-900">
                {ACCOMMODATION_LABELS[budgetStyle.accommodationStyle]}
              </p>
            </div>

            <div className="flex items-center gap-3">
              <Gauge className="h-5 w-5 text-primary flex-shrink-0" />
              <p className="text-sm font-medium text-gray-900">
                {PACE_LABELS[budgetStyle.travelPace]} {t('pace')}
              </p>
            </div>
          </>
        )}
      </div>

      {/* Estimated Total */}
      {calculateEstimate() && (
        <div className="mt-6 pt-4 border-t border-gray-200">
          <div className="flex justify-between items-center">
            <span className="text-sm text-gray-600">{t('estimated_total')}</span>
            <span className="text-xl font-bold text-primary">~{calculateEstimate()} EUR</span>
          </div>
          {basics && basics.adults + basics.children > 1 && (
            <p className="text-xs text-gray-500 text-right mt-1">
              ({basics.adults + basics.children} {t('travelers')})
            </p>
          )}
        </div>
      )}

      {/* Help Section */}
      <div className="mt-6 pt-4 border-t border-gray-200">
        <div className="flex items-center gap-2 text-sm text-gray-600">
          <MessageCircle className="h-4 w-4" />
          <span>{t('questions')}</span>
        </div>
        <a
          href="mailto:contact@djerba.fun"
          className="text-sm text-primary hover:text-primary/80 font-medium"
        >
          {t('chat_with_us')}
        </a>
      </div>
    </div>
  );
}
