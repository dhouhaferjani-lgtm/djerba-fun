'use client';

import { useState, useEffect, useMemo } from 'react';
import { useParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { Link, useRouter } from '@/i18n/navigation';
import { useBooking, useParticipants, useUpdateParticipants } from '@/lib/api/hooks';
import type { UpdateParticipantData } from '@/lib/api/client';

interface ParticipantFormData {
  id: string;
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  personType: string | null;
}

export default function ParticipantsPage() {
  const params = useParams();
  const router = useRouter();
  const bookingId = params.id as string;
  const t = useTranslations('participants');
  const tCommon = useTranslations('common');

  // Check if user is authenticated - if not, use guest access
  const isGuest = useMemo(() => {
    if (typeof window === 'undefined') return false;
    return !localStorage.getItem('auth_token');
  }, []);

  const { data: booking, isLoading: bookingLoading } = useBooking(bookingId, isGuest);
  const { data: participantsData, isLoading: participantsLoading } = useParticipants(
    bookingId,
    isGuest
  );
  const updateMutation = useUpdateParticipants(isGuest);

  const [formData, setFormData] = useState<ParticipantFormData[]>([]);
  const [hasChanges, setHasChanges] = useState(false);

  // Initialize form data when participants load
  useEffect(() => {
    if (participantsData?.data) {
      setFormData(
        participantsData.data.map((p) => ({
          id: p.id,
          firstName: p.firstName || '',
          lastName: p.lastName || '',
          email: p.email || '',
          phone: p.phone || '',
          personType: p.personType,
        }))
      );
    }
  }, [participantsData]);

  const handleInputChange = (index: number, field: keyof ParticipantFormData, value: string) => {
    setFormData((prev) => {
      const newData = [...prev];
      newData[index] = { ...newData[index], [field]: value };
      return newData;
    });
    setHasChanges(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    const updates: UpdateParticipantData[] = formData.map((p) => ({
      id: p.id,
      first_name: p.firstName,
      last_name: p.lastName,
      email: p.email || undefined,
      phone: p.phone || undefined,
    }));

    try {
      await updateMutation.mutateAsync({ bookingId, participants: updates });
      setHasChanges(false);
      // Redirect to vouchers page after successful update
      router.push(`/dashboard/bookings/${bookingId}/vouchers`);
    } catch (error) {
      console.error('Failed to update participants:', error);
    }
  };

  const getPersonTypeLabel = (type: string | null) => {
    if (!type) return '';
    const labels: Record<string, string> = {
      adult: t('adult') || 'Adult',
      child: t('child') || 'Child',
      infant: t('infant') || 'Infant',
    };
    return labels[type] || type;
  };

  if (bookingLoading || participantsLoading) {
    return (
      <div className="max-w-3xl mx-auto p-6">
        <div className="animate-pulse space-y-4">
          <div className="h-8 bg-gray-200 rounded w-1/3"></div>
          <div className="h-4 bg-gray-200 rounded w-2/3"></div>
          <div className="space-y-3 mt-6">
            {[1, 2, 3].map((i) => (
              <div key={i} className="h-24 bg-gray-200 rounded"></div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  if (!booking || !participantsData) {
    return (
      <div className="max-w-3xl mx-auto p-6">
        <div className="text-center py-12">
          <p className="text-gray-600">{t('booking_not_found') || 'Booking not found'}</p>
          <Link
            href="/dashboard/bookings"
            className="text-primary hover:underline mt-2 inline-block"
          >
            {tCommon('back_to_bookings') || 'Back to bookings'}
          </Link>
        </div>
      </div>
    );
  }

  const participants = participantsData.data;
  const requiresNames = participantsData.meta.requiresNames;
  const completeCount = formData.filter((p) => p.firstName && p.lastName).length;
  const allComplete = completeCount === participants.length;

  return (
    <div className="max-w-3xl mx-auto p-6">
      {/* Header */}
      <div className="mb-8">
        <Link
          href={`/dashboard/bookings/${bookingId}`}
          className="text-sm text-gray-600 hover:text-gray-900 mb-4 inline-flex items-center"
        >
          <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M15 19l-7-7 7-7"
            />
          </svg>
          {tCommon('back') || 'Back'}
        </Link>
        <h1 className="text-2xl font-bold text-gray-900 mt-2">
          {t('title') || 'Enter Participant Names'}
        </h1>
        <p className="text-gray-600 mt-1">
          {t('subtitle') ||
            'Add names for all participants to receive individual vouchers with QR codes.'}
        </p>
      </div>

      {/* Booking Info */}
      <div className="bg-gray-50 rounded-lg p-4 mb-6">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm text-gray-600">{t('booking_number') || 'Booking'}</p>
            <p className="font-semibold text-gray-900">{booking.bookingNumber}</p>
          </div>
          <div className="text-right">
            <p className="text-sm text-gray-600">{t('progress') || 'Progress'}</p>
            <p className="font-semibold text-gray-900">
              {completeCount} / {participants.length} {t('completed') || 'completed'}
            </p>
          </div>
        </div>
        {requiresNames && !allComplete && (
          <div className="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
            <p className="text-sm text-amber-800">
              {t('names_required_notice') ||
                'This activity requires participant names. Please fill in all names to download your vouchers.'}
            </p>
          </div>
        )}
      </div>

      {/* Participant Form */}
      <form onSubmit={handleSubmit}>
        <div className="space-y-4">
          {formData.map((participant, index) => (
            <div key={participant.id} className="bg-white border border-gray-200 rounded-lg p-6">
              <div className="flex items-center justify-between mb-4">
                <h3 className="font-semibold text-gray-900">
                  {t('participant') || 'Participant'} {index + 1}
                  {participant.personType && (
                    <span className="ml-2 text-sm font-normal text-gray-500">
                      ({getPersonTypeLabel(participant.personType)})
                    </span>
                  )}
                </h3>
                {index === 0 && (
                  <span className="text-xs bg-primary/10 text-primary px-2 py-1 rounded">
                    {t('billing_contact') || 'Billing Contact'}
                  </span>
                )}
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    {t('first_name') || 'First Name'} *
                  </label>
                  <input
                    type="text"
                    value={participant.firstName}
                    onChange={(e) => handleInputChange(index, 'firstName', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    {t('last_name') || 'Last Name'} *
                  </label>
                  <input
                    type="text"
                    value={participant.lastName}
                    onChange={(e) => handleInputChange(index, 'lastName', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    {t('email') || 'Email'}
                  </label>
                  <input
                    type="email"
                    value={participant.email}
                    onChange={(e) => handleInputChange(index, 'email', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    {t('phone') || 'Phone'}
                  </label>
                  <input
                    type="tel"
                    value={participant.phone}
                    onChange={(e) => handleInputChange(index, 'phone', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                  />
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* Action Buttons */}
        <div className="mt-8 flex flex-col sm:flex-row gap-4">
          <button
            type="submit"
            disabled={updateMutation.isPending || !hasChanges}
            className="flex-1 px-6 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {updateMutation.isPending
              ? t('saving') || 'Saving...'
              : t('save_and_view_vouchers') || 'Save & View Vouchers'}
          </button>
          <Link
            href={`/dashboard/bookings/${bookingId}`}
            className="flex-1 px-6 py-3 border border-gray-300 rounded-lg font-medium text-center text-gray-700 hover:bg-gray-50 transition-colors"
          >
            {tCommon('cancel') || 'Cancel'}
          </Link>
        </div>

        {/* Error Display */}
        {updateMutation.isError && (
          <div className="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p className="text-sm text-red-800">
              {t('update_error') || 'Failed to update participants. Please try again.'}
            </p>
          </div>
        )}
      </form>
    </div>
  );
}
