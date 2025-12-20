'use client';

import { useTranslations } from 'next-intl';
import { Shield, X, CheckCircle, AlertCircle } from 'lucide-react';
import { Badge } from '@go-adventure/ui';
import type { SafetyInfo } from '@go-adventure/schemas';

interface SafetySectionProps {
  safety: SafetyInfo | null | undefined;
}

export function SafetySection({ safety }: SafetySectionProps) {
  const t = useTranslations('listing.safety');

  if (!safety) {
    return null;
  }

  const hasAgeRestrictions = safety.minimumAge !== null || safety.maximumAge !== null;

  return (
    <section>
      <div className="flex items-center gap-3 mb-6">
        <Shield className="h-6 w-6 text-primary" />
        <h3 className="font-display text-2xl font-bold text-heading tracking-tight">
          {t('title')}
        </h3>
      </div>

      <div className="space-y-4">
        {/* Age restrictions */}
        {hasAgeRestrictions && (
          <div className="flex items-center gap-2">
            <AlertCircle className="h-4 w-4 text-warning" />
            <span className="font-sans text-sm font-medium">
              {safety.minimumAge !== null && safety.maximumAge !== null
                ? t('ageRange', { min: safety.minimumAge, max: safety.maximumAge })
                : safety.minimumAge !== null
                  ? t('minimumAge', { age: safety.minimumAge })
                  : safety.maximumAge !== null
                    ? t('maximumAge', { age: safety.maximumAge })
                    : ''}
            </span>
          </div>
        )}

        {/* Required fitness level */}
        {safety.requiredFitnessLevel && (
          <div className="space-y-1">
            <p className="font-sans text-sm font-medium text-neutral-700">{t('fitnessLevel')}</p>
            <p className="font-sans text-sm text-neutral-600">
              {typeof safety.requiredFitnessLevel === 'string'
                ? safety.requiredFitnessLevel
                : safety.requiredFitnessLevel.en || safety.requiredFitnessLevel.fr || ''}
            </p>
          </div>
        )}

        {/* Insurance required */}
        {safety.insuranceRequired && (
          <div className="flex items-center gap-2">
            <Badge variant="secondary">{t('insuranceRequired')}</Badge>
          </div>
        )}

        {/* Safety equipment provided */}
        {safety.safetyEquipmentProvided && safety.safetyEquipmentProvided.length > 0 && (
          <div className="space-y-2">
            <p className="font-sans text-sm font-medium text-neutral-700">
              {t('equipmentProvided')}
            </p>
            <ul className="space-y-1">
              {safety.safetyEquipmentProvided.map((equipment, index) => (
                <li
                  key={index}
                  className="flex items-center gap-2 font-sans text-sm text-neutral-600"
                >
                  <CheckCircle className="h-4 w-4 text-primary flex-shrink-0" />
                  <span>
                    {typeof equipment === 'string' ? equipment : equipment.en || equipment.fr || ''}
                  </span>
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* Not suitable for */}
        {safety.notSuitableFor && safety.notSuitableFor.length > 0 && (
          <div className="space-y-2">
            <p className="font-sans text-sm font-medium text-neutral-700">{t('notSuitableFor')}</p>
            <ul className="space-y-1">
              {safety.notSuitableFor.map((item, index) => (
                <li
                  key={index}
                  className="flex items-center gap-2 font-sans text-sm text-neutral-600"
                >
                  <X className="h-4 w-4 text-error flex-shrink-0" />
                  <span>{typeof item === 'string' ? item : item.en || item.fr || ''}</span>
                </li>
              ))}
            </ul>
          </div>
        )}
      </div>
    </section>
  );
}
