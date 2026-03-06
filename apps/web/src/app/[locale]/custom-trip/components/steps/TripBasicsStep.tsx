'use client';

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useTranslations } from 'next-intl';
import { Button } from '@djerba-fun/ui';
import { Calendar, Minus, Plus, ArrowRight } from 'lucide-react';
import type { TripBasicsData } from '../CustomTripWizard';

const tripBasicsSchema = z
  .object({
    startDate: z.string().min(1, 'Start date is required'),
    endDate: z.string().min(1, 'End date is required'),
    datesFlexible: z.boolean(),
    adults: z.number().min(1, 'At least 1 adult is required').max(20),
    children: z.number().min(0).max(10),
    durationDays: z.number().min(3).max(21),
  })
  .refine(
    (data) => {
      const start = new Date(data.startDate);
      const end = new Date(data.endDate);
      return end > start;
    },
    {
      message: 'End date must be after start date',
      path: ['endDate'],
    }
  );

type FormData = z.infer<typeof tripBasicsSchema>;

interface TripBasicsStepProps {
  initialData: TripBasicsData | null;
  onComplete: (data: TripBasicsData) => void;
}

export function TripBasicsStep({ initialData, onComplete }: TripBasicsStepProps) {
  const t = useTranslations('customTrip.tripBasics');

  const today = new Date().toISOString().split('T')[0];

  const {
    register,
    handleSubmit,
    watch,
    setValue,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(tripBasicsSchema),
    defaultValues: {
      startDate: initialData?.travelDates.start || '',
      endDate: initialData?.travelDates.end || '',
      datesFlexible: initialData?.datesFlexible || false,
      adults: initialData?.adults || 1,
      children: initialData?.children || 0,
      durationDays: initialData?.durationDays || 7,
    },
  });

  const adults = watch('adults');
  const children = watch('children');
  const durationDays = watch('durationDays');

  const handleCounterChange = (field: 'adults' | 'children', increment: boolean) => {
    const currentValue = field === 'adults' ? adults : children;
    const min = field === 'adults' ? 1 : 0;
    const max = field === 'adults' ? 20 : 10;

    const newValue = increment ? Math.min(currentValue + 1, max) : Math.max(currentValue - 1, min);

    setValue(field, newValue);
  };

  const handleDurationChange = (value: number) => {
    setValue('durationDays', value);
  };

  const onSubmit = (data: FormData) => {
    onComplete({
      travelDates: {
        start: data.startDate,
        end: data.endDate,
      },
      datesFlexible: data.datesFlexible,
      adults: data.adults,
      children: data.children,
      durationDays: data.durationDays,
    });
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-8">
      <div>
        <h2 className="text-2xl font-bold text-gray-900 mb-2">{t('title')}</h2>
        <p className="text-gray-600">{t('subtitle')}</p>
      </div>

      {/* Travel Dates */}
      <div className="space-y-4">
        <label className="block text-sm font-semibold text-gray-900">{t('when_travel')}</label>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm text-gray-600 mb-1">{t('start_date')}</label>
            <div className="relative">
              <input
                type="date"
                min={today}
                {...register('startDate')}
                className={`
                  w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary
                  ${errors.startDate ? 'border-red-500' : 'border-gray-300'}
                `}
              />
              <Calendar className="absolute right-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" />
            </div>
            {errors.startDate && (
              <p className="text-red-500 text-sm mt-1">{errors.startDate.message}</p>
            )}
          </div>
          <div>
            <label className="block text-sm text-gray-600 mb-1">{t('end_date')}</label>
            <div className="relative">
              <input
                type="date"
                min={today}
                {...register('endDate')}
                className={`
                  w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary
                  ${errors.endDate ? 'border-red-500' : 'border-gray-300'}
                `}
              />
              <Calendar className="absolute right-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" />
            </div>
            {errors.endDate && (
              <p className="text-red-500 text-sm mt-1">{errors.endDate.message}</p>
            )}
          </div>
        </div>

        {/* Flexible Dates Toggle */}
        <label className="flex items-center gap-3 cursor-pointer">
          <input
            type="checkbox"
            {...register('datesFlexible')}
            className="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary"
          />
          <span className="text-sm text-gray-700">{t('dates_flexible')}</span>
        </label>
      </div>

      {/* Travelers */}
      <div className="space-y-4">
        <label className="block text-sm font-semibold text-gray-900">
          {t('how_many_travelers')}
        </label>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          {/* Adults Counter */}
          <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
              <p className="font-medium text-gray-900">{t('adults')}</p>
              <p className="text-sm text-gray-500">{t('adults_hint')}</p>
            </div>
            <div className="flex items-center gap-3">
              <button
                type="button"
                onClick={() => handleCounterChange('adults', false)}
                disabled={adults <= 1}
                className={`
                  w-10 h-10 rounded-full flex items-center justify-center border-2 transition-colors
                  ${
                    adults <= 1
                      ? 'border-gray-200 text-gray-300 cursor-not-allowed'
                      : 'border-gray-300 text-gray-600 hover:border-primary hover:text-primary'
                  }
                `}
              >
                <Minus className="h-4 w-4" />
              </button>
              <span className="w-8 text-center font-semibold text-lg">{adults}</span>
              <button
                type="button"
                onClick={() => handleCounterChange('adults', true)}
                disabled={adults >= 20}
                className={`
                  w-10 h-10 rounded-full flex items-center justify-center border-2 transition-colors
                  ${
                    adults >= 20
                      ? 'border-gray-200 text-gray-300 cursor-not-allowed'
                      : 'border-gray-300 text-gray-600 hover:border-primary hover:text-primary'
                  }
                `}
              >
                <Plus className="h-4 w-4" />
              </button>
            </div>
          </div>

          {/* Children Counter */}
          <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
              <p className="font-medium text-gray-900">{t('children')}</p>
              <p className="text-sm text-gray-500">{t('children_hint')}</p>
            </div>
            <div className="flex items-center gap-3">
              <button
                type="button"
                onClick={() => handleCounterChange('children', false)}
                disabled={children <= 0}
                className={`
                  w-10 h-10 rounded-full flex items-center justify-center border-2 transition-colors
                  ${
                    children <= 0
                      ? 'border-gray-200 text-gray-300 cursor-not-allowed'
                      : 'border-gray-300 text-gray-600 hover:border-primary hover:text-primary'
                  }
                `}
              >
                <Minus className="h-4 w-4" />
              </button>
              <span className="w-8 text-center font-semibold text-lg">{children}</span>
              <button
                type="button"
                onClick={() => handleCounterChange('children', true)}
                disabled={children >= 10}
                className={`
                  w-10 h-10 rounded-full flex items-center justify-center border-2 transition-colors
                  ${
                    children >= 10
                      ? 'border-gray-200 text-gray-300 cursor-not-allowed'
                      : 'border-gray-300 text-gray-600 hover:border-primary hover:text-primary'
                  }
                `}
              >
                <Plus className="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
        {errors.adults && <p className="text-red-500 text-sm">{errors.adults.message}</p>}
      </div>

      {/* Duration Slider */}
      <div className="space-y-4">
        <label className="block text-sm font-semibold text-gray-900">{t('trip_duration')}</label>
        <div className="space-y-2">
          <input
            type="range"
            min={3}
            max={21}
            value={durationDays}
            onChange={(e) => handleDurationChange(parseInt(e.target.value))}
            className="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-primary"
          />
          <div className="flex justify-between text-sm text-gray-500">
            <span>3 {t('days')}</span>
            <span className="font-semibold text-primary text-lg">
              {durationDays} {t('days')}
            </span>
            <span>21 {t('days')}</span>
          </div>
        </div>
      </div>

      {/* Submit Button */}
      <div className="flex justify-end pt-4">
        <Button type="submit" variant="primary" size="lg" className="min-w-[200px]">
          {t('continue')}
          <ArrowRight className="h-5 w-5 ml-2" />
        </Button>
      </div>
    </form>
  );
}
