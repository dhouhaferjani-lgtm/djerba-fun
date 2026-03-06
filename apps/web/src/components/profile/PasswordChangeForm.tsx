'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { useForm } from 'react-hook-form';
import { Button, Input } from '@djerba-fun/ui';
import { useUpdatePassword } from '@/lib/api/hooks';

interface PasswordFormData {
  currentPassword: string;
  newPassword: string;
  newPasswordConfirmation: string;
}

export function PasswordChangeForm() {
  const t = useTranslations('profile');
  const updatePassword = useUpdatePassword();
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  const {
    register,
    handleSubmit,
    reset,
    watch,
    formState: { errors },
  } = useForm<PasswordFormData>({
    defaultValues: {
      currentPassword: '',
      newPassword: '',
      newPasswordConfirmation: '',
    },
  });

  const newPassword = watch('newPassword');

  const onSubmit = async (data: PasswordFormData) => {
    setMessage(null);
    try {
      await updatePassword.mutateAsync({
        currentPassword: data.currentPassword,
        newPassword: data.newPassword,
        newPasswordConfirmation: data.newPasswordConfirmation,
      });
      setMessage({ type: 'success', text: t('password_updated') });
      reset();
    } catch (error: any) {
      if (error?.message?.includes('Current password')) {
        setMessage({ type: 'error', text: t('invalid_current_password') });
      } else {
        setMessage({ type: 'error', text: t('password_error') });
      }
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6 max-w-md">
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

      <div className="space-y-4">
        <Input
          {...register('currentPassword', { required: t('required_field') })}
          label={t('current_password')}
          type="password"
          error={errors.currentPassword?.message}
        />
        <Input
          {...register('newPassword', {
            required: t('required_field'),
            minLength: {
              value: 8,
              message: t('password_requirements'),
            },
          })}
          label={t('new_password')}
          type="password"
          error={errors.newPassword?.message}
          helperText={t('password_requirements')}
        />
        <Input
          {...register('newPasswordConfirmation', {
            required: t('required_field'),
            validate: (value) => value === newPassword || t('passwords_must_match'),
          })}
          label={t('confirm_password')}
          type="password"
          error={errors.newPasswordConfirmation?.message}
        />
      </div>

      <div className="flex justify-end gap-3 pt-4 border-t">
        <Button type="submit" isLoading={updatePassword.isPending}>
          {updatePassword.isPending ? t('updating') : t('update_password')}
        </Button>
      </div>
    </form>
  );
}
