'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Button } from '@go-adventure/ui';
import { X, Package, AlertCircle, Check } from 'lucide-react';
import { useClaimBooking } from '@/lib/api/hooks';

// Booking number format: GA-YYYYMM-XXXXX
const claimFormSchema = z.object({
  bookingNumber: z
    .string()
    .regex(
      /^GA-\d{6}-[A-Z0-9]{5}$/,
      'Invalid booking number format. Expected format: GA-YYYYMM-XXXXX'
    ),
});

type ClaimFormData = z.infer<typeof claimFormSchema>;

interface ClaimBookingModalProps {
  onClose: () => void;
  onSuccess?: () => void;
}

/**
 * Modal for claiming a booking by booking number
 * Validates booking number format and email match
 * Used in dashboard for linking past bookings manually
 */
export function ClaimBookingModal({ onClose, onSuccess }: ClaimBookingModalProps) {
  const t = useTranslations('dashboard');
  const tBooking = useTranslations('booking');
  const [claimSuccess, setClaimSuccess] = useState(false);
  const [claimedBookingNumber, setClaimedBookingNumber] = useState('');

  const claimMutation = useClaimBooking();

  const {
    register,
    handleSubmit,
    formState: { errors },
    setError,
  } = useForm<ClaimFormData>({
    resolver: zodResolver(claimFormSchema),
  });

  const onSubmit = async (data: ClaimFormData) => {
    try {
      await claimMutation.mutateAsync(data.bookingNumber);
      setClaimedBookingNumber(data.bookingNumber);
      setClaimSuccess(true);

      // Call onSuccess callback and close after delay
      setTimeout(() => {
        onSuccess?.();
        onClose();
      }, 2000);
    } catch (error: any) {
      // Handle specific errors
      const errorCode = error?.response?.data?.error?.code;
      const message = error?.response?.data?.error?.message || error?.message;

      if (errorCode === 'NOT_FOUND') {
        setError('bookingNumber', {
          message: 'Booking not found. Please check the booking number and try again.',
        });
      } else if (errorCode === 'EMAIL_MISMATCH') {
        setError('bookingNumber', {
          message:
            'This booking is associated with a different email address. Please log in with the correct account.',
        });
      } else if (errorCode === 'ALREADY_LINKED') {
        setError('bookingNumber', {
          message: 'This booking is already linked to an account.',
        });
      } else {
        setError('bookingNumber', {
          message: message || 'Failed to claim booking. Please try again.',
        });
      }
    }
  };

  if (claimSuccess) {
    return (
      <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
        <div className="bg-white rounded-lg max-w-md w-full p-8">
          <div className="text-center">
            <div className="flex justify-center mb-6">
              <div className="w-16 h-16 bg-success-light rounded-full flex items-center justify-center">
                <Check className="w-10 h-10 text-success" />
              </div>
            </div>
            <h2 className="text-2xl font-bold text-gray-900 mb-2">
              {t('claim_success') || 'Booking claimed successfully!'}
            </h2>
            <p className="text-gray-600 mb-4">
              Booking <strong>{claimedBookingNumber}</strong> has been added to your account.
            </p>
            <div className="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
              <div
                className="bg-primary h-full transition-all duration-[2000ms] ease-linear"
                style={{ width: '100%' }}
              />
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
      <div className="bg-white rounded-lg max-w-md w-full">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
              <Package className="w-5 h-5 text-primary" />
            </div>
            <h2 className="text-xl font-bold text-gray-900">
              {t('claim_booking_title') || 'Claim Booking'}
            </h2>
          </div>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
            aria-label="Close"
          >
            <X className="w-6 h-6" />
          </button>
        </div>

        {/* Body */}
        <form onSubmit={handleSubmit(onSubmit)} className="p-6 space-y-6">
          {/* Info Banner */}
          <div className="bg-success-light border border-success/20 rounded-lg p-4">
            <div className="flex gap-3">
              <AlertCircle className="w-5 h-5 text-success flex-shrink-0 mt-0.5" />
              <div className="text-sm text-success-dark">
                <p className="font-medium mb-1">
                  {t('claim_booking_info_title') || 'Link a past booking'}
                </p>
                <p>
                  {t('claim_booking_info_text') ||
                    'Enter your booking number to link it to your account. The booking email must match your account email.'}
                </p>
              </div>
            </div>
          </div>

          {/* Booking Number Input */}
          <div>
            <label htmlFor="bookingNumber" className="block text-sm font-medium text-gray-700 mb-2">
              {t('booking_number_label') || 'Booking Number'}
            </label>
            <input
              id="bookingNumber"
              type="text"
              {...register('bookingNumber')}
              className={`w-full px-4 py-3 border rounded-lg font-mono text-lg focus:ring-2 focus:ring-primary focus:border-transparent ${
                errors.bookingNumber ? 'border-error/30 bg-error-light' : 'border-gray-300'
              }`}
              placeholder="GA-202512-A1B2C"
              autoComplete="off"
              autoFocus
            />
            {errors.bookingNumber && (
              <p className="text-sm text-error mt-2 flex items-start gap-2">
                <AlertCircle className="w-4 h-4 flex-shrink-0 mt-0.5" />
                <span>{errors.bookingNumber.message}</span>
              </p>
            )}
            <p className="text-xs text-gray-500 mt-2">
              Format: GA-YYYYMM-XXXXX (e.g., GA-202512-A1B2C)
            </p>
          </div>

          {/* Example */}
          <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p className="text-xs font-medium text-gray-700 mb-2">
              Where to find your booking number:
            </p>
            <ul className="text-xs text-gray-600 space-y-1">
              <li>• Check your booking confirmation email</li>
              <li>• Look at the top of your booking receipt</li>
              <li>• Find it in your original booking summary</li>
            </ul>
          </div>

          {/* Actions */}
          <div className="flex gap-3 pt-2">
            <Button
              type="button"
              variant="outline"
              size="lg"
              onClick={onClose}
              className="flex-1"
              disabled={claimMutation.isPending}
            >
              {t('cancel') || 'Cancel'}
            </Button>
            <Button
              type="submit"
              variant="primary"
              size="lg"
              className="flex-1"
              isLoading={claimMutation.isPending}
            >
              {t('claim_booking_button') || 'Claim Booking'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
}
