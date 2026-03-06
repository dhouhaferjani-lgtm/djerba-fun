'use client';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useTranslations } from 'next-intl';
import { Button } from '@djerba-fun/ui';
import { ArrowLeft, Send, Mail, Phone, MessageCircle, AlertCircle, Loader2 } from 'lucide-react';
import type { ContactData } from '../CustomTripWizard';

const contactSchema = z.object({
  name: z.string().min(2, 'Name must be at least 2 characters').max(255),
  email: z.string().email('Please enter a valid email address'),
  phone: z.string().min(8, 'Please enter a valid phone number').max(50),
  whatsapp: z.string().max(50).optional(),
  sameAsPhone: z.boolean(),
  country: z.string().length(2, 'Please select your country'),
  specialRequests: z.string().max(1000).optional(),
  preferredContact: z.enum(['email', 'phone', 'whatsapp']),
  newsletterConsent: z.boolean(),
});

type FormData = z.infer<typeof contactSchema>;

interface ContactStepProps {
  initialData: ContactData | null;
  onComplete: (data: ContactData) => void;
  onBack: () => void;
  isSubmitting: boolean;
  error: string | null;
}

// Common countries for Tunisia tourism
const COUNTRIES = [
  { code: 'TN', name: 'Tunisia' },
  { code: 'FR', name: 'France' },
  { code: 'DE', name: 'Germany' },
  { code: 'GB', name: 'United Kingdom' },
  { code: 'IT', name: 'Italy' },
  { code: 'ES', name: 'Spain' },
  { code: 'US', name: 'United States' },
  { code: 'CA', name: 'Canada' },
  { code: 'BE', name: 'Belgium' },
  { code: 'CH', name: 'Switzerland' },
  { code: 'NL', name: 'Netherlands' },
  { code: 'DZ', name: 'Algeria' },
  { code: 'LY', name: 'Libya' },
  { code: 'MA', name: 'Morocco' },
  { code: 'AE', name: 'United Arab Emirates' },
  { code: 'SA', name: 'Saudi Arabia' },
];

export function ContactStep({
  initialData,
  onComplete,
  onBack,
  isSubmitting,
  error,
}: ContactStepProps) {
  const t = useTranslations('customTrip.contact');

  const {
    register,
    watch,
    setValue,
    handleSubmit,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(contactSchema),
    defaultValues: {
      name: initialData?.name || '',
      email: initialData?.email || '',
      phone: initialData?.phone || '',
      whatsapp: initialData?.whatsapp || '',
      sameAsPhone: initialData?.sameAsPhone ?? true,
      country: initialData?.country || '',
      specialRequests: initialData?.specialRequests || '',
      preferredContact: initialData?.preferredContact || 'email',
      newsletterConsent: initialData?.newsletterConsent || false,
    },
  });

  const sameAsPhone = watch('sameAsPhone');
  const preferredContact = watch('preferredContact');

  const onSubmit = (data: FormData) => {
    onComplete({
      name: data.name,
      email: data.email,
      phone: data.phone,
      whatsapp: data.sameAsPhone ? data.phone : data.whatsapp || '',
      sameAsPhone: data.sameAsPhone,
      country: data.country,
      specialRequests: data.specialRequests || '',
      preferredContact: data.preferredContact,
      newsletterConsent: data.newsletterConsent,
    });
  };

  const contactMethods = [
    { id: 'email', icon: <Mail className="h-5 w-5" />, label: t('method.email') },
    { id: 'phone', icon: <Phone className="h-5 w-5" />, label: t('method.phone') },
    { id: 'whatsapp', icon: <MessageCircle className="h-5 w-5" />, label: t('method.whatsapp') },
  ] as const;

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-8">
      <div>
        <h2 className="text-2xl font-bold text-gray-900 mb-2">{t('title')}</h2>
        <p className="text-gray-600">{t('subtitle')}</p>
      </div>

      {/* Error Display */}
      {error && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
          <AlertCircle className="h-5 w-5 text-red-500 flex-shrink-0 mt-0.5" />
          <p className="text-red-700 text-sm">{error}</p>
        </div>
      )}

      {/* Name */}
      <div>
        <label className="block text-sm font-semibold text-gray-900 mb-2">
          {t('full_name')} <span className="text-red-500">*</span>
        </label>
        <input
          type="text"
          {...register('name')}
          placeholder={t('full_name_placeholder')}
          className={`
            w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary
            ${errors.name ? 'border-red-500' : 'border-gray-300'}
          `}
        />
        {errors.name && <p className="text-red-500 text-sm mt-1">{errors.name.message}</p>}
      </div>

      {/* Email */}
      <div>
        <label className="block text-sm font-semibold text-gray-900 mb-2">
          {t('email')} <span className="text-red-500">*</span>
        </label>
        <input
          type="email"
          {...register('email')}
          placeholder={t('email_placeholder')}
          className={`
            w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary
            ${errors.email ? 'border-red-500' : 'border-gray-300'}
          `}
        />
        {errors.email && <p className="text-red-500 text-sm mt-1">{errors.email.message}</p>}
      </div>

      {/* Phone */}
      <div>
        <label className="block text-sm font-semibold text-gray-900 mb-2">
          {t('phone')} <span className="text-red-500">*</span>
        </label>
        <input
          type="tel"
          {...register('phone')}
          placeholder={t('phone_placeholder')}
          className={`
            w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary
            ${errors.phone ? 'border-red-500' : 'border-gray-300'}
          `}
        />
        {errors.phone && <p className="text-red-500 text-sm mt-1">{errors.phone.message}</p>}
      </div>

      {/* WhatsApp */}
      <div className="space-y-3">
        <label className="flex items-center gap-3 cursor-pointer">
          <input
            type="checkbox"
            {...register('sameAsPhone')}
            className="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary"
          />
          <span className="text-sm text-gray-700">{t('whatsapp_same_as_phone')}</span>
        </label>

        {!sameAsPhone && (
          <div>
            <label className="block text-sm font-semibold text-gray-900 mb-2">
              {t('whatsapp_number')}
            </label>
            <input
              type="tel"
              {...register('whatsapp')}
              placeholder={t('whatsapp_placeholder')}
              className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
            />
          </div>
        )}
      </div>

      {/* Country */}
      <div>
        <label className="block text-sm font-semibold text-gray-900 mb-2">
          {t('country')} <span className="text-red-500">*</span>
        </label>
        <select
          {...register('country')}
          className={`
            w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary
            bg-white
            ${errors.country ? 'border-red-500' : 'border-gray-300'}
          `}
        >
          <option value="">{t('country_placeholder')}</option>
          {COUNTRIES.map((country) => (
            <option key={country.code} value={country.code}>
              {country.name}
            </option>
          ))}
        </select>
        {errors.country && <p className="text-red-500 text-sm mt-1">{errors.country.message}</p>}
      </div>

      {/* Preferred Contact Method */}
      <div className="space-y-3">
        <label className="block text-sm font-semibold text-gray-900">
          {t('preferred_contact')} <span className="text-red-500">*</span>
        </label>
        <div className="flex flex-wrap gap-3">
          {contactMethods.map((method) => {
            const isSelected = preferredContact === method.id;
            return (
              <button
                key={method.id}
                type="button"
                onClick={() => setValue('preferredContact', method.id)}
                className={`
                  px-4 py-2 rounded-full border-2 transition-all duration-200
                  flex items-center gap-2
                  ${
                    isSelected
                      ? 'border-primary bg-primary/10 text-primary'
                      : 'border-gray-200 hover:border-gray-300 text-gray-700'
                  }
                `}
              >
                {method.icon}
                <span className="text-sm font-medium">{method.label}</span>
              </button>
            );
          })}
        </div>
      </div>

      {/* Special Requests */}
      <div>
        <label className="block text-sm font-semibold text-gray-900 mb-2">
          {t('special_requests')}
          <span className="font-normal text-gray-500 ml-2">{t('optional')}</span>
        </label>
        <textarea
          {...register('specialRequests')}
          rows={4}
          placeholder={t('special_requests_placeholder')}
          className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
        />
        <p className="text-xs text-gray-500 mt-1">{t('special_requests_hint')}</p>
      </div>

      {/* Newsletter Consent */}
      <div className="bg-gray-50 rounded-lg p-4">
        <label className="flex items-start gap-3 cursor-pointer">
          <input
            type="checkbox"
            {...register('newsletterConsent')}
            className="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary mt-0.5"
          />
          <span className="text-sm text-gray-700">{t('newsletter_consent')}</span>
        </label>
      </div>

      {/* Navigation Buttons */}
      <div className="flex justify-between pt-4">
        <Button type="button" variant="outline" size="lg" onClick={onBack} disabled={isSubmitting}>
          <ArrowLeft className="h-5 w-5 mr-2" />
          {t('back')}
        </Button>
        <Button
          type="submit"
          variant="primary"
          size="lg"
          disabled={isSubmitting}
          className="min-w-[200px]"
        >
          {isSubmitting ? (
            <>
              <Loader2 className="h-5 w-5 mr-2 animate-spin" />
              {t('submitting')}
            </>
          ) : (
            <>
              {t('submit')}
              <Send className="h-5 w-5 ml-2" />
            </>
          )}
        </Button>
      </div>
    </form>
  );
}
