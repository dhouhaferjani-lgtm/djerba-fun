'use client';

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useTranslations } from 'next-intl';
import { Button } from '@go-adventure/ui';
import { User, Check, AlertCircle, Users } from 'lucide-react';
import type { Booking } from '@go-adventure/schemas';

// Zod schema for bulk participant form validation
const participantSchema = z.object({
  firstName: z.string().min(1, 'First name is required').max(100),
  lastName: z.string().min(1, 'Last name is required').max(100),
  email: z.string().email('Invalid email').optional().or(z.literal('')),
  phone: z.string().optional(),
});

const bulkParticipantsFormSchema = z.object({
  participants: z.array(participantSchema).min(1),
});

type BulkParticipantsFormData = z.infer<typeof bulkParticipantsFormSchema>;

interface BulkParticipantsFormProps {
  bookings: Booking[];
  onSubmit: (participants: BulkParticipantsFormData['participants']) => Promise<void>;
  isLoading?: boolean;
}

/**
 * BulkParticipantsForm Component
 *
 * Form for entering participant names once to apply across multiple bookings.
 * Used when user selects "Same participants for all tours" option.
 */
export function BulkParticipantsForm({
  bookings,
  onSubmit,
  isLoading = false,
}: BulkParticipantsFormProps) {
  const t = useTranslations('booking.participants');
  const [submitting, setSubmitting] = useState(false);

  // Find the participant count to use (should all be same if using this form)
  const participantCount = bookings[0]?.quantity || 1;

  // Initialize form with empty participant fields
  const initialParticipants = Array.from({ length: participantCount }, () => ({
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
  }));

  const {
    register,
    handleSubmit,
    formState: { errors },
    watch,
  } = useForm<BulkParticipantsFormData>({
    resolver: zodResolver(bulkParticipantsFormSchema),
    defaultValues: {
      participants: initialParticipants,
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
  const completionPercentage = Math.round((completionCount / participantCount) * 100);

  const handleFormSubmit = async (data: BulkParticipantsFormData) => {
    setSubmitting(true);
    try {
      // Convert form data to API format (camelCase to snake_case)
      const apiParticipants = data.participants.map((p) => ({
        first_name: p.firstName,
        last_name: p.lastName,
        email: p.email || null,
        phone: p.phone || null,
      }));
      await onSubmit(apiParticipants as any);
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="max-w-2xl mx-auto space-y-6">
      {/* Header */}
      <div className="text-center border-b pb-6">
        <div className="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
          <Users className="w-8 h-8 text-primary" />
        </div>
        <h2 className="text-2xl font-bold text-neutral-900">
          {t('bulk_entry_title') || 'Enter Participant Names'}
        </h2>
        <p className="text-neutral-600 mt-2">
          {t('bulk_entry_subtitle', { bookings: bookings.length }) ||
            `These names will be applied to all ${bookings.length} bookings.`}
        </p>

        {/* Progress bar */}
        <div className="mt-4 h-2 bg-neutral-200 rounded-full overflow-hidden">
          <div
            className="h-full bg-primary transition-all duration-300"
            style={{ width: `${completionPercentage}%` }}
          />
        </div>
        <p className="text-sm text-neutral-500 mt-2">
          {completionCount} of {participantCount} participants complete
        </p>
      </div>

      {/* Bookings summary */}
      <div className="bg-neutral-50 rounded-lg p-4">
        <p className="text-sm font-medium text-neutral-700 mb-2">
          {t('applying_to_bookings') || 'Applying to bookings:'}
        </p>
        <div className="flex flex-wrap gap-2">
          {bookings.map((booking) => (
            <span
              key={booking.id}
              className="px-3 py-1 bg-white border border-neutral-200 rounded-full text-sm text-neutral-600"
            >
              #{booking.bookingNumber}
            </span>
          ))}
        </div>
      </div>

      {/* Participant forms */}
      <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-4">
        {initialParticipants.map((_, index) => {
          const isComplete = isParticipantComplete(index);
          const hasErrors =
            errors.participants?.[index]?.firstName || errors.participants?.[index]?.lastName;

          return (
            <div
              key={index}
              className={`border rounded-lg p-5 transition-colors ${
                isComplete
                  ? 'border-success/30 bg-success/5'
                  : hasErrors
                    ? 'border-error/30 bg-error/5'
                    : 'border-neutral-200 bg-white'
              }`}
            >
              <div className="flex items-center justify-between mb-4">
                <div className="flex items-center gap-3">
                  <div
                    className={`w-9 h-9 rounded-full flex items-center justify-center ${
                      isComplete ? 'bg-success text-white' : 'bg-neutral-100 text-neutral-500'
                    }`}
                  >
                    {isComplete ? <Check className="w-4 h-4" /> : <User className="w-4 h-4" />}
                  </div>
                  <h3 className="font-semibold text-neutral-900">
                    {t('participant_number', { number: index + 1 }) || `Participant #${index + 1}`}
                  </h3>
                </div>
                {isComplete && (
                  <span className="text-xs font-medium text-success bg-success/10 px-2 py-1 rounded-full">
                    {t('complete') || 'Complete'}
                  </span>
                )}
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {/* First Name */}
                <div>
                  <label
                    htmlFor={`participant-${index}-firstName`}
                    className="block text-sm font-medium text-neutral-700 mb-1"
                  >
                    {t('first_name') || 'First Name'} <span className="text-error">*</span>
                  </label>
                  <input
                    id={`participant-${index}-firstName`}
                    type="text"
                    {...register(`participants.${index}.firstName`)}
                    className={`w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent ${
                      errors.participants?.[index]?.firstName
                        ? 'border-error bg-error/5'
                        : 'border-neutral-300'
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
                    className="block text-sm font-medium text-neutral-700 mb-1"
                  >
                    {t('last_name') || 'Last Name'} <span className="text-error">*</span>
                  </label>
                  <input
                    id={`participant-${index}-lastName`}
                    type="text"
                    {...register(`participants.${index}.lastName`)}
                    className={`w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent ${
                      errors.participants?.[index]?.lastName
                        ? 'border-error bg-error/5'
                        : 'border-neutral-300'
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
                    className="block text-sm font-medium text-neutral-700 mb-1"
                  >
                    {t('email') || 'Email'}{' '}
                    <span className="text-neutral-400 text-xs">
                      ({t('optional') || 'optional'})
                    </span>
                  </label>
                  <input
                    id={`participant-${index}-email`}
                    type="email"
                    {...register(`participants.${index}.email`)}
                    className="w-full px-4 py-2.5 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
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
                    className="block text-sm font-medium text-neutral-700 mb-1"
                  >
                    {t('phone') || 'Phone'}{' '}
                    <span className="text-neutral-400 text-xs">
                      ({t('optional') || 'optional'})
                    </span>
                  </label>
                  <input
                    id={`participant-${index}-phone`}
                    type="tel"
                    {...register(`participants.${index}.phone`)}
                    className="w-full px-4 py-2.5 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    placeholder="+216 XX XXX XXX"
                  />
                </div>
              </div>
            </div>
          );
        })}

        {/* Submit button */}
        <div className="flex items-center justify-between pt-4 border-t">
          <p className="text-sm text-neutral-600">
            {completionPercentage === 100 ? (
              <span className="flex items-center gap-2 text-success">
                <Check className="w-4 h-4" />
                {t('all_complete') || 'All participants complete!'}
              </span>
            ) : (
              <span className="flex items-center gap-2">
                <AlertCircle className="w-4 h-4" />
                {t('required_fields') || '* Required fields'}
              </span>
            )}
          </p>
          <Button
            type="submit"
            variant="primary"
            size="lg"
            isLoading={submitting || isLoading}
            disabled={completionPercentage === 0}
          >
            {t('apply_to_all_bookings') || 'Apply to All Bookings'}
          </Button>
        </div>
      </form>
    </div>
  );
}
