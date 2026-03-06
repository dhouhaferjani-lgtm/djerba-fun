'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { Button } from '@djerba-fun/ui';
import {
  ArrowRight,
  ArrowLeft,
  Building2,
  Mountain,
  Palmtree,
  UtensilsCrossed,
  Footprints,
  Camera,
  Music,
  Film,
} from 'lucide-react';
import type { InterestsData } from '../CustomTripWizard';

interface InterestsStepProps {
  initialData: InterestsData | null;
  onComplete: (data: InterestsData) => void;
  onBack: () => void;
}

interface Interest {
  id: string;
  icon: React.ReactNode;
  labelKey: string;
}

const INTERESTS: Interest[] = [
  { id: 'history-culture', icon: <Building2 className="h-6 w-6" />, labelKey: 'history_culture' },
  {
    id: 'desert-adventures',
    icon: <Mountain className="h-6 w-6" />,
    labelKey: 'desert_adventures',
  },
  { id: 'beach-relaxation', icon: <Palmtree className="h-6 w-6" />, labelKey: 'beach_relaxation' },
  {
    id: 'food-gastronomy',
    icon: <UtensilsCrossed className="h-6 w-6" />,
    labelKey: 'food_gastronomy',
  },
  { id: 'hiking-nature', icon: <Footprints className="h-6 w-6" />, labelKey: 'hiking_nature' },
  { id: 'photography', icon: <Camera className="h-6 w-6" />, labelKey: 'photography' },
  { id: 'local-festivals', icon: <Music className="h-6 w-6" />, labelKey: 'local_festivals' },
  { id: 'star-wars-sites', icon: <Film className="h-6 w-6" />, labelKey: 'star_wars_sites' },
];

const MAX_SELECTIONS = 5;

export function InterestsStep({ initialData, onComplete, onBack }: InterestsStepProps) {
  const t = useTranslations('customTrip.interests');
  const [selectedInterests, setSelectedInterests] = useState<string[]>(
    initialData?.interests || []
  );
  const [error, setError] = useState<string | null>(null);

  const toggleInterest = (interestId: string) => {
    setError(null);

    if (selectedInterests.includes(interestId)) {
      // Remove interest
      setSelectedInterests((prev) => prev.filter((id) => id !== interestId));
    } else {
      // Add interest (if under max)
      if (selectedInterests.length >= MAX_SELECTIONS) {
        setError(t('max_selections_error', { max: MAX_SELECTIONS }));
        return;
      }
      setSelectedInterests((prev) => [...prev, interestId]);
    }
  };

  const handleSubmit = () => {
    if (selectedInterests.length === 0) {
      setError(t('min_selection_error'));
      return;
    }
    onComplete({ interests: selectedInterests });
  };

  return (
    <div className="space-y-8">
      <div>
        <h2 className="text-2xl font-bold text-gray-900 mb-2">{t('title')}</h2>
        <p className="text-gray-600">{t('subtitle')}</p>
        <p className="text-sm text-gray-500 mt-1">
          {t('selection_hint', { current: selectedInterests.length, max: MAX_SELECTIONS })}
        </p>
      </div>

      {/* Interest Grid */}
      <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        {INTERESTS.map((interest) => {
          const isSelected = selectedInterests.includes(interest.id);
          const isDisabled = !isSelected && selectedInterests.length >= MAX_SELECTIONS;

          return (
            <button
              key={interest.id}
              type="button"
              onClick={() => toggleInterest(interest.id)}
              disabled={isDisabled}
              className={`
                relative p-4 rounded-xl border-2 transition-all duration-200
                flex flex-col items-center justify-center gap-2 min-h-[120px]
                ${
                  isSelected
                    ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
                    : 'border-gray-200 hover:border-gray-300 bg-white'
                }
                ${isDisabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}
              `}
            >
              {/* Selection Indicator */}
              {isSelected && (
                <div className="absolute top-2 right-2 w-6 h-6 bg-primary rounded-full flex items-center justify-center">
                  <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      fillRule="evenodd"
                      d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                      clipRule="evenodd"
                    />
                  </svg>
                </div>
              )}

              <div
                className={`
                  p-3 rounded-full
                  ${isSelected ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600'}
                `}
              >
                {interest.icon}
              </div>
              <span
                className={`
                  text-sm font-medium text-center
                  ${isSelected ? 'text-primary' : 'text-gray-700'}
                `}
              >
                {t(`options.${interest.labelKey}`)}
              </span>
            </button>
          );
        })}
      </div>

      {/* Error Message */}
      {error && <p className="text-red-500 text-sm text-center">{error}</p>}

      {/* Navigation Buttons */}
      <div className="flex justify-between pt-4">
        <Button type="button" variant="outline" size="lg" onClick={onBack}>
          <ArrowLeft className="h-5 w-5 mr-2" />
          {t('back')}
        </Button>
        <Button
          type="button"
          variant="primary"
          size="lg"
          onClick={handleSubmit}
          disabled={selectedInterests.length === 0}
          className="min-w-[200px]"
        >
          {t('continue')}
          <ArrowRight className="h-5 w-5 ml-2" />
        </Button>
      </div>
    </div>
  );
}
