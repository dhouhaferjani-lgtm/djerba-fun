'use client';

import { useTranslations } from 'next-intl';
import { Accessibility, CheckCircle, X } from 'lucide-react';
import type { AccessibilityInfo } from '@djerba-fun/schemas';

interface AccessibilitySectionProps {
  accessibility: AccessibilityInfo | null | undefined;
}

interface AccessibilityFeature {
  key: keyof Omit<AccessibilityInfo, 'accessibilityNotes'>;
  labelKey: string;
  available: boolean;
}

export function AccessibilitySection({ accessibility }: AccessibilitySectionProps) {
  const t = useTranslations('listing.accessibility');

  if (!accessibility) {
    return null;
  }

  const features: AccessibilityFeature[] = [
    {
      key: 'wheelchairAccessible',
      labelKey: 'wheelchairAccessible',
      available: accessibility.wheelchairAccessible,
    },
    {
      key: 'mobilityAidAccessible',
      labelKey: 'mobilityAidAccessible',
      available: accessibility.mobilityAidAccessible,
    },
    {
      key: 'accessibleParking',
      labelKey: 'accessibleParking',
      available: accessibility.accessibleParking,
    },
    {
      key: 'accessibleRestrooms',
      labelKey: 'accessibleRestrooms',
      available: accessibility.accessibleRestrooms,
    },
    {
      key: 'serviceAnimalsAllowed',
      labelKey: 'serviceAnimalsAllowed',
      available: accessibility.serviceAnimalsAllowed,
    },
  ];

  // Check if there's any accessibility info to show
  const hasFeatures = features.some((f) => f.available);
  const hasNotes = accessibility.accessibilityNotes;

  if (!hasFeatures && !hasNotes) {
    return null;
  }

  return (
    <section>
      <div className="flex items-center gap-3 mb-6">
        <Accessibility className="h-6 w-6 text-primary" />
        <h3 className="font-display text-2xl font-bold text-heading tracking-tight">
          {t('title')}
        </h3>
      </div>

      {/* Feature grid */}
      {hasFeatures && (
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          {features.map((feature) => (
            <div key={feature.key} className="flex items-center gap-2">
              {feature.available ? (
                <CheckCircle className="h-4 w-4 text-primary flex-shrink-0" />
              ) : (
                <X className="h-4 w-4 text-neutral-400 flex-shrink-0" />
              )}
              <span
                className={`font-sans text-sm ${feature.available ? 'text-neutral-700 font-medium' : 'text-neutral-500'}`}
              >
                {t(feature.labelKey)}
              </span>
            </div>
          ))}
        </div>
      )}

      {/* Accessibility notes */}
      {hasNotes && (
        <div className="pt-4 border-t border-neutral-200">
          <p className="font-sans text-sm text-neutral-700">
            {typeof accessibility.accessibilityNotes === 'string'
              ? accessibility.accessibilityNotes
              : accessibility.accessibilityNotes?.en || accessibility.accessibilityNotes?.fr || ''}
          </p>
        </div>
      )}
    </section>
  );
}
