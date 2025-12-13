'use client';

import { useState, useEffect, useCallback } from 'react';
import { useTranslations } from 'next-intl';
import { parseISO, differenceInSeconds } from 'date-fns';
import { Clock, AlertTriangle } from 'lucide-react';

interface HoldTimerProps {
  expiresAt: string; // ISO datetime string
  onExpire?: () => void;
  className?: string;
}

export default function HoldTimer({ expiresAt, onExpire, className = '' }: HoldTimerProps) {
  const t = useTranslations('availability');

  const calculateTimeRemaining = useCallback(() => {
    const now = new Date();
    const expiryDate = parseISO(expiresAt);
    const seconds = differenceInSeconds(expiryDate, now);
    return Math.max(0, seconds);
  }, [expiresAt]);

  const [timeRemaining, setTimeRemaining] = useState<number>(calculateTimeRemaining());

  useEffect(() => {
    // Update every second
    const interval = setInterval(() => {
      const remaining = calculateTimeRemaining();
      setTimeRemaining(remaining);

      if (remaining === 0) {
        clearInterval(interval);
        onExpire?.();
      }
    }, 1000);

    return () => clearInterval(interval);
  }, [calculateTimeRemaining, onExpire]);

  const formatTime = (seconds: number) => {
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${minutes}:${secs.toString().padStart(2, '0')}`;
  };

  const getProgressColor = () => {
    if (timeRemaining <= 60) return 'bg-red-500'; // Last minute - red
    if (timeRemaining <= 300) return 'bg-yellow-500'; // Last 5 minutes - yellow
    return 'bg-green-500'; // Green
  };

  const getBackgroundColor = () => {
    if (timeRemaining <= 60) return 'bg-red-50 border-red-300'; // Last minute
    if (timeRemaining <= 300) return 'bg-yellow-50 border-yellow-300'; // Last 5 minutes
    return 'bg-green-50 border-green-300';
  };

  const getTextColor = () => {
    if (timeRemaining <= 60) return 'text-red-900';
    if (timeRemaining <= 300) return 'text-yellow-900';
    return 'text-green-900';
  };

  const shouldPulse = timeRemaining <= 60;
  const totalDuration = 15 * 60; // 15 minutes in seconds
  const progressPercent = (timeRemaining / totalDuration) * 100;

  if (timeRemaining === 0) {
    return (
      <div className={`rounded-lg border-2 border-red-300 bg-red-50 p-4 ${className}`}>
        <div className="flex items-center gap-3">
          <AlertTriangle className="h-5 w-5 text-red-600" />
          <div>
            <div className="font-semibold text-red-900">{t('hold_expired')}</div>
            <div className="text-sm text-red-700">{t('hold_expired_message')}</div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className={`rounded-lg border-2 ${getBackgroundColor()} p-4 ${className}`}>
      <div className="flex items-center gap-3">
        <div className={`${shouldPulse ? 'animate-pulse' : ''}`}>
          <Clock className={`h-5 w-5 ${getTextColor()}`} />
        </div>
        <div className="flex-1">
          <div className="mb-1 flex items-center justify-between">
            <span className={`font-semibold ${getTextColor()}`}>{t('hold_expires_in')}</span>
            <span className={`text-lg font-bold ${getTextColor()}`}>
              {formatTime(timeRemaining)}
            </span>
          </div>

          {/* Progress bar */}
          <div className="h-2 w-full overflow-hidden rounded-full bg-white">
            <div
              className={`h-full transition-all duration-1000 ${getProgressColor()} ${shouldPulse ? 'animate-pulse' : ''}`}
              style={{ width: `${progressPercent}%` }}
            />
          </div>

          {timeRemaining <= 300 && (
            <div className={`mt-1 text-xs ${getTextColor()}`}>
              {timeRemaining <= 60 ? t('hurry_last_minute') : t('complete_booking_soon')}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
