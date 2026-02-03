'use client';

import { useState, useEffect } from 'react';
import { useTranslations } from 'next-intl';
import { Users, ListChecks, AlertCircle } from 'lucide-react';
import { Button } from '@go-adventure/ui';
import type { Booking } from '@go-adventure/schemas';

interface ParticipantModeSelectorProps {
  bookings: Booking[];
  onModeSelect: (mode: 'same' | 'different') => void;
}

/**
 * ParticipantModeSelector Component
 *
 * Lets users choose between:
 * - "Same participants for all tours" (fill once, apply to all)
 * - "Different participants per tour" (fill each booking separately)
 *
 * If bookings have different quantities, shows a popup and auto-selects "different" mode.
 */
export function ParticipantModeSelector({ bookings, onModeSelect }: ParticipantModeSelectorProps) {
  const t = useTranslations('booking.participants');
  const [selectedMode, setSelectedMode] = useState<'same' | 'different' | null>(null);
  const [showMismatchPopup, setShowMismatchPopup] = useState(false);

  // Calculate total participants
  const totalParticipants = bookings.reduce((sum, b) => sum + (b.quantity || 1), 0);

  // Check if all bookings have the same quantity
  const quantities = bookings.map((b) => b.quantity || 1);
  const allSameQuantity = quantities.length > 0 && quantities.every((q) => q === quantities[0]);

  // If quantities differ and there are multiple bookings, show popup on mount
  useEffect(() => {
    if (!allSameQuantity && bookings.length > 1) {
      setShowMismatchPopup(true);
    }
  }, [allSameQuantity, bookings.length]);

  const handleMismatchAcknowledge = () => {
    setShowMismatchPopup(false);
    onModeSelect('different'); // Auto-select different mode
  };

  const handleContinue = () => {
    if (selectedMode) {
      onModeSelect(selectedMode);
    }
  };

  // Quantity mismatch popup
  if (showMismatchPopup) {
    return (
      <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div className="bg-white rounded-xl p-6 max-w-md w-full shadow-xl">
          <div className="text-center">
            <div className="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <AlertCircle className="h-8 w-8 text-amber-600" />
            </div>
            <h3 className="text-lg font-semibold text-neutral-900 mb-2">
              {t('quantities_differ_title')}
            </h3>
            <p className="text-neutral-600 mb-6">{t('quantities_differ_message')}</p>
            <Button onClick={handleMismatchAcknowledge} className="w-full">
              {t('ok_continue')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-lg mx-auto space-y-6">
      {/* Header */}
      <div className="text-center">
        <h2 className="text-2xl font-bold text-neutral-900">{t('enter_participant_names')}</h2>
        <p className="text-neutral-600 mt-2">
          {t('you_have_bookings', { count: bookings.length, participants: totalParticipants })}
        </p>
      </div>

      {/* Mode Selection */}
      <div className="space-y-4">
        {/* Same for all option - only show if quantities match */}
        {allSameQuantity && (
          <button
            onClick={() => setSelectedMode('same')}
            className={`w-full p-4 rounded-xl border-2 transition-all text-left ${
              selectedMode === 'same'
                ? 'border-primary bg-primary/5 shadow-sm'
                : 'border-neutral-200 hover:border-primary/50'
            }`}
          >
            <div className="flex items-start gap-4">
              <div
                className={`flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center ${
                  selectedMode === 'same'
                    ? 'bg-primary text-white'
                    : 'bg-neutral-100 text-neutral-500'
                }`}
              >
                <Users className="h-5 w-5" />
              </div>
              <div className="flex-1">
                <p className="font-semibold text-neutral-900">{t('same_for_all_tours')}</p>
                <p className="text-sm text-neutral-600 mt-1">{t('same_for_all_description')}</p>
              </div>
              <div
                className={`w-5 h-5 rounded-full border-2 flex-shrink-0 mt-0.5 ${
                  selectedMode === 'same' ? 'border-primary bg-primary' : 'border-neutral-300'
                }`}
              >
                {selectedMode === 'same' && (
                  <svg className="w-full h-full text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      fillRule="evenodd"
                      d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                      clipRule="evenodd"
                    />
                  </svg>
                )}
              </div>
            </div>
          </button>
        )}

        {/* Different per tour option */}
        <button
          onClick={() => setSelectedMode('different')}
          className={`w-full p-4 rounded-xl border-2 transition-all text-left ${
            selectedMode === 'different'
              ? 'border-primary bg-primary/5 shadow-sm'
              : 'border-neutral-200 hover:border-primary/50'
          }`}
        >
          <div className="flex items-start gap-4">
            <div
              className={`flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center ${
                selectedMode === 'different'
                  ? 'bg-primary text-white'
                  : 'bg-neutral-100 text-neutral-500'
              }`}
            >
              <ListChecks className="h-5 w-5" />
            </div>
            <div className="flex-1">
              <p className="font-semibold text-neutral-900">{t('different_per_tour')}</p>
              <p className="text-sm text-neutral-600 mt-1">{t('different_per_tour_description')}</p>
            </div>
            <div
              className={`w-5 h-5 rounded-full border-2 flex-shrink-0 mt-0.5 ${
                selectedMode === 'different' ? 'border-primary bg-primary' : 'border-neutral-300'
              }`}
            >
              {selectedMode === 'different' && (
                <svg className="w-full h-full text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fillRule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clipRule="evenodd"
                  />
                </svg>
              )}
            </div>
          </div>
        </button>
      </div>

      {/* Continue Button */}
      <Button onClick={handleContinue} disabled={!selectedMode} className="w-full" size="lg">
        {t('continue')}
      </Button>
    </div>
  );
}
