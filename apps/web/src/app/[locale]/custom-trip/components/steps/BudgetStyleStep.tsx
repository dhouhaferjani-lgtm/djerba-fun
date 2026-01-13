'use client';

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useTranslations } from 'next-intl';
import { Button } from '@go-adventure/ui';
import {
  ArrowRight,
  ArrowLeft,
  Tent,
  Hotel,
  Crown,
  Turtle,
  Gauge,
  Rocket,
  Heart,
  Cake,
  Gift,
  Sparkles,
} from 'lucide-react';
import type { BudgetStyleData } from '../CustomTripWizard';

const budgetStyleSchema = z.object({
  budgetPerPerson: z.number().min(150).max(3000),
  accommodationStyle: z.enum(['budget', 'mid-range', 'luxury']),
  travelPace: z.enum(['relaxed', 'moderate', 'active']),
  specialOccasions: z.array(z.string()),
});

type FormData = z.infer<typeof budgetStyleSchema>;

interface BudgetStyleStepProps {
  initialData: BudgetStyleData | null;
  onComplete: (data: BudgetStyleData) => void;
  onBack: () => void;
}

const ACCOMMODATION_OPTIONS = [
  { id: 'budget', icon: <Tent className="h-6 w-6" />, priceRange: '150-450 EUR' },
  { id: 'mid-range', icon: <Hotel className="h-6 w-6" />, priceRange: '450-1,200 EUR' },
  { id: 'luxury', icon: <Crown className="h-6 w-6" />, priceRange: '1,200-3,000 EUR' },
] as const;

const PACE_OPTIONS = [
  { id: 'relaxed', icon: <Turtle className="h-6 w-6" /> },
  { id: 'moderate', icon: <Gauge className="h-6 w-6" /> },
  { id: 'active', icon: <Rocket className="h-6 w-6" /> },
] as const;

const OCCASION_OPTIONS = [
  { id: 'honeymoon', icon: <Heart className="h-5 w-5" /> },
  { id: 'birthday', icon: <Cake className="h-5 w-5" /> },
  { id: 'anniversary', icon: <Gift className="h-5 w-5" /> },
  { id: 'other', icon: <Sparkles className="h-5 w-5" /> },
] as const;

export function BudgetStyleStep({ initialData, onComplete, onBack }: BudgetStyleStepProps) {
  const t = useTranslations('customTrip.budgetStyle');

  const {
    watch,
    setValue,
    handleSubmit,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(budgetStyleSchema),
    defaultValues: {
      budgetPerPerson: initialData?.budgetPerPerson || 600,
      accommodationStyle: initialData?.accommodationStyle || 'mid-range',
      travelPace: initialData?.travelPace || 'moderate',
      specialOccasions: initialData?.specialOccasions || [],
    },
  });

  const budgetPerPerson = watch('budgetPerPerson');
  const accommodationStyle = watch('accommodationStyle');
  const travelPace = watch('travelPace');
  const specialOccasions = watch('specialOccasions');

  const handleBudgetChange = (value: number) => {
    setValue('budgetPerPerson', value);
  };

  const toggleOccasion = (occasionId: string) => {
    const current = specialOccasions;
    if (current.includes(occasionId)) {
      setValue(
        'specialOccasions',
        current.filter((id) => id !== occasionId)
      );
    } else {
      setValue('specialOccasions', [...current, occasionId]);
    }
  };

  const onSubmit = (data: FormData) => {
    onComplete({
      budgetPerPerson: data.budgetPerPerson,
      accommodationStyle: data.accommodationStyle,
      travelPace: data.travelPace,
      specialOccasions: data.specialOccasions,
    });
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-8">
      <div>
        <h2 className="text-2xl font-bold text-gray-900 mb-2">{t('title')}</h2>
        <p className="text-gray-600">{t('subtitle')}</p>
      </div>

      {/* Budget Slider */}
      <div className="space-y-4">
        <label className="block text-sm font-semibold text-gray-900">
          {t('budget_per_person')}
        </label>
        <div className="space-y-2">
          <input
            type="range"
            min={150}
            max={3000}
            step={50}
            value={budgetPerPerson}
            onChange={(e) => handleBudgetChange(parseInt(e.target.value))}
            className="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-primary"
          />
          <div className="flex justify-between text-sm text-gray-500">
            <span>150 EUR</span>
            <span className="font-semibold text-primary text-xl">
              {budgetPerPerson.toLocaleString()} EUR
            </span>
            <span>3,000 EUR</span>
          </div>
        </div>
      </div>

      {/* Accommodation Style */}
      <div className="space-y-4">
        <label className="block text-sm font-semibold text-gray-900">
          {t('accommodation_style')}
        </label>
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          {ACCOMMODATION_OPTIONS.map((option) => {
            const isSelected = accommodationStyle === option.id;
            return (
              <button
                key={option.id}
                type="button"
                onClick={() => setValue('accommodationStyle', option.id)}
                className={`
                  relative p-4 rounded-xl border-2 transition-all duration-200
                  flex flex-col items-center justify-center gap-2 min-h-[140px]
                  ${
                    isSelected
                      ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
                      : 'border-gray-200 hover:border-gray-300 bg-white'
                  }
                `}
              >
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
                  {option.icon}
                </div>
                <span
                  className={`
                    font-medium text-center
                    ${isSelected ? 'text-primary' : 'text-gray-700'}
                  `}
                >
                  {t(`accommodation.${option.id}`)}
                </span>
                <span className="text-xs text-gray-500">{option.priceRange}</span>
              </button>
            );
          })}
        </div>
      </div>

      {/* Travel Pace */}
      <div className="space-y-4">
        <label className="block text-sm font-semibold text-gray-900">{t('travel_pace')}</label>
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          {PACE_OPTIONS.map((option) => {
            const isSelected = travelPace === option.id;
            return (
              <button
                key={option.id}
                type="button"
                onClick={() => setValue('travelPace', option.id)}
                className={`
                  relative p-4 rounded-xl border-2 transition-all duration-200
                  flex flex-col items-center justify-center gap-2 min-h-[120px]
                  ${
                    isSelected
                      ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
                      : 'border-gray-200 hover:border-gray-300 bg-white'
                  }
                `}
              >
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
                  {option.icon}
                </div>
                <span
                  className={`
                    font-medium text-center
                    ${isSelected ? 'text-primary' : 'text-gray-700'}
                  `}
                >
                  {t(`pace.${option.id}`)}
                </span>
                <span className="text-xs text-gray-500 text-center">
                  {t(`pace.${option.id}_desc`)}
                </span>
              </button>
            );
          })}
        </div>
      </div>

      {/* Special Occasions */}
      <div className="space-y-4">
        <label className="block text-sm font-semibold text-gray-900">
          {t('special_occasion')}
          <span className="font-normal text-gray-500 ml-2">{t('optional')}</span>
        </label>
        <div className="flex flex-wrap gap-3">
          {OCCASION_OPTIONS.map((option) => {
            const isSelected = specialOccasions.includes(option.id);
            return (
              <button
                key={option.id}
                type="button"
                onClick={() => toggleOccasion(option.id)}
                className={`
                  px-4 py-2 rounded-full border-2 transition-all duration-200
                  flex items-center gap-2
                  ${
                    isSelected
                      ? 'border-primary bg-primary/10 text-primary'
                      : 'border-gray-200 hover:border-gray-300 text-gray-700'
                  }
                `}
              >
                {option.icon}
                <span className="text-sm font-medium">{t(`occasions.${option.id}`)}</span>
              </button>
            );
          })}
        </div>
      </div>

      {/* Navigation Buttons */}
      <div className="flex justify-between pt-4">
        <Button type="button" variant="outline" size="lg" onClick={onBack}>
          <ArrowLeft className="h-5 w-5 mr-2" />
          {t('back')}
        </Button>
        <Button type="submit" variant="primary" size="lg" className="min-w-[200px]">
          {t('continue')}
          <ArrowRight className="h-5 w-5 ml-2" />
        </Button>
      </div>
    </form>
  );
}
