'use client';

import { useTranslations } from 'next-intl';
import { User, Check } from 'lucide-react';
import type { ParticipantData } from './ActivityAccordion';

interface ParticipantRowsProps {
  participants: ParticipantData[];
  onChange: (participants: ParticipantData[]) => void;
}

/**
 * Inline participant entry rows for accordion view.
 * Compact design with essential fields only (first name, last name).
 */
export function ParticipantRows({ participants, onChange }: ParticipantRowsProps) {
  const t = useTranslations('booking.participants');

  const handleChange = (index: number, field: keyof ParticipantData, value: string) => {
    const updated = [...participants];
    updated[index] = { ...updated[index], [field]: value };
    onChange(updated);
  };

  const isRowComplete = (participant: ParticipantData) => {
    return participant.firstName.trim() !== '' && participant.lastName.trim() !== '';
  };

  return (
    <div className="space-y-3">
      {participants.map((participant, index) => {
        const complete = isRowComplete(participant);

        return (
          <div
            key={participant.id}
            className={`flex items-center gap-3 p-3 rounded-lg border transition-colors ${
              complete ? 'border-success/30 bg-success/5' : 'border-neutral-200 bg-white'
            }`}
          >
            {/* Row number / status indicator */}
            <div
              className={`w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 ${
                complete ? 'bg-success text-white' : 'bg-neutral-100 text-neutral-500'
              }`}
            >
              {complete ? (
                <Check className="w-4 h-4" />
              ) : (
                <span className="text-sm font-medium">{index + 1}</span>
              )}
            </div>

            {/* Name inputs */}
            <div className="flex-1 grid grid-cols-2 gap-2">
              <input
                type="text"
                placeholder={t('first_name_placeholder') || 'First name'}
                value={participant.firstName}
                onChange={(e) => handleChange(index, 'firstName', e.target.value)}
                className={`w-full px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary ${
                  participant.firstName.trim()
                    ? 'border-success/30 bg-success/5'
                    : 'border-neutral-200'
                }`}
              />
              <input
                type="text"
                placeholder={t('last_name_placeholder') || 'Last name'}
                value={participant.lastName}
                onChange={(e) => handleChange(index, 'lastName', e.target.value)}
                className={`w-full px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary ${
                  participant.lastName.trim()
                    ? 'border-success/30 bg-success/5'
                    : 'border-neutral-200'
                }`}
              />
            </div>

            {/* Optional: Expand button for email/phone */}
            {complete && (
              <div className="flex-shrink-0">
                <button
                  type="button"
                  onClick={() => {
                    // Could expand to show email/phone fields
                    // For now just visual indicator
                  }}
                  className="p-1 text-neutral-400 hover:text-neutral-600"
                  title={t('add_contact_info') || 'Add contact info'}
                >
                  <User className="w-4 h-4" />
                </button>
              </div>
            )}
          </div>
        );
      })}

      {/* Helper text */}
      <p className="text-xs text-neutral-500 mt-2">
        {t('names_helper') ||
          'Enter the first and last name for each participant. Names will appear on vouchers.'}
      </p>
    </div>
  );
}
