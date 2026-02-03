'use client';

import { useTranslations } from 'next-intl';
import { Check, AlertCircle, Clock } from 'lucide-react';

interface StatusBadgeProps {
  completed: number;
  total: number;
  isSaved: boolean;
}

/**
 * Visual status indicator for participant completion.
 * Shows different states: saved, complete, in-progress, not started.
 */
export function StatusBadge({ completed, total, isSaved }: StatusBadgeProps) {
  const t = useTranslations('booking.participants');

  // Determine state
  const isComplete = completed === total;
  const isPartial = completed > 0 && completed < total;
  const isNotStarted = completed === 0;

  if (isSaved) {
    return (
      <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-success/10 text-success">
        <Check className="w-3.5 h-3.5" />
        {t('saved') || 'Saved'}
      </span>
    );
  }

  if (isComplete) {
    return (
      <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary">
        <Check className="w-3.5 h-3.5" />
        {completed}/{total} {t('complete') || 'complete'}
      </span>
    );
  }

  if (isPartial) {
    return (
      <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
        <Clock className="w-3.5 h-3.5" />
        {completed}/{total} {t('incomplete') || 'incomplete'}
      </span>
    );
  }

  // Not started
  return (
    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-neutral-100 text-neutral-600">
      <AlertCircle className="w-3.5 h-3.5" />
      {t('not_started') || 'Not started'}
    </span>
  );
}
