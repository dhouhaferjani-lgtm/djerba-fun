'use client';

import { useTranslations } from 'next-intl';
import { Info, Calendar, RefreshCcw } from 'lucide-react';
import { Badge } from '@go-adventure/ui';
import type { CancellationPolicy } from '@go-adventure/schemas';

interface CancellationPolicyCardProps {
  policy: CancellationPolicy | null | undefined;
}

export function CancellationPolicyCard({ policy }: CancellationPolicyCardProps) {
  const t = useTranslations('listing.cancellation');

  if (!policy) {
    return null;
  }

  // Handle missing or invalid policy type
  const validTypes = ['flexible', 'moderate', 'strict', 'non_refundable'];
  const hasValidType = policy.type && validTypes.includes(policy.type);

  // Get variant color based on policy type
  const getPolicyVariant = (type: string) => {
    switch (type) {
      case 'flexible':
        return 'success';
      case 'moderate':
        return 'warning';
      case 'strict':
        return 'error';
      case 'non_refundable':
        return 'error';
      default:
        return 'default';
    }
  };

  // Format hours to human-readable time
  const formatTimeBeforeStart = (hours: number): string => {
    if (hours === 0) {
      return t('atStartTime');
    } else if (hours < 24) {
      return t('hoursBeforeStart', { hours });
    } else {
      const days = Math.floor(hours / 24);
      return t('daysBeforeStart', { days });
    }
  };

  return (
    <section className="space-y-6">
      <div className="flex items-center gap-3">
        <RefreshCcw className="h-6 w-6 text-primary" />
        <h2 className="font-display text-2xl font-bold text-heading tracking-tight">
          {t('title')}
        </h2>
      </div>

      <div className="space-y-4">
        {/* Policy type badge - only show if valid type exists */}
        {hasValidType && (
          <div>
            <Badge variant={getPolicyVariant(policy.type!)} className="text-sm font-semibold">
              {t(`type.${policy.type}`)}
            </Badge>
          </div>
        )}

        {/* Description */}
        {policy.description && (
          <p className="font-sans text-neutral-700">
            {typeof policy.description === 'string'
              ? policy.description
              : policy.description.en || policy.description.fr || ''}
          </p>
        )}

        {/* Rules timeline */}
        {policy.rules && policy.rules.length > 0 && (
          <div className="space-y-3">
            <div className="flex items-center gap-2 text-sm font-medium text-neutral-700">
              <Calendar className="h-4 w-4" />
              <span>{t('refundSchedule')}</span>
            </div>

            <ul className="space-y-2">
              {policy.rules
                .sort((a, b) => b.hoursBeforeStart - a.hoursBeforeStart)
                .map((rule, index) => (
                  <li key={index} className="flex items-start gap-3">
                    <div className="flex-shrink-0 w-2 h-2 rounded-full bg-primary mt-1.5" />
                    <div className="flex-1">
                      <div className="flex items-baseline gap-2 flex-wrap">
                        <span className="font-sans text-sm text-neutral-700">
                          {formatTimeBeforeStart(rule.hoursBeforeStart)}:
                        </span>
                        <span className="font-sans text-sm font-semibold text-primary">
                          {rule.refundPercent}% {t('refund')}
                        </span>
                      </div>
                    </div>
                  </li>
                ))}
            </ul>
          </div>
        )}

        {/* Info footer */}
        <div className="flex items-start gap-2 p-3 bg-accent-dark/30 rounded-lg">
          <Info className="h-4 w-4 text-neutral-500 flex-shrink-0 mt-0.5" />
          <p className="font-sans text-xs text-neutral-600">{t('disclaimer')}</p>
        </div>
      </div>
    </section>
  );
}
