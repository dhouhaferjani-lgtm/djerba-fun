'use client';

import { useState, useEffect } from 'react';
import { useTranslations } from 'next-intl';
import { Button } from '@go-adventure/ui';

export interface BillingAddress {
  country_code: string;
  city: string;
  postal_code: string;
  address_line1: string;
  address_line2?: string;
}

interface BillingAddressStepProps {
  onSubmit: (address: BillingAddress) => void;
  onBack?: () => void;
  defaultAddress?: BillingAddress;
  isProcessing?: boolean;
}

// Common countries for quick selection
const COMMON_COUNTRIES = [
  { code: 'TN', name: 'Tunisia' },
  { code: 'FR', name: 'France' },
  { code: 'US', name: 'United States' },
  { code: 'DE', name: 'Germany' },
  { code: 'GB', name: 'United Kingdom' },
  { code: 'ES', name: 'Spain' },
  { code: 'IT', name: 'Italy' },
  { code: 'MA', name: 'Morocco' },
  { code: 'DZ', name: 'Algeria' },
  { code: 'CA', name: 'Canada' },
];

export function BillingAddressStep({
  onSubmit,
  onBack,
  defaultAddress,
  isProcessing = false,
}: BillingAddressStepProps) {
  const t = useTranslations('booking');

  const [formData, setFormData] = useState<BillingAddress>(
    defaultAddress || {
      country_code: '',
      city: '',
      postal_code: '',
      address_line1: '',
      address_line2: '',
    }
  );

  const [errors, setErrors] = useState<Partial<Record<keyof BillingAddress, string>>>({});

  useEffect(() => {
    if (defaultAddress) {
      setFormData(defaultAddress);
    }
  }, [defaultAddress]);

  const validateForm = (): boolean => {
    const newErrors: Partial<Record<keyof BillingAddress, string>> = {};

    if (!formData.country_code) {
      newErrors.country_code = t('billing_country_required') || 'Country is required';
    }
    if (!formData.city) {
      newErrors.city = t('billing_city_required') || 'City is required';
    }
    if (!formData.postal_code) {
      newErrors.postal_code = t('billing_postal_code_required') || 'Postal code is required';
    }
    if (!formData.address_line1) {
      newErrors.address_line1 = t('billing_address_required') || 'Address is required';
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

  const handleChange = (field: keyof BillingAddress, value: string) => {
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
        <h2 className="text-2xl font-bold text-gray-900 mb-2">
          {t('billing_address_title') || 'Billing Address'}
        </h2>
        <p className="text-gray-600">
          {t('billing_address_subtitle') || 'Enter your billing address for payment processing.'}
        </p>
      </div>

      <form onSubmit={handleSubmit} className="space-y-4">
        {/* Country */}
        <div>
          <label htmlFor="billing-country" className="block text-sm font-medium text-gray-700 mb-1">
            {t('billing_country') || 'Country'} <span className="text-error">*</span>
          </label>
          <select
            id="billing-country"
            data-testid="billing-country"
            value={formData.country_code}
            onChange={(e) => handleChange('country_code', e.target.value)}
            className={`w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary ${
              errors.country_code ? 'border-error' : 'border-gray-300'
            }`}
            disabled={isProcessing}
          >
            <option value="">{t('select_country') || 'Select a country'}</option>
            {COMMON_COUNTRIES.map((country) => (
              <option key={country.code} value={country.code}>
                {country.name}
              </option>
            ))}
          </select>
          {errors.country_code && <p className="mt-1 text-sm text-error">{errors.country_code}</p>}
        </div>

        {/* Address Line 1 */}
        <div>
          <label
            htmlFor="billing-address-line1"
            className="block text-sm font-medium text-gray-700 mb-1"
          >
            {t('billing_address') || 'Street Address'} <span className="text-error">*</span>
          </label>
          <input
            type="text"
            id="billing-address-line1"
            data-testid="billing-address-line1"
            value={formData.address_line1}
            onChange={(e) => handleChange('address_line1', e.target.value)}
            placeholder={t('billing_address_placeholder') || '123 Main Street'}
            className={`w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary ${
              errors.address_line1 ? 'border-error' : 'border-gray-300'
            }`}
            disabled={isProcessing}
          />
          {errors.address_line1 && (
            <p className="mt-1 text-sm text-error">{errors.address_line1}</p>
          )}
        </div>

        {/* Address Line 2 (Optional) */}
        <div>
          <label
            htmlFor="billing-address-line2"
            className="block text-sm font-medium text-gray-700 mb-1"
          >
            {t('billing_address_line2') || 'Apartment, suite, etc.'}{' '}
            <span className="text-gray-400 text-xs">({t('optional') || 'optional'})</span>
          </label>
          <input
            type="text"
            id="billing-address-line2"
            data-testid="billing-address-line2"
            value={formData.address_line2 || ''}
            onChange={(e) => handleChange('address_line2', e.target.value)}
            placeholder={t('billing_address_line2_placeholder') || 'Apt 4B'}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
            disabled={isProcessing}
          />
        </div>

        {/* City and Postal Code in a row */}
        <div className="grid grid-cols-2 gap-4">
          {/* City */}
          <div>
            <label htmlFor="billing-city" className="block text-sm font-medium text-gray-700 mb-1">
              {t('billing_city') || 'City'} <span className="text-error">*</span>
            </label>
            <input
              type="text"
              id="billing-city"
              data-testid="billing-city"
              value={formData.city}
              onChange={(e) => handleChange('city', e.target.value)}
              placeholder={t('billing_city_placeholder') || 'Tunis'}
              className={`w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary ${
                errors.city ? 'border-error' : 'border-gray-300'
              }`}
              disabled={isProcessing}
            />
            {errors.city && <p className="mt-1 text-sm text-error">{errors.city}</p>}
          </div>

          {/* Postal Code */}
          <div>
            <label
              htmlFor="billing-postal-code"
              className="block text-sm font-medium text-gray-700 mb-1"
            >
              {t('billing_postal_code') || 'Postal Code'} <span className="text-error">*</span>
            </label>
            <input
              type="text"
              id="billing-postal-code"
              data-testid="billing-postal-code"
              value={formData.postal_code}
              onChange={(e) => handleChange('postal_code', e.target.value)}
              placeholder={t('billing_postal_code_placeholder') || '1000'}
              className={`w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary ${
                errors.postal_code ? 'border-error' : 'border-gray-300'
              }`}
              disabled={isProcessing}
            />
            {errors.postal_code && <p className="mt-1 text-sm text-error">{errors.postal_code}</p>}
          </div>
        </div>

        {/* Action Buttons */}
        <div className="flex gap-4 pt-4">
          {onBack && (
            <Button
              type="button"
              onClick={onBack}
              variant="outline"
              disabled={isProcessing}
              data-testid="back-to-traveler-info"
              className="flex-1"
            >
              {t('back') || 'Back'}
            </Button>
          )}
          <Button
            type="submit"
            disabled={isProcessing}
            data-testid="continue-to-review"
            className="flex-1"
          >
            {isProcessing
              ? t('processing') || 'Processing...'
              : t('continue_to_review') || 'Continue to Review'}
          </Button>
        </div>
      </form>
    </div>
  );
}
