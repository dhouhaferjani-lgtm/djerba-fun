'use client';

import { useState, useEffect } from 'react';
import { useTranslations, useLocale } from 'next-intl';
import { ChevronDown, Check, AlertCircle, Clock } from 'lucide-react';
import { Button } from '@djerba-fun/ui';
import { ParticipantRows } from './ParticipantRows';
import { StatusBadge } from './StatusBadge';
import type { Booking } from '@djerba-fun/schemas';

export interface ParticipantData {
  id: string;
  firstName: string;
  lastName: string;
  email?: string;
  phone?: string;
}

interface ActivityAccordionProps {
  booking: Booking;
  isExpanded: boolean;
  onToggle: () => void;
  isSaved: boolean;
  isSaving: boolean;
  onSave: (data: ParticipantData[]) => Promise<void>;
  formData: ParticipantData[];
  onFormChange: (data: ParticipantData[]) => void;
}

/**
 * Collapsible accordion section for entering participant names per activity.
 * Shows activity header with status badge, expands to reveal participant form.
 */
export function ActivityAccordion({
  booking,
  isExpanded,
  onToggle,
  isSaved,
  isSaving,
  onSave,
  formData,
  onFormChange,
}: ActivityAccordionProps) {
  const t = useTranslations('booking.participants');
  const locale = useLocale();

  // Calculate completion status
  const completedCount =
    formData?.filter((p) => p.firstName.trim() && p.lastName.trim()).length || 0;
  const totalCount = booking.quantity || 1;
  const isComplete = completedCount === totalCount;

  // Get listing title
  const getListingTitle = () => {
    const listing = booking.listing as { title?: string | Record<string, string> } | undefined;
    if (typeof listing === 'object' && listing?.title) {
      if (typeof listing.title === 'object') {
        return listing.title[locale] || Object.values(listing.title)[0];
      }
      return listing.title;
    }
    return t('activity') || 'Activity';
  };

  // Get slot date
  const getSlotDate = () => {
    const slot = booking.availabilitySlot as { date?: string } | undefined;
    if (slot?.date) {
      return new Date(slot.date).toLocaleDateString(locale, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
      });
    }
    return '';
  };

  // Handle save button click
  const handleSave = async () => {
    if (isComplete && !isSaving) {
      await onSave(formData);
    }
  };

  return (
    <div
      className={`border rounded-xl overflow-hidden transition-all duration-200 ${
        isExpanded ? 'border-primary shadow-md' : 'border-neutral-200 hover:border-neutral-300'
      } ${isSaved ? 'border-success/50 bg-success/5' : ''}`}
    >
      {/* Header - always visible */}
      <button
        type="button"
        onClick={onToggle}
        className="w-full p-4 flex items-center justify-between bg-white hover:bg-neutral-50 transition-colors"
      >
        <div className="flex items-center gap-3 text-left">
          {/* Status icon */}
          <div
            className={`w-10 h-10 rounded-full flex items-center justify-center ${
              isSaved
                ? 'bg-success/10 text-success'
                : isComplete
                  ? 'bg-primary/10 text-primary'
                  : 'bg-neutral-100 text-neutral-500'
            }`}
          >
            {isSaved ? (
              <Check className="w-5 h-5" />
            ) : isComplete ? (
              <Check className="w-5 h-5" />
            ) : completedCount > 0 ? (
              <Clock className="w-5 h-5" />
            ) : (
              <AlertCircle className="w-5 h-5" />
            )}
          </div>

          <div>
            <p className="font-semibold text-neutral-900">{getListingTitle()}</p>
            <p className="text-sm text-neutral-500">
              {getSlotDate()} • {totalCount} {t('participants') || 'participants'}
            </p>
          </div>
        </div>

        <div className="flex items-center gap-3">
          <StatusBadge completed={completedCount} total={totalCount} isSaved={isSaved} />
          <ChevronDown
            className={`w-5 h-5 text-neutral-400 transition-transform duration-200 ${
              isExpanded ? 'rotate-180' : ''
            }`}
          />
        </div>
      </button>

      {/* Collapsible content */}
      <div
        className={`overflow-hidden transition-all duration-300 ease-in-out ${
          isExpanded ? 'max-h-[2000px] opacity-100' : 'max-h-0 opacity-0'
        }`}
      >
        <div className="p-4 pt-0 border-t bg-neutral-50">
          <div className="pt-4">
            {/* Booking number reference */}
            <p className="text-xs text-neutral-500 mb-4">
              {t('booking_number') || 'Booking'}: #{booking.bookingNumber || booking.code}
            </p>

            {/* Participant rows */}
            <ParticipantRows participants={formData} onChange={onFormChange} />

            {/* Save button */}
            <div className="mt-6 flex items-center justify-between">
              <p className="text-sm text-neutral-500">
                {completedCount}/{totalCount} {t('filled') || 'filled'}
              </p>
              <Button
                onClick={handleSave}
                disabled={!isComplete || isSaving || isSaved}
                variant={isSaved ? 'outline' : 'primary'}
                className={isSaved ? 'text-success border-success' : ''}
              >
                {isSaving ? (
                  <span className="flex items-center gap-2">
                    <span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                    {t('saving') || 'Saving...'}
                  </span>
                ) : isSaved ? (
                  <span className="flex items-center gap-2">
                    <Check className="w-4 h-4" />
                    {t('saved') || 'Saved'}
                  </span>
                ) : (
                  t('save_activity') || 'Save This Activity'
                )}
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
