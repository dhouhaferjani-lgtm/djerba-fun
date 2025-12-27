'use client';

import { useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { Link, useRouter } from '@/i18n/navigation';
import { magicLinksApi, type MagicLinkParticipant } from '@/lib/api/client';

interface ParticipantFormData {
  id: string;
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  personType: string | null;
}

export default function MagicLinkParticipantsPage() {
  const params = useParams();
  const router = useRouter();
  const token = params.token as string;
  const t = useTranslations('participants');
  const tCommon = useTranslations('common');

  const [formData, setFormData] = useState<ParticipantFormData[]>([]);
  const [meta, setMeta] = useState<{
    bookingNumber: string;
    requiresNames: boolean;
    totalParticipants: number;
    completeParticipants: number;
  } | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [hasChanges, setHasChanges] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [saveError, setSaveError] = useState<string | null>(null);

  useEffect(() => {
    const fetchParticipants = async () => {
      try {
        const response = await magicLinksApi.getParticipants(token);
        setFormData(
          response.data.map((p: MagicLinkParticipant) => ({
            id: p.id,
            firstName: p.firstName || '',
            lastName: p.lastName || '',
            email: p.email || '',
            phone: p.phone || '',
            personType: p.personType,
          }))
        );
        setMeta(response.meta);
      } catch (err: unknown) {
        const apiError = err as { status?: number; message?: string };
        if (apiError.status === 410 || apiError.status === 404) {
          setError('This link is invalid or has expired.');
        } else {
          setError(apiError.message || 'Failed to load participants.');
        }
      } finally {
        setIsLoading(false);
      }
    };

    fetchParticipants();
  }, [token]);

  const handleInputChange = (index: number, field: keyof ParticipantFormData, value: string) => {
    setFormData((prev) => {
      const newData = [...prev];
      newData[index] = { ...newData[index], [field]: value };
      return newData;
    });
    setHasChanges(true);
    setSaveError(null);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSaving(true);
    setSaveError(null);

    try {
      await magicLinksApi.updateParticipants(
        token,
        formData.map((p) => ({
          id: p.id,
          first_name: p.firstName,
          last_name: p.lastName,
          email: p.email || undefined,
          phone: p.phone || undefined,
        }))
      );
      setHasChanges(false);
      router.push(`/booking/${token}/vouchers`);
    } catch (err: unknown) {
      const apiError = err as { message?: string };
      setSaveError(apiError.message || 'Failed to save participants. Please try again.');
    } finally {
      setIsSaving(false);
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

  if (isLoading) {
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

  if (error) {
    return (
      <div className="max-w-3xl mx-auto p-6">
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
          <div className="w-16 h-16 bg-error-light rounded-full flex items-center justify-center mx-auto mb-4">
            <svg
              className="w-8 h-8 text-error-dark"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
              />
            </svg>
          </div>
          <h2 className="text-xl font-bold text-gray-900 mb-2">{error}</h2>
          <div className="space-y-3 mt-6">
            <Link
              href="/booking/recover"
              className="block w-full px-6 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors text-center"
            >
              Request New Link
            </Link>
          </div>
        </div>
      </div>
    );
  }

  const completeCount = formData.filter((p) => p.firstName && p.lastName).length;
  const allComplete = completeCount === formData.length;

  return (
    <div className="max-w-3xl mx-auto p-6">
      {/* Header */}
      <div className="mb-8">
        <Link
          href={`/booking/${token}`}
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
      {meta && (
        <div className="bg-gray-50 rounded-lg p-4 mb-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600">{t('booking_number') || 'Booking'}</p>
              <p className="font-semibold text-gray-900">{meta.bookingNumber}</p>
            </div>
            <div className="text-right">
              <p className="text-sm text-gray-600">{t('progress') || 'Progress'}</p>
              <p className="font-semibold text-gray-900">
                {completeCount} / {formData.length} {t('completed') || 'completed'}
              </p>
            </div>
          </div>
          {meta.requiresNames && !allComplete && (
            <div className="mt-3 p-3 bg-warning-light border border-warning rounded-lg">
              <p className="text-sm text-warning-dark">
                {t('names_required_notice') ||
                  'This activity requires participant names. Please fill in all names to download your vouchers.'}
              </p>
            </div>
          )}
        </div>
      )}

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
                    {t('billing_contact') || 'Primary Contact'}
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

        {/* Error Display */}
        {saveError && (
          <div className="mt-4 p-4 bg-error-light border border-error rounded-lg">
            <p className="text-sm text-error-dark">{saveError}</p>
          </div>
        )}

        {/* Action Buttons */}
        <div className="mt-8 flex flex-col sm:flex-row gap-4">
          <button
            type="submit"
            disabled={isSaving || !hasChanges}
            className="flex-1 px-6 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isSaving
              ? t('saving') || 'Saving...'
              : t('save_and_view_vouchers') || 'Save & View Vouchers'}
          </button>
          <Link
            href={`/booking/${token}`}
            className="flex-1 px-6 py-3 border border-gray-300 rounded-lg font-medium text-center text-gray-700 hover:bg-gray-50 transition-colors"
          >
            {tCommon('cancel') || 'Cancel'}
          </Link>
        </div>
      </form>
    </div>
  );
}
