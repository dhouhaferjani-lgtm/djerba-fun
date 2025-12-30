'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { useRouter } from '@/i18n/navigation';
import { useCurrentUser } from '@/lib/api/hooks';
import { ProfileForm } from '@/components/profile/ProfileForm';
import { PasswordChangeForm } from '@/components/profile/PasswordChangeForm';
import { PreferencesForm } from '@/components/profile/PreferencesForm';
import { DeleteAccountSection } from '@/components/profile/DeleteAccountSection';

type Tab = 'profile' | 'password' | 'preferences' | 'delete';

export default function ProfilePage() {
  const t = useTranslations('profile');
  const router = useRouter();
  const { data: user, isLoading } = useCurrentUser();
  const [activeTab, setActiveTab] = useState<Tab>('profile');

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>
    );
  }

  if (!user) {
    router.push('/auth/login');
    return null;
  }

  const tabs: { id: Tab; label: string }[] = [
    { id: 'profile', label: t('edit_profile') },
    { id: 'password', label: t('change_password') },
    { id: 'preferences', label: t('preferences') },
    { id: 'delete', label: t('delete_account') },
  ];

  return (
    <div className="min-h-screen bg-gray-50 py-12">
      <div className="container mx-auto px-4">
        <div className="max-w-4xl mx-auto">
          {/* Header */}
          <div className="mb-8">
            <h1 className="text-3xl font-bold text-gray-900 mb-2">{t('title')}</h1>
            <p className="text-gray-600">{user.email}</p>
          </div>

          {/* Tabs */}
          <div className="bg-white rounded-lg shadow-sm border border-gray-200">
            <div className="border-b border-gray-200">
              <nav className="flex -mb-px overflow-x-auto">
                {tabs.map((tab) => (
                  <button
                    key={tab.id}
                    onClick={() => setActiveTab(tab.id)}
                    className={`
                      flex-shrink-0 px-6 py-4 text-sm font-medium border-b-2 transition-colors
                      ${
                        activeTab === tab.id
                          ? 'border-primary text-primary'
                          : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                      }
                    `}
                  >
                    {tab.label}
                  </button>
                ))}
              </nav>
            </div>

            {/* Tab Content */}
            <div className="p-6">
              {activeTab === 'profile' && <ProfileForm user={user} />}
              {activeTab === 'password' && <PasswordChangeForm />}
              {activeTab === 'preferences' && <PreferencesForm />}
              {activeTab === 'delete' && <DeleteAccountSection />}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
