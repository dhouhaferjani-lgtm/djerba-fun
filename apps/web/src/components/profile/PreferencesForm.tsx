'use client';

import { useEffect, useState } from 'react';
import { useTranslations } from 'next-intl';
import { useForm, Controller } from 'react-hook-form';
import { Button } from '@go-adventure/ui';
import { usePreferences, useUpdatePreferences } from '@/lib/api/hooks';

interface PreferencesFormData {
  locale: string;
  currency: string;
  notifications: {
    emailNotifications: boolean;
    marketingEmails: boolean;
    bookingReminders: boolean;
    reviewReminders: boolean;
  };
}

export function PreferencesForm() {
  const t = useTranslations('profile');
  const { data: preferences, isLoading } = usePreferences();
  const updatePreferences = useUpdatePreferences();
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  const {
    register,
    handleSubmit,
    reset,
    control,
    formState: { isDirty },
  } = useForm<PreferencesFormData>({
    defaultValues: {
      locale: 'en',
      currency: 'TND',
      notifications: {
        emailNotifications: true,
        marketingEmails: false,
        bookingReminders: true,
        reviewReminders: true,
      },
    },
  });

  useEffect(() => {
    if (preferences) {
      reset({
        locale: preferences.locale || 'en',
        currency: preferences.currency || 'TND',
        notifications: preferences.notifications,
      });
    }
  }, [preferences, reset]);

  const onSubmit = async (data: PreferencesFormData) => {
    setMessage(null);
    try {
      await updatePreferences.mutateAsync(data);
      setMessage({ type: 'success', text: t('preferences_updated') });
    } catch (error) {
      setMessage({ type: 'error', text: t('update_error') });
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6 max-w-2xl">
      {/* Message */}
      {message && (
        <div
          className={`p-4 rounded-lg ${
            message.type === 'success'
              ? 'bg-success-light text-success-dark'
              : 'bg-error-light text-error-dark'
          }`}
        >
          {message.text}
        </div>
      )}

      {/* Language & Currency */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">{t('language')}</label>
          <select
            {...register('locale')}
            className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
          >
            <option value="en">English</option>
            <option value="fr">Français</option>
            <option value="ar">العربية</option>
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">{t('currency')}</label>
          <select
            {...register('currency')}
            className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
          >
            <option value="TND">TND - Tunisian Dinar</option>
            <option value="EUR">EUR - Euro</option>
            <option value="USD">USD - US Dollar</option>
            <option value="GBP">GBP - British Pound</option>
          </select>
        </div>
      </div>

      {/* Notifications */}
      <div>
        <h3 className="text-lg font-semibold text-gray-900 mb-4">{t('notifications_title')}</h3>
        <div className="space-y-4">
          <Controller
            name="notifications.emailNotifications"
            control={control}
            render={({ field }) => (
              <label className="flex items-start gap-3 p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                <input
                  type="checkbox"
                  checked={field.value}
                  onChange={field.onChange}
                  className="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                />
                <div className="flex-1">
                  <div className="font-medium text-gray-900">{t('email_notifications')}</div>
                  <div className="text-sm text-gray-600">{t('email_notifications_desc')}</div>
                </div>
              </label>
            )}
          />

          <Controller
            name="notifications.marketingEmails"
            control={control}
            render={({ field }) => (
              <label className="flex items-start gap-3 p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                <input
                  type="checkbox"
                  checked={field.value}
                  onChange={field.onChange}
                  className="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                />
                <div className="flex-1">
                  <div className="font-medium text-gray-900">{t('marketing_emails')}</div>
                  <div className="text-sm text-gray-600">{t('marketing_emails_desc')}</div>
                </div>
              </label>
            )}
          />

          <Controller
            name="notifications.bookingReminders"
            control={control}
            render={({ field }) => (
              <label className="flex items-start gap-3 p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                <input
                  type="checkbox"
                  checked={field.value}
                  onChange={field.onChange}
                  className="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                />
                <div className="flex-1">
                  <div className="font-medium text-gray-900">{t('booking_reminders')}</div>
                  <div className="text-sm text-gray-600">{t('booking_reminders_desc')}</div>
                </div>
              </label>
            )}
          />

          <Controller
            name="notifications.reviewReminders"
            control={control}
            render={({ field }) => (
              <label className="flex items-start gap-3 p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                <input
                  type="checkbox"
                  checked={field.value}
                  onChange={field.onChange}
                  className="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                />
                <div className="flex-1">
                  <div className="font-medium text-gray-900">{t('review_reminders')}</div>
                  <div className="text-sm text-gray-600">{t('review_reminders_desc')}</div>
                </div>
              </label>
            )}
          />
        </div>
      </div>

      {/* Actions */}
      <div className="flex justify-end gap-3 pt-4 border-t">
        <Button type="submit" isLoading={updatePreferences.isPending} disabled={!isDirty}>
          {updatePreferences.isPending ? t('saving') : t('save_preferences')}
        </Button>
      </div>
    </form>
  );
}
