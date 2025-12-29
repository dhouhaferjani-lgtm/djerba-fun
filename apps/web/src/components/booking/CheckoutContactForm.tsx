'use client';

import { useState, useEffect } from 'react';
import { useTranslations } from 'next-intl';
import { Button, FloatingInput } from '@go-adventure/ui';
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
          <FloatingInput
            id="firstName"
            name="firstName"
            type="text"
            data-testid="checkout-first-name"
            autoComplete="given-name"
            label={`${t('first_name') || 'First Name'} *`}
            value={formData.firstName}
            onChange={(e) => handleChange('firstName', e.target.value)}
            error={errors.firstName}
            disabled={isProcessing}
            icon={<User className="h-4 w-4" />}
          />

          <FloatingInput
            id="lastName"
            name="lastName"
            type="text"
            data-testid="checkout-last-name"
            autoComplete="family-name"
            label={`${t('last_name') || 'Last Name'} *`}
            value={formData.lastName}
            onChange={(e) => handleChange('lastName', e.target.value)}
            error={errors.lastName}
            disabled={isProcessing}
            icon={<User className="h-4 w-4" />}
          />
        </div>

        {/* Email */}
        <FloatingInput
          id="email"
          name="email"
          type="email"
          data-testid="checkout-email"
          autoComplete="email"
          label={`${t('email') || 'Email Address'} *`}
          value={formData.email}
          onChange={(e) => handleChange('email', e.target.value)}
          error={errors.email}
          helperText={t('email_helper') || 'Booking confirmation will be sent to this email'}
          disabled={isProcessing}
          icon={<Mail className="h-4 w-4" />}
        />

        {/* Phone */}
        <FloatingInput
          id="phone"
          name="phone"
          type="tel"
          data-testid="checkout-phone"
          autoComplete="tel"
          label={`${t('phone') || 'Phone Number'} *`}
          value={formData.phone}
          onChange={(e) => handleChange('phone', e.target.value)}
          error={errors.phone}
          helperText={t('phone_helper') || 'We may need to contact you about your booking'}
          disabled={isProcessing}
          icon={<Phone className="h-4 w-4" />}
        />

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
