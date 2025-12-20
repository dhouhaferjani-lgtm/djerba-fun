'use client';

import { useTranslations } from 'next-intl';
import { Calendar, Clock, Users, Check } from 'lucide-react';
import { cn } from '@/lib/utils/cn';

export type BookingStep = 'date' | 'time' | 'guests';

interface BookingStepIndicatorProps {
  currentStep: BookingStep;
  completedSteps: BookingStep[];
}

export function BookingStepIndicator({ currentStep, completedSteps }: BookingStepIndicatorProps) {
  const t = useTranslations('booking');

  const steps: { key: BookingStep; label: string; icon: React.ReactNode }[] = [
    { key: 'date', label: t('step_date'), icon: <Calendar className="h-4 w-4" /> },
    { key: 'time', label: t('step_time'), icon: <Clock className="h-4 w-4" /> },
    { key: 'guests', label: t('step_guests'), icon: <Users className="h-4 w-4" /> },
  ];

  const getStepStatus = (step: BookingStep) => {
    if (completedSteps.includes(step)) return 'completed';
    if (step === currentStep) return 'active';
    return 'pending';
  };

  return (
    <div className="flex items-center justify-between mb-6 px-2">
      {steps.map((step, index) => {
        const status = getStepStatus(step.key);
        const isLast = index === steps.length - 1;

        return (
          <div key={step.key} className="flex items-center flex-1">
            {/* Step circle */}
            <div className="flex flex-col items-center">
              <div
                className={cn(
                  'w-10 h-10 rounded-full flex items-center justify-center transition-colors',
                  status === 'completed' && 'bg-green-500 text-white',
                  status === 'active' && 'bg-primary text-white',
                  status === 'pending' && 'bg-gray-200 text-gray-400'
                )}
              >
                {status === 'completed' ? <Check className="h-5 w-5" /> : step.icon}
              </div>
              <span
                className={cn(
                  'text-xs mt-1.5 font-medium text-center',
                  status === 'completed' && 'text-green-600',
                  status === 'active' && 'text-primary',
                  status === 'pending' && 'text-gray-400'
                )}
              >
                {step.label}
              </span>
            </div>

            {/* Connector line */}
            {!isLast && (
              <div
                className={cn(
                  'flex-1 h-0.5 mx-2 mt-[-1rem]',
                  completedSteps.includes(step.key) ? 'bg-green-500' : 'bg-gray-200'
                )}
              />
            )}
          </div>
        );
      })}
    </div>
  );
}

BookingStepIndicator.displayName = 'BookingStepIndicator';
