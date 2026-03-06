'use client';

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useTranslations } from 'next-intl';
import { Button } from '@djerba-fun/ui';
import { User, Check, AlertCircle } from 'lucide-react';
import type { Booking } from '@djerba-fun/schemas';

// Zod schema for participant form validation
const participantSchema = z.object({
  id: z.string(), // Can be UUID or temp-{index} for new participants
  firstName: z.string().min(1, 'First name is required').max(100),
  lastName: z.string().min(1, 'Last name is required').max(100),
  email: z.string().email('Invalid email').optional().or(z.literal('')),
  phone: z.string().optional(),
});

const participantsFormSchema = z.object({
  participants: z.array(participantSchema).min(1),
});

type ParticipantsFormData = z.infer<typeof participantsFormSchema>;

interface ParticipantsFormProps {
  booking: Booking;
  onSubmit: (data: ParticipantsFormData) => Promise<void>;
  isLoading?: boolean;
}

/**
 * Form for collecting participant names post-payment
 * Shows dynamic fields based on booking quantity
 * Validates with Zod and submits to booking participants endpoint
 */
export function ParticipantsForm({ booking, onSubmit, isLoading = false }: ParticipantsFormProps) {
  const t = useTranslations('booking.participants');
  const [submitting, setSubmitting] = useState(false);

  // Get participants from booking (typed as unknown[] in schema, so we cast)
  const existingParticipants = (booking.participants || []) as Array<{
    id?: string;
    firstName?: string;
    lastName?: string;
    email?: string;
    phone?: string;
  }>;
  const quantity = booking.quantity || existingParticipants.length || 1;

  // Generate placeholder participants if none exist (common after cart checkout)
  // This ensures form fields are rendered based on booking quantity
  const participants =
    existingParticipants.length > 0
      ? existingParticipants
      : Array.from({ length: quantity }, (_, i) => ({
          id: `temp-${i}`,
          firstName: '',
          lastName: '',
          email: '',
          phone: '',
        }));

  // Initialize form with existing participant data
  const {
    register,
    handleSubmit,
    formState: { errors },
    watch,
  } = useForm<ParticipantsFormData>({
    resolver: zodResolver(participantsFormSchema),
    defaultValues: {
      participants: participants.map((p) => ({
        id: p.id || '',
        firstName: p.firstName || '',
        lastName: p.lastName || '',
        email: p.email || '',
        phone: p.phone || '',
      })),
    },
  });

  // Watch form values to show completion status
  const watchedParticipants = watch('participants');

  // Check if a participant is complete
  const isParticipantComplete = (index: number) => {
    const participant = watchedParticipants?.[index];
    return participant?.firstName && participant?.lastName;
  };

  // Calculate completion percentage
  const completionCount =
    watchedParticipants?.filter((p) => p?.firstName && p?.lastName).length || 0;
  const completionPercentage = Math.round((completionCount / quantity) * 100);

  const handleFormSubmit = async (data: ParticipantsFormData) => {
    setSubmitting(true);
    try {
      await onSubmit(data);
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header with completion status */}
      <div className="border-b pb-4">
        <div className="flex items-center justify-between mb-2">
          <h2 className="text-2xl font-bold text-gray-900">
            {t('title') || 'Participant Information'}
          </h2>
          <div className="flex items-center gap-2">
            {completionPercentage === 100 ? (
              <div className="flex items-center gap-2 text-success">
                <Check className="w-5 h-5" />
                <span className="text-sm font-medium">Complete</span>
              </div>
            ) : (
              <div className="flex items-center gap-2 text-gray-600">
                <AlertCircle className="w-5 h-5" />
                <span className="text-sm font-medium">
                  {completionCount} of {quantity} complete
                </span>
              </div>
            )}
          </div>
        </div>
        <p className="text-sm text-gray-600">
          {t('subtitle') || 'Enter the names of all participants for this booking.'}
        </p>

        {/* Progress bar */}
        <div className="mt-4 h-2 bg-gray-200 rounded-full overflow-hidden">
          <div
            className="h-full bg-primary transition-all duration-300"
            style={{ width: `${completionPercentage}%` }}
          />
        </div>
      </div>

      {/* Participant forms */}
      <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-6">
        {participants.map((participant, index) => {
          const isComplete = isParticipantComplete(index);
          const hasErrors =
            errors.participants?.[index]?.firstName || errors.participants?.[index]?.lastName;

          return (
            <div
              key={participant.id || index}
              className={`border rounded-lg p-6 transition-colors ${
                isComplete
                  ? 'border-success/20 bg-success-light/30'
                  : hasErrors
                    ? 'border-error/20 bg-error-light/30'
                    : 'border-gray-200 bg-white'
              }`}
            >
              <div className="flex items-center justify-between mb-4">
                <div className="flex items-center gap-3">
                  <div
                    className={`w-10 h-10 rounded-full flex items-center justify-center ${
                      isComplete ? 'bg-success-light text-success' : 'bg-gray-100 text-gray-600'
                    }`}
                  >
                    {isComplete ? <Check className="w-5 h-5" /> : <User className="w-5 h-5" />}
                  </div>
                  <h3 className="font-semibold text-gray-900">
                    {t('participant_number', { number: index + 1 }) || `Participant #${index + 1}`}
                  </h3>
                </div>
                {isComplete && (
                  <span className="text-sm font-medium text-success">
                    {t('complete') || 'Complete'}
                  </span>
                )}
              </div>

              {/* Hidden ID field */}
              <input type="hidden" {...register(`participants.${index}.id`)} />

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* First Name */}
                <div>
                  <label
                    htmlFor={`participant-${index}-firstName`}
                    className="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t('first_name') || 'First Name'} <span className="text-error">*</span>
                  </label>
                  <input
                    id={`participant-${index}-firstName`}
                    type="text"
                    {...register(`participants.${index}.firstName`)}
                    className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent ${
                      errors.participants?.[index]?.firstName
                        ? 'border-error/30 bg-error-light'
                        : 'border-gray-300'
                    }`}
                    placeholder="John"
                  />
                  {errors.participants?.[index]?.firstName && (
                    <p className="text-sm text-error mt-1">
                      {errors.participants[index]?.firstName?.message}
                    </p>
                  )}
                </div>

                {/* Last Name */}
                <div>
                  <label
                    htmlFor={`participant-${index}-lastName`}
                    className="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t('last_name') || 'Last Name'} <span className="text-error">*</span>
                  </label>
                  <input
                    id={`participant-${index}-lastName`}
                    type="text"
                    {...register(`participants.${index}.lastName`)}
                    className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent ${
                      errors.participants?.[index]?.lastName
                        ? 'border-error/30 bg-error-light'
                        : 'border-gray-300'
                    }`}
                    placeholder="Doe"
                  />
                  {errors.participants?.[index]?.lastName && (
                    <p className="text-sm text-error mt-1">
                      {errors.participants[index]?.lastName?.message}
                    </p>
                  )}
                </div>

                {/* Email (optional) */}
                <div>
                  <label
                    htmlFor={`participant-${index}-email`}
                    className="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t('email') || 'Email'}{' '}
                    <span className="text-gray-400 text-xs">(optional)</span>
                  </label>
                  <input
                    id={`participant-${index}-email`}
                    type="email"
                    {...register(`participants.${index}.email`)}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    placeholder="john@example.com"
                  />
                  {errors.participants?.[index]?.email && (
                    <p className="text-sm text-error mt-1">
                      {errors.participants[index]?.email?.message}
                    </p>
                  )}
                </div>

                {/* Phone (optional) */}
                <div>
                  <label
                    htmlFor={`participant-${index}-phone`}
                    className="block text-sm font-medium text-gray-700 mb-1"
                  >
                    {t('phone') || 'Phone'}{' '}
                    <span className="text-gray-400 text-xs">(optional)</span>
                  </label>
                  <input
                    id={`participant-${index}-phone`}
                    type="tel"
                    {...register(`participants.${index}.phone`)}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    placeholder="+1 (555) 123-4567"
                  />
                </div>
              </div>
            </div>
          );
        })}

        {/* Submit button */}
        <div className="flex items-center justify-end gap-4 pt-4 border-t">
          <p className="text-sm text-gray-600">
            {completionPercentage === 100
              ? t('all_complete') || 'All participants complete!'
              : t('required_fields') || '* Required fields'}
          </p>
          <Button
            type="submit"
            variant="primary"
            size="lg"
            isLoading={submitting || isLoading}
            disabled={completionPercentage === 0}
          >
            {t('save_participants') || 'Save Participants'}
          </Button>
        </div>
      </form>
    </div>
  );
}
