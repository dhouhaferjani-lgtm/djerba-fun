'use client';

import { useState, useEffect } from 'react';
import { useTranslations } from 'next-intl';
import { Button } from '@go-adventure/ui';
import { Mail, Phone, User } from 'lucide-react';

export interface ContactInfo {
  email: string;
  phone: string;
  firstName: string;
  lastName: string;
}

interface CheckoutContactFormProps {
  onSubmit: (contactInfo: ContactInfo) => void;
  defaultValues?: Partial<ContactInfo>;
  isProcessing?: boolean;
}

export function CheckoutContactForm({
  onSubmit,
  defaultValues,
  isProcessing = false,
}: CheckoutContactFormProps) {
  const t = useTranslations('booking');

  const [formData, setFormData] = useState<ContactInfo>({
    email: defaultValues?.email || '',
    phone: defaultValues?.phone || '',
    firstName: defaultValues?.firstName || '',
    lastName: defaultValues?.lastName || '',
  });

  const [errors, setErrors] = useState<Partial<Record<keyof ContactInfo, string>>>({});

  // Only update form if default values change AND the field is currently empty
  // This prevents overwriting user input when component re-renders
  useEffect(() => {
    if (defaultValues) {
      setFormData((prev) => ({
        email: prev.email || defaultValues.email || '',
        phone: prev.phone || defaultValues.phone || '',
        firstName: prev.firstName || defaultValues.firstName || '',
        lastName: prev.lastName || defaultValues.lastName || '',
      }));
    }
  }, [defaultValues]);

  const validateForm = (): boolean => {
    const newErrors: Partial<Record<keyof ContactInfo, string>> = {};

    // Email validation
    if (!formData.email) {
      newErrors.email = t('email_required') || 'Email is required';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      newErrors.email = t('email_invalid') || 'Please enter a valid email';
    }

    // Phone validation
    if (!formData.phone) {
      newErrors.phone = t('phone_required') || 'Phone number is required';
    } else if (formData.phone.length < 8) {
      newErrors.phone = t('phone_invalid') || 'Please enter a valid phone number';
    }

    // First name validation
    if (!formData.firstName) {
      newErrors.firstName = t('first_name_required') || 'First name is required';
    }

    // Last name validation
    if (!formData.lastName) {
      newErrors.lastName = t('last_name_required') || 'Last name is required';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (validateForm()) {
      onSubmit(formData);
    }
  };

  const handleChange = (field: keyof ContactInfo, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    // Clear error for this field
    if (errors[field]) {
      setErrors((prev) => {
        const newErrors = { ...prev };
        delete newErrors[field];
        return newErrors;
      });
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-neutral-900 mb-2">
          {t('contact_information') || 'Contact Information'}
        </h2>
        <p className="text-neutral-600">
          {t('contact_information_subtitle') ||
            'Enter your details to receive booking confirmation and updates.'}
        </p>
      </div>

      <form onSubmit={handleSubmit} className="space-y-4">
        {/* First Name and Last Name Row */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {/* First Name */}
          <div>
            <label htmlFor="firstName" className="block text-sm font-medium text-neutral-700 mb-1">
              <div className="flex items-center gap-2">
                <User className="h-4 w-4 text-neutral-500" />
                <span>
                  {t('first_name') || 'First Name'} <span className="text-error">*</span>
                </span>
              </div>
            </label>
            <input
              type="text"
              id="firstName"
              name="firstName"
              data-testid="checkout-first-name"
              autoComplete="given-name"
              value={formData.firstName}
              onChange={(e) => handleChange('firstName', e.target.value)}
              placeholder={t('first_name_placeholder') || 'John'}
              className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary ${
                errors.firstName ? 'border-error bg-error-light' : 'border-neutral-300'
              }`}
              disabled={isProcessing}
              aria-invalid={!!errors.firstName}
              aria-describedby={errors.firstName ? 'firstName-error' : undefined}
            />
            {errors.firstName && (
              <p id="firstName-error" className="mt-1 text-sm text-error">
                {errors.firstName}
              </p>
            )}
          </div>

          {/* Last Name */}
          <div>
            <label htmlFor="lastName" className="block text-sm font-medium text-neutral-700 mb-1">
              <div className="flex items-center gap-2">
                <User className="h-4 w-4 text-neutral-500" />
                <span>
                  {t('last_name') || 'Last Name'} <span className="text-error">*</span>
                </span>
              </div>
            </label>
            <input
              type="text"
              id="lastName"
              name="lastName"
              data-testid="checkout-last-name"
              autoComplete="family-name"
              value={formData.lastName}
              onChange={(e) => handleChange('lastName', e.target.value)}
              placeholder={t('last_name_placeholder') || 'Doe'}
              className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary ${
                errors.lastName ? 'border-error bg-error-light' : 'border-neutral-300'
              }`}
              disabled={isProcessing}
              aria-invalid={!!errors.lastName}
              aria-describedby={errors.lastName ? 'lastName-error' : undefined}
            />
            {errors.lastName && (
              <p id="lastName-error" className="mt-1 text-sm text-error">
                {errors.lastName}
              </p>
            )}
          </div>
        </div>

        {/* Email */}
        <div>
          <label htmlFor="email" className="block text-sm font-medium text-neutral-700 mb-1">
            <div className="flex items-center gap-2">
              <Mail className="h-4 w-4 text-neutral-500" />
              <span>
                {t('email') || 'Email Address'} <span className="text-error">*</span>
              </span>
            </div>
          </label>
          <input
            type="email"
            id="email"
            name="email"
            data-testid="checkout-email"
            autoComplete="email"
            value={formData.email}
            onChange={(e) => handleChange('email', e.target.value)}
            placeholder={t('email_placeholder') || 'john.doe@example.com'}
            className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary ${
              errors.email ? 'border-error bg-error-light' : 'border-neutral-300'
            }`}
            disabled={isProcessing}
            aria-invalid={!!errors.email}
            aria-describedby={errors.email ? 'email-error' : undefined}
          />
          {errors.email && (
            <p id="email-error" className="mt-1 text-sm text-error">
              {errors.email}
            </p>
          )}
          <p className="mt-1 text-sm text-neutral-500">
            {t('email_helper') || 'Booking confirmation will be sent to this email'}
          </p>
        </div>

        {/* Phone */}
        <div>
          <label htmlFor="phone" className="block text-sm font-medium text-neutral-700 mb-1">
            <div className="flex items-center gap-2">
              <Phone className="h-4 w-4 text-neutral-500" />
              <span>
                {t('phone') || 'Phone Number'} <span className="text-error">*</span>
              </span>
            </div>
          </label>
          <input
            type="tel"
            id="phone"
            name="phone"
            data-testid="checkout-phone"
            autoComplete="tel"
            value={formData.phone}
            onChange={(e) => handleChange('phone', e.target.value)}
            placeholder={t('phone_placeholder') || '+216 XX XXX XXX'}
            className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary ${
              errors.phone ? 'border-error bg-error-light' : 'border-neutral-300'
            }`}
            disabled={isProcessing}
            aria-invalid={!!errors.phone}
            aria-describedby={errors.phone ? 'phone-error' : undefined}
          />
          {errors.phone && (
            <p id="phone-error" className="mt-1 text-sm text-error">
              {errors.phone}
            </p>
          )}
          <p className="mt-1 text-sm text-neutral-500">
            {t('phone_helper') || 'We may need to contact you about your booking'}
          </p>
        </div>

        {/* Submit Button */}
        <div className="pt-4">
          <Button
            type="submit"
            variant="primary"
            size="lg"
            disabled={isProcessing}
            className="w-full"
          >
            {isProcessing ? t('processing') || 'Processing...' : t('continue') || 'Continue'}
          </Button>
        </div>
      </form>
    </div>
  );
}
