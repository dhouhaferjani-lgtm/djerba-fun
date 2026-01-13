'use client';

import { Check } from 'lucide-react';
import type { WizardStep } from './CustomTripWizard';

interface ProgressBarProps {
  steps: { key: WizardStep; label: string }[];
  currentStep: WizardStep;
}

export function ProgressBar({ steps, currentStep }: ProgressBarProps) {
  const currentStepIndex = steps.findIndex((s) => s.key === currentStep);

  return (
    <div className="max-w-3xl mx-auto">
      {/* Desktop Progress Bar */}
      <div className="hidden md:block">
        <div className="flex items-center justify-between">
          {steps.map((step, index) => {
            const isCompleted = index < currentStepIndex;
            const isCurrent = index === currentStepIndex;

            return (
              <div key={step.key} className="flex items-center flex-1 last:flex-none">
                {/* Step Circle */}
                <div className="flex flex-col items-center">
                  <div
                    className={`
                      w-10 h-10 rounded-full flex items-center justify-center font-semibold text-sm
                      transition-all duration-300
                      ${isCompleted ? 'bg-primary text-white' : ''}
                      ${isCurrent ? 'bg-primary text-white ring-4 ring-primary/20' : ''}
                      ${!isCompleted && !isCurrent ? 'bg-gray-200 text-gray-500' : ''}
                    `}
                  >
                    {isCompleted ? <Check className="h-5 w-5" /> : index + 1}
                  </div>
                  <span
                    className={`
                      mt-2 text-sm font-medium
                      ${isCurrent ? 'text-primary' : ''}
                      ${isCompleted ? 'text-gray-700' : ''}
                      ${!isCompleted && !isCurrent ? 'text-gray-400' : ''}
                    `}
                  >
                    {step.label}
                  </span>
                </div>

                {/* Connector Line */}
                {index < steps.length - 1 && (
                  <div className="flex-1 mx-4">
                    <div
                      className={`
                        h-1 rounded-full transition-all duration-300
                        ${index < currentStepIndex ? 'bg-primary' : 'bg-gray-200'}
                      `}
                    />
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </div>

      {/* Mobile Progress Bar */}
      <div className="md:hidden">
        <div className="flex items-center justify-between mb-2">
          <span className="text-sm font-medium text-gray-700">
            Step {currentStepIndex + 1} of {steps.length}
          </span>
          <span className="text-sm font-medium text-primary">{steps[currentStepIndex]?.label}</span>
        </div>
        <div className="h-2 bg-gray-200 rounded-full overflow-hidden">
          <div
            className="h-full bg-primary rounded-full transition-all duration-300"
            style={{ width: `${((currentStepIndex + 1) / steps.length) * 100}%` }}
          />
        </div>
      </div>
    </div>
  );
}
