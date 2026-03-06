'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { useForm } from 'react-hook-form';
import { Button, Input } from '@djerba-fun/ui';
import { useUpdateProfile, useUploadAvatar, useDeleteAvatar } from '@/lib/api/hooks';
import type { User } from '@djerba-fun/schemas';

interface ProfileFormProps {
  user: User;
}

interface ProfileFormData {
  firstName: string;
  lastName: string;
  displayName: string;
  email: string;
  phone: string;
}

export function ProfileForm({ user }: ProfileFormProps) {
  const t = useTranslations('profile');
  const [avatarPreview, setAvatarPreview] = useState<string | null>(user.avatarUrl || null);
  const updateProfile = useUpdateProfile();
  const uploadAvatar = useUploadAvatar();
  const deleteAvatar = useDeleteAvatar();

  const {
    register,
    handleSubmit,
    formState: { errors, isDirty },
  } = useForm<ProfileFormData>({
    defaultValues: {
      firstName: user.firstName || '',
      lastName: user.lastName || '',
      displayName: user.displayName || '',
      email: user.email || '',
      phone: user.phone || '',
    },
  });

  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  const onSubmit = async (data: ProfileFormData) => {
    setMessage(null);
    try {
      await updateProfile.mutateAsync(data);
      setMessage({ type: 'success', text: t('profile_updated') });
    } catch (error) {
      setMessage({ type: 'error', text: t('update_error') });
    }
  };

  const handleAvatarChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    // Validate file size (2MB)
    if (file.size > 2 * 1024 * 1024) {
      setMessage({ type: 'error', text: t('max_file_size') });
      return;
    }

    // Validate file type
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
      setMessage({ type: 'error', text: t('allowed_file_types') });
      return;
    }

    // Preview
    const reader = new FileReader();
    reader.onloadend = () => {
      setAvatarPreview(reader.result as string);
    };
    reader.readAsDataURL(file);

    // Upload
    try {
      await uploadAvatar.mutateAsync(file);
      setMessage({ type: 'success', text: t('avatar_updated') });
    } catch (error) {
      setMessage({ type: 'error', text: t('upload_error') });
      setAvatarPreview(user.avatarUrl || null);
    }
  };

  const handleDeleteAvatar = async () => {
    try {
      await deleteAvatar.mutateAsync();
      setAvatarPreview(null);
      setMessage({ type: 'success', text: t('avatar_removed') });
    } catch (error) {
      setMessage({ type: 'error', text: t('delete_error') });
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
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

      {/* Avatar Section */}
      <div className="flex items-center gap-6">
        <div className="relative">
          {avatarPreview ? (
            <img
              src={avatarPreview}
              alt={user.displayName || ''}
              className="w-24 h-24 rounded-full object-cover"
            />
          ) : (
            <div className="w-24 h-24 rounded-full bg-primary/10 flex items-center justify-center">
              <span className="text-3xl font-bold text-primary">
                {(user.displayName || user.email || '?').charAt(0).toUpperCase()}
              </span>
            </div>
          )}
        </div>

        <div className="flex-1 space-y-2">
          <h3 className="font-medium text-gray-900">{t('avatar')}</h3>
          <div className="flex gap-2">
            <label className="cursor-pointer">
              <input
                type="file"
                accept="image/jpeg,image/png,image/webp"
                onChange={handleAvatarChange}
                className="hidden"
              />
              <Button type="button" variant="outline" size="sm" asChild>
                <span>{avatarPreview ? t('change_avatar') : t('upload_avatar')}</span>
              </Button>
            </label>
            {avatarPreview && (
              <Button
                type="button"
                variant="ghost"
                size="sm"
                onClick={handleDeleteAvatar}
                isLoading={deleteAvatar.isPending}
              >
                {t('remove_avatar')}
              </Button>
            )}
          </div>
          <p className="text-xs text-gray-500">{t('allowed_file_types')}</p>
        </div>
      </div>

      {/* Personal Information */}
      <div>
        <h3 className="text-lg font-semibold text-gray-900 mb-4">{t('personal_info')}</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Input
            {...register('firstName', { required: t('required_field') })}
            label={t('first_name')}
            error={errors.firstName?.message}
          />
          <Input
            {...register('lastName', { required: t('required_field') })}
            label={t('last_name')}
            error={errors.lastName?.message}
          />
          <div className="md:col-span-2">
            <Input
              {...register('displayName')}
              label={t('display_name')}
              error={errors.displayName?.message}
            />
          </div>
          <Input
            {...register('email', {
              required: t('required_field'),
              pattern: {
                value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
                message: t('invalid_email'),
              },
            })}
            label={t('email')}
            type="email"
            error={errors.email?.message}
          />
          <Input
            {...register('phone')}
            label={t('phone')}
            type="tel"
            error={errors.phone?.message}
          />
        </div>
      </div>

      {/* Actions */}
      <div className="flex justify-end gap-3 pt-4 border-t">
        <Button type="submit" isLoading={updateProfile.isPending} disabled={!isDirty}>
          {updateProfile.isPending ? t('saving') : t('save_changes')}
        </Button>
      </div>
    </form>
  );
}
