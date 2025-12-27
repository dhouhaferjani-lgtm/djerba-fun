'use client';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useTranslations } from 'next-intl';
import { Button } from '@go-adventure/ui';
import { useAuth } from '@/lib/contexts/AuthContext';
import { useEffect } from 'react';

const primaryContactSchema = z.object({
  firstName: z.string().min(1, 'First name is required'),
  lastName: z.string().min(1, 'Last name is required'),
  email: z.string().email('Invalid email address'),
  phone: z.string().optional(),
  specialRequests: z.string().optional(),
});

export type PrimaryContactData = z.infer<typeof primaryContactSchema>;

interface PrimaryContactFormProps {
  onSubmit: (data: PrimaryContactData) => void;
  onBack?: () => void;
  defaultValues?: Partial<PrimaryContactData>;
  isLoading?: boolean;
}

export function PrimaryContactForm({
  onSubmit,
  onBack,
  defaultValues,
  isLoading = false,
}: PrimaryContactFormProps) {
  const t = useTranslations('cart.checkout');
  const tBooking = useTranslations('booking');
  const { user, isAuthenticated } = useAuth();

  const {
    register,
    handleSubmit,
    setValue,
    formState: { errors },
  } = useForm<PrimaryContactData>({
    resolver: zodResolver(primaryContactSchema),
    defaultValues: {
      firstName: defaultValues?.firstName || '',
      lastName: defaultValues?.lastName || '',
      email: defaultValues?.email || '',
      phone: defaultValues?.phone || '',
      specialRequests: defaultValues?.specialRequests || '',
    },
  });

  // Pre-fill from authenticated user
  useEffect(() => {
    if (isAuthenticated && user && !defaultValues?.email) {
      const profile = (
        user as { travelerProfile?: { firstName?: string; lastName?: string; phone?: string } }
      ).travelerProfile;

      if (profile?.firstName) setValue('firstName', profile.firstName);
      if (profile?.lastName) setValue('lastName', profile.lastName);
      if (user.email) setValue('email', user.email);
      if (profile?.phone) setValue('phone', profile.phone);
    }
  }, [isAuthenticated, user, defaultValues, setValue]);

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      <div>
        <h3 className="text-lg font-semibold text-gray-900 mb-1">{t('primary_contact')}</h3>
        <p className="text-sm text-gray-600 mb-4">{t('primary_contact_description')}</p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label htmlFor="firstName" className="block text-sm font-medium text-gray-700 mb-1">
            {tBooking('first_name')} *
          </label>
          <input
            {...register('firstName')}
            type="text"
            id="firstName"
            className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary ${
              errors.firstName ? 'border-error' : 'border-gray-300'
            }`}
            placeholder={tBooking('first_name')}
          />
          {errors.firstName && (
            <p className="mt-1 text-sm text-error">{errors.firstName.message}</p>
          )}
        </div>

        <div>
          <label htmlFor="lastName" className="block text-sm font-medium text-gray-700 mb-1">
            {tBooking('last_name')} *
          </label>
          <input
            {...register('lastName')}
            type="text"
            id="lastName"
            className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary ${
              errors.lastName ? 'border-error' : 'border-gray-300'
            }`}
            placeholder={tBooking('last_name')}
          />
          {errors.lastName && <p className="mt-1 text-sm text-error">{errors.lastName.message}</p>}
        </div>
      </div>

      <div>
        <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
          {tBooking('email')} *
        </label>
        <input
          {...register('email')}
          type="email"
          id="email"
          className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary ${
            errors.email ? 'border-error' : 'border-gray-300'
          }`}
          placeholder={tBooking('email')}
        />
        {errors.email && <p className="mt-1 text-sm text-error">{errors.email.message}</p>}
      </div>

      <div>
        <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-1">
          {tBooking('phone')}
        </label>
        <input
          {...register('phone')}
          type="tel"
          id="phone"
          className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
          placeholder={tBooking('phone')}
        />
      </div>

      <div>
        <label htmlFor="specialRequests" className="block text-sm font-medium text-gray-700 mb-1">
          {tBooking('special_requests')}
        </label>
        <textarea
          {...register('specialRequests')}
          id="specialRequests"
          rows={3}
          className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
          placeholder={t('special_requests_placeholder')}
        />
      </div>

      <div className="flex gap-4 pt-4">
        {onBack && (
          <Button type="button" variant="outline" onClick={onBack}>
            {t('back')}
          </Button>
        )}
        <Button type="submit" className="flex-1" disabled={isLoading}>
          {isLoading ? t('processing') : t('continue_to_payment')}
        </Button>
      </div>
    </form>
  );
}
