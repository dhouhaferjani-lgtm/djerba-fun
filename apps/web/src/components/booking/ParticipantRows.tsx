'use client';

import { useTranslations } from 'next-intl';
import { Check, Mail, Phone } from 'lucide-react';
import type { ParticipantData } from './ActivityAccordion';

interface ParticipantRowsProps {
  participants: ParticipantData[];
  onChange: (participants: ParticipantData[]) => void;
}

/**
 * Inline participant entry rows for accordion view.
 * Shows all fields (first name, last name, email, phone) in a 2x2 grid - matching dashboard layout.
 * Email and phone are optional.
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
    <div className="space-y-4">
      {participants.map((participant, index) => {
        const complete = isRowComplete(participant);

        return (
          <div
            key={participant.id}
            className={`p-4 rounded-lg border transition-colors ${
              complete ? 'border-success/30 bg-success/5' : 'border-neutral-200 bg-white'
            }`}
          >
            {/* Header with row number and status */}
            <div className="flex items-center gap-3 mb-3">
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
              <span className="text-sm font-medium text-neutral-700">
                {t('participant') || 'Participant'} {index + 1}
              </span>
            </div>

            {/* 2x2 Grid: Names (required) on top, Contact (optional) below */}
            <div className="grid grid-cols-2 gap-3">
              {/* First Name */}
              <input
                type="text"
                placeholder={t('first_name_placeholder') || 'First name *'}
                value={participant.firstName}
                onChange={(e) => handleChange(index, 'firstName', e.target.value)}
                className={`w-full px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary ${
                  participant.firstName.trim()
                    ? 'border-success/30 bg-success/5'
                    : 'border-neutral-200'
                }`}
              />

              {/* Last Name */}
              <input
                type="text"
                placeholder={t('last_name_placeholder') || 'Last name *'}
                value={participant.lastName}
                onChange={(e) => handleChange(index, 'lastName', e.target.value)}
                className={`w-full px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary ${
                  participant.lastName.trim()
                    ? 'border-success/30 bg-success/5'
                    : 'border-neutral-200'
                }`}
              />

              {/* Email (optional) */}
              <div className="relative">
                <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400" />
                <input
                  type="email"
                  placeholder={t('email_placeholder') || 'Email (optional)'}
                  value={participant.email || ''}
                  onChange={(e) => handleChange(index, 'email', e.target.value)}
                  className={`w-full pl-9 pr-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary ${
                    participant.email?.trim()
                      ? 'border-primary/30 bg-primary/5'
                      : 'border-neutral-200'
                  }`}
                />
              </div>

              {/* Phone (optional) */}
              <div className="relative">
                <Phone className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400" />
                <input
                  type="tel"
                  placeholder={t('phone_placeholder') || 'Phone (optional)'}
                  value={participant.phone || ''}
                  onChange={(e) => handleChange(index, 'phone', e.target.value)}
                  className={`w-full pl-9 pr-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary ${
                    participant.phone?.trim()
                      ? 'border-primary/30 bg-primary/5'
                      : 'border-neutral-200'
                  }`}
                />
              </div>
            </div>
          </div>
        );
      })}

      {/* Helper text */}
      <p className="text-xs text-neutral-500 mt-2">
        {t('names_helper_extended') ||
          'Enter the first and last name for each participant. Names will appear on vouchers. Email and phone are optional.'}
      </p>
    </div>
  );
}
