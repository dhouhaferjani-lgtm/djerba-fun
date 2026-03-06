'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { useRouter } from '@/i18n/navigation';
import { Button } from '@djerba-fun/ui';
import { useDeleteAccount, useExportData } from '@/lib/api/hooks';

export function DeleteAccountSection() {
  const t = useTranslations('profile');
  const router = useRouter();
  const [showConfirmation, setShowConfirmation] = useState(false);
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);
  const deleteAccount = useDeleteAccount();
  const exportData = useExportData();

  const handleExportData = async () => {
    setMessage(null);
    try {
      const response = await exportData.mutateAsync();
      const dataStr = JSON.stringify(response.data, null, 2);
      const dataBlob = new Blob([dataStr], { type: 'application/json' });
      const url = URL.createObjectURL(dataBlob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `go-adventure-data-${new Date().toISOString().split('T')[0]}.json`;
      link.click();
      URL.revokeObjectURL(url);
      setMessage({ type: 'success', text: t('data_exported') });
    } catch (error) {
      setMessage({ type: 'error', text: t('delete_error') });
    }
  };

  const handleDeleteAccount = async () => {
    try {
      await deleteAccount.mutateAsync();
      setMessage({ type: 'success', text: t('account_deleted') });
      setTimeout(() => router.push('/'), 1500);
    } catch (error) {
      setMessage({ type: 'error', text: t('delete_error') });
    }
  };

  return (
    <div className="space-y-6 max-w-2xl">
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

      {!showConfirmation ? (
        <>
          {/* Export Data Section */}
          <div className="border border-gray-200 rounded-lg p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-2">{t('export_data')}</h3>
            <p className="text-sm text-gray-600 mb-4">{t('export_data_desc')}</p>
            <Button
              type="button"
              variant="outline"
              onClick={handleExportData}
              isLoading={exportData.isPending}
            >
              {t('export_data')}
            </Button>
          </div>

          {/* Delete Account Section */}
          <div className="border border-error rounded-lg p-6 bg-error-light/5">
            <h3 className="text-lg font-semibold text-error mb-2">{t('delete_account_title')}</h3>
            <p className="text-sm text-error-dark mb-4">{t('delete_account_warning')}</p>

            <div className="mb-6">
              <p className="text-sm font-medium text-gray-900 mb-2">
                {t('delete_account_description')}
              </p>
              <ul className="space-y-2 text-sm text-gray-600">
                <li className="flex items-start gap-2">
                  <svg
                    className="w-5 h-5 text-error flex-shrink-0 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                  {t('delete_bullet_1')}
                </li>
                <li className="flex items-start gap-2">
                  <svg
                    className="w-5 h-5 text-error flex-shrink-0 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                  {t('delete_bullet_2')}
                </li>
                <li className="flex items-start gap-2">
                  <svg
                    className="w-5 h-5 text-error flex-shrink-0 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                  {t('delete_bullet_3')}
                </li>
                <li className="flex items-start gap-2">
                  <svg
                    className="w-5 h-5 text-error flex-shrink-0 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                  {t('delete_bullet_4')}
                </li>
              </ul>
            </div>

            <Button type="button" variant="destructive" onClick={() => setShowConfirmation(true)}>
              {t('delete_account')}
            </Button>
          </div>
        </>
      ) : (
        <div className="border border-error rounded-lg p-6 bg-error-light/10">
          <h3 className="text-xl font-bold text-error mb-4">{t('delete_account_title')}</h3>
          <p className="text-gray-700 mb-6">{t('delete_account_warning')}</p>

          <div className="flex gap-3">
            <Button
              type="button"
              variant="destructive"
              onClick={handleDeleteAccount}
              isLoading={deleteAccount.isPending}
            >
              {deleteAccount.isPending ? t('deleting') : t('confirm_delete')}
            </Button>
            <Button
              type="button"
              variant="outline"
              onClick={() => setShowConfirmation(false)}
              disabled={deleteAccount.isPending}
            >
              {t('keep_account')}
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}
