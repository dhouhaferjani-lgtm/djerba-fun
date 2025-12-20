'use client';

import { useState, useEffect } from 'react';
import { useTranslations } from 'next-intl';
import { Link } from '@/i18n/navigation';
import { consentApi } from '@/lib/api/client';
import { getGuestSessionId } from '@/lib/utils/session';

const COOKIE_CONSENT_KEY = 'cookie_consent';

interface CookiePreferences {
  essential: boolean;
  analytics: boolean;
  marketing: boolean;
}

export default function CookieConsentBanner() {
  const t = useTranslations('cookies');
  const [isVisible, setIsVisible] = useState(false);
  const [showDetails, setShowDetails] = useState(false);
  const [preferences, setPreferences] = useState<CookiePreferences>({
    essential: true,
    analytics: false,
    marketing: false,
  });

  useEffect(() => {
    // Check if consent has already been given
    const savedConsent = localStorage.getItem(COOKIE_CONSENT_KEY);
    if (!savedConsent) {
      // Delay showing banner slightly for better UX
      const timer = setTimeout(() => setIsVisible(true), 1000);
      return () => clearTimeout(timer);
    }
  }, []);

  const savePreferences = async (prefs: CookiePreferences) => {
    // Save to localStorage
    localStorage.setItem(COOKIE_CONSENT_KEY, JSON.stringify(prefs));

    // Record consent via API
    const sessionId = getGuestSessionId();
    try {
      await consentApi.recordConsents(
        {
          cookies_essential: prefs.essential,
          cookies_analytics: prefs.analytics,
          cookies_marketing: prefs.marketing,
        },
        {
          sessionId,
          context: 'cookie_banner',
        }
      );
    } catch (error) {
      console.error('Failed to record cookie consent:', error);
    }

    setIsVisible(false);
  };

  const handleAcceptAll = () => {
    const allAccepted = {
      essential: true,
      analytics: true,
      marketing: true,
    };
    setPreferences(allAccepted);
    savePreferences(allAccepted);
  };

  const handleAcceptEssential = () => {
    const essentialOnly = {
      essential: true,
      analytics: false,
      marketing: false,
    };
    setPreferences(essentialOnly);
    savePreferences(essentialOnly);
  };

  const handleSavePreferences = () => {
    savePreferences(preferences);
  };

  if (!isVisible) {
    return null;
  }

  return (
    <div className="fixed bottom-0 left-0 right-0 z-50 p-4 md:p-6 bg-white border-t shadow-lg">
      <div className="max-w-7xl mx-auto">
        <div className="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
          {/* Main Content */}
          <div className="flex-1">
            <h3 className="text-lg font-semibold text-gray-900 mb-2">
              {t('banner_title') || 'Cookie Preferences'}
            </h3>
            <p className="text-sm text-gray-600 mb-4">
              {t('banner_description') ||
                'We use cookies to enhance your browsing experience, analyze site traffic, and personalize content. You can choose which cookies you want to allow.'}
            </p>

            {/* Cookie Categories (expanded view) */}
            {showDetails && (
              <div className="space-y-3 mb-4 p-4 bg-gray-50 rounded-lg">
                {/* Essential */}
                <div className="flex items-start justify-between">
                  <div>
                    <p className="font-medium text-gray-900">
                      {t('essential_title') || 'Essential Cookies'}
                    </p>
                    <p className="text-xs text-gray-500">
                      {t('essential_description') ||
                        'Required for the website to function. Cannot be disabled.'}
                    </p>
                  </div>
                  <input
                    type="checkbox"
                    checked={preferences.essential}
                    disabled
                    className="h-4 w-4 rounded border-gray-300 text-primary cursor-not-allowed opacity-50"
                  />
                </div>

                {/* Analytics */}
                <div className="flex items-start justify-between">
                  <div>
                    <p className="font-medium text-gray-900">
                      {t('analytics_title') || 'Analytics Cookies'}
                    </p>
                    <p className="text-xs text-gray-500">
                      {t('analytics_description') ||
                        'Help us understand how visitors interact with our website.'}
                    </p>
                  </div>
                  <input
                    type="checkbox"
                    checked={preferences.analytics}
                    onChange={(e) =>
                      setPreferences({ ...preferences, analytics: e.target.checked })
                    }
                    className="h-4 w-4 rounded border-gray-300 text-primary cursor-pointer"
                  />
                </div>

                {/* Marketing */}
                <div className="flex items-start justify-between">
                  <div>
                    <p className="font-medium text-gray-900">
                      {t('marketing_title') || 'Marketing Cookies'}
                    </p>
                    <p className="text-xs text-gray-500">
                      {t('marketing_description') ||
                        'Used to deliver personalized advertisements relevant to you.'}
                    </p>
                  </div>
                  <input
                    type="checkbox"
                    checked={preferences.marketing}
                    onChange={(e) =>
                      setPreferences({ ...preferences, marketing: e.target.checked })
                    }
                    className="h-4 w-4 rounded border-gray-300 text-primary cursor-pointer"
                  />
                </div>
              </div>
            )}

            {/* Toggle Details Button */}
            <button
              onClick={() => setShowDetails(!showDetails)}
              className="text-sm text-primary hover:underline"
            >
              {showDetails
                ? t('hide_details') || 'Hide details'
                : t('customize') || 'Customize preferences'}
            </button>

            {/* Cookie Policy Link */}
            <span className="mx-2 text-gray-300">|</span>
            <Link href="/cookies" className="text-sm text-primary hover:underline">
              {t('cookie_policy') || 'Cookie Policy'}
            </Link>
          </div>

          {/* Action Buttons */}
          <div className="flex flex-col sm:flex-row gap-2 lg:ml-6">
            {showDetails ? (
              <button
                onClick={handleSavePreferences}
                className="px-6 py-2.5 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors"
              >
                {t('save_preferences') || 'Save Preferences'}
              </button>
            ) : (
              <>
                <button
                  onClick={handleAcceptEssential}
                  className="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors"
                >
                  {t('essential_only') || 'Essential Only'}
                </button>
                <button
                  onClick={handleAcceptAll}
                  className="px-6 py-2.5 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors"
                >
                  {t('accept_all') || 'Accept All'}
                </button>
              </>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
