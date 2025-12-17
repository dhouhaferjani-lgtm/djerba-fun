'use client';

import { useForm, useFieldArray } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useTranslations } from 'next-intl';
import { Plus, Trash2, User, Users } from 'lucide-react';
import { Button } from '@go-adventure/ui';

// Schema for a single traveler
const travelerSchema = z.object({
  firstName: z.string().min(1, 'First name is required'),
  lastName: z.string().min(1, 'Last name is required'),
  email: z.string().email('Invalid email').optional().or(z.literal('')),
  phone: z.string().optional(),
  age: z.number().optional(),
  personType: z.string().optional(),
  isPrimary: z.boolean().optional(),
});

// Schema for the entire form
const multiTravelerSchema = z.object({
  primaryTraveler: z.object({
    firstName: z.string().min(1, 'First name is required'),
    lastName: z.string().min(1, 'Last name is required'),
    email: z.string().email('Invalid email'),
    phone: z.string().min(1, 'Phone number is required'),
    specialRequests: z.string().optional(),
  }),
  additionalTravelers: z.array(travelerSchema).optional(),
});

type MultiTravelerFormData = z.infer<typeof multiTravelerSchema>;

interface PersonType {
  key: string;
  label: { en: string; fr: string } | string;
  minAge: number | null;
  maxAge: number | null;
}

// Default values can have optional fields
interface MultiTravelerFormDefaultValues {
  primaryTraveler?: {
    firstName?: string;
    lastName?: string;
    email?: string;
    phone?: string;
    specialRequests?: string;
  };
  additionalTravelers?: Array<{
    firstName?: string;
    lastName?: string;
    personType?: string;
  }>;
}

interface MultiTravelerFormProps {
  totalGuests: number;
  personTypeBreakdown?: Record<string, number>;
  personTypes?: PersonType[];
  onSubmit: (data: MultiTravelerFormData) => void;
  defaultValues?: MultiTravelerFormDefaultValues;
  onBack?: () => void;
  locale?: string;
}

export function MultiTravelerForm({
  totalGuests,
  personTypeBreakdown,
  personTypes = [],
  onSubmit,
  defaultValues,
  onBack,
  locale = 'en',
}: MultiTravelerFormProps) {
  const t = useTranslations('booking');

  const getPersonTypeLabel = (type: PersonType): string => {
    if (typeof type.label === 'string') return type.label;
    return type.label[locale as keyof typeof type.label] || type.label.en || type.key;
  };

  // Build initial travelers list from breakdown
  const buildInitialTravelers = () => {
    if (!personTypeBreakdown || Object.keys(personTypeBreakdown).length === 0) {
      // Default to single adult
      return Array(Math.max(0, totalGuests - 1)).fill({
        firstName: '',
        lastName: '',
        personType: 'adult',
      });
    }

    const travelers: Array<{ firstName: string; lastName: string; personType: string }> = [];
    let skipFirst = true; // Skip first one as it's the primary traveler

    for (const [typeKey, count] of Object.entries(personTypeBreakdown)) {
      for (let i = 0; i < count; i++) {
        if (skipFirst) {
          skipFirst = false;
          continue;
        }
        travelers.push({
          firstName: '',
          lastName: '',
          personType: typeKey,
        });
      }
    }

    return travelers;
  };

  // Merge defaultValues with form defaults
  const formDefaults: MultiTravelerFormData = {
    primaryTraveler: {
      firstName: defaultValues?.primaryTraveler?.firstName || '',
      lastName: defaultValues?.primaryTraveler?.lastName || '',
      email: defaultValues?.primaryTraveler?.email || '',
      phone: defaultValues?.primaryTraveler?.phone || '',
      specialRequests: defaultValues?.primaryTraveler?.specialRequests || '',
    },
    additionalTravelers: defaultValues?.additionalTravelers?.length
      ? defaultValues.additionalTravelers.map((t) => ({
          firstName: t.firstName || '',
          lastName: t.lastName || '',
          personType: t.personType,
        }))
      : buildInitialTravelers(),
  };

  const {
    register,
    control,
    handleSubmit,
    formState: { errors, isSubmitting },
    watch,
  } = useForm<MultiTravelerFormData>({
    resolver: zodResolver(multiTravelerSchema),
    defaultValues: formDefaults,
  });

  const { fields } = useFieldArray({
    control,
    name: 'additionalTravelers',
  });

  // Watch additional travelers to get current values including personType
  const additionalTravelersValues = watch('additionalTravelers') || [];

  const additionalGuestCount = totalGuests - 1;

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-8">
      {/* Primary Traveler (Contact Person) */}
      <div className="bg-white border border-gray-200 rounded-xl p-6">
        <div className="flex items-center gap-3 mb-6">
          <div className="p-2 bg-primary/10 rounded-lg">
            <User className="h-5 w-5 text-primary" />
          </div>
          <div>
            <h2 className="text-xl font-bold text-gray-900">{t('traveler_info')}</h2>
            <p className="text-sm text-gray-500">Primary contact for this booking</p>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {/* First Name */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('first_name')} <span className="text-red-500">*</span>
            </label>
            <input
              {...register('primaryTraveler.firstName')}
              type="text"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
              placeholder={t('first_name')}
            />
            {errors.primaryTraveler?.firstName && (
              <p className="mt-1 text-sm text-red-600">
                {errors.primaryTraveler.firstName.message}
              </p>
            )}
          </div>

          {/* Last Name */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('last_name')} <span className="text-red-500">*</span>
            </label>
            <input
              {...register('primaryTraveler.lastName')}
              type="text"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
              placeholder={t('last_name')}
            />
            {errors.primaryTraveler?.lastName && (
              <p className="mt-1 text-sm text-red-600">{errors.primaryTraveler.lastName.message}</p>
            )}
          </div>

          {/* Email */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('email')} <span className="text-red-500">*</span>
            </label>
            <input
              {...register('primaryTraveler.email')}
              type="email"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
              placeholder={t('email_placeholder')}
            />
            {errors.primaryTraveler?.email && (
              <p className="mt-1 text-sm text-red-600">{errors.primaryTraveler.email.message}</p>
            )}
          </div>

          {/* Phone */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('phone')} <span className="text-red-500">*</span>
            </label>
            <input
              {...register('primaryTraveler.phone')}
              type="tel"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
              placeholder={t('phone_placeholder')}
            />
            {errors.primaryTraveler?.phone && (
              <p className="mt-1 text-sm text-red-600">{errors.primaryTraveler.phone.message}</p>
            )}
          </div>
        </div>

        {/* Special Requests */}
        <div className="mt-4">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            {t('special_requests')}
          </label>
          <textarea
            {...register('primaryTraveler.specialRequests')}
            rows={3}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent resize-none"
            placeholder={t('special_requests_placeholder')}
          />
        </div>
      </div>

      {/* Additional Travelers */}
      {additionalGuestCount > 0 && (
        <div className="bg-white border border-gray-200 rounded-xl p-6">
          <div className="flex items-center gap-3 mb-6">
            <div className="p-2 bg-secondary/10 rounded-lg">
              <Users className="h-5 w-5 text-secondary" />
            </div>
            <div>
              <h2 className="text-xl font-bold text-gray-900">Additional Travelers</h2>
              <p className="text-sm text-gray-500">
                {additionalGuestCount} more {additionalGuestCount === 1 ? 'guest' : 'guests'}
              </p>
            </div>
          </div>

          <div className="space-y-4">
            {fields.map((field, index) => {
              // Get personType from watched values since field doesn't include it
              const watchedTraveler = additionalTravelersValues[index];
              const personTypeKey = watchedTraveler?.personType || 'adult';
              const personType = personTypes.find((pt) => pt.key === personTypeKey);
              const typeLabel = personType ? getPersonTypeLabel(personType) : personTypeKey;

              return (
                <div key={field.id} className="border border-gray-100 rounded-lg p-4 bg-gray-50">
                  <div className="flex items-center justify-between mb-3">
                    <span className="text-sm font-medium text-gray-700">
                      Guest {index + 2}
                      {personType && (
                        <span className="ml-2 px-2 py-0.5 text-xs bg-gray-200 text-gray-600 rounded-full">
                          {typeLabel}
                        </span>
                      )}
                    </span>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                    {/* First Name */}
                    <div>
                      <label className="block text-xs font-medium text-gray-600 mb-1">
                        {t('first_name')} <span className="text-red-500">*</span>
                      </label>
                      <input
                        {...register(`additionalTravelers.${index}.firstName`)}
                        type="text"
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-transparent"
                        placeholder={t('first_name')}
                      />
                      {errors.additionalTravelers?.[index]?.firstName && (
                        <p className="mt-1 text-xs text-red-600">
                          {errors.additionalTravelers[index]?.firstName?.message}
                        </p>
                      )}
                    </div>

                    {/* Last Name */}
                    <div>
                      <label className="block text-xs font-medium text-gray-600 mb-1">
                        {t('last_name')} <span className="text-red-500">*</span>
                      </label>
                      <input
                        {...register(`additionalTravelers.${index}.lastName`)}
                        type="text"
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-transparent"
                        placeholder={t('last_name')}
                      />
                      {errors.additionalTravelers?.[index]?.lastName && (
                        <p className="mt-1 text-xs text-red-600">
                          {errors.additionalTravelers[index]?.lastName?.message}
                        </p>
                      )}
                    </div>
                  </div>

                  {/* Hidden field to preserve person type */}
                  <input
                    type="hidden"
                    {...register(`additionalTravelers.${index}.personType`)}
                    value={personTypeKey}
                  />
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Action Buttons */}
      <div className="flex gap-4 pt-4">
        {onBack && (
          <button
            type="button"
            onClick={onBack}
            className="flex-1 px-6 py-3 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 transition-colors"
          >
            {t('back')}
          </button>
        )}
        <button
          type="submit"
          disabled={isSubmitting}
          className="flex-1 px-6 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {isSubmitting ? t('processing') : t('continue')}
        </button>
      </div>
    </form>
  );
}

MultiTravelerForm.displayName = 'MultiTravelerForm';
