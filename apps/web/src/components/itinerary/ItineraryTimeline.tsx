'use client';

import { useTranslations } from 'next-intl';
import type { ItineraryStop } from '@go-adventure/schemas';
import { MapPin, Clock } from 'lucide-react';

// Brand-aligned day colors: Navy, Emerald, Gold, Orange + complementary
const DAY_COLORS = ['#1B2A4E', '#2E9E6B', '#F5B041', '#E05D26', '#3a5a8c', '#4ade9a', '#ca8a04'];

interface ItineraryTimelineProps {
  stops: ItineraryStop[];
  locale: string;
  isAccommodation?: boolean;
}

export default function ItineraryTimeline({
  stops,
  locale,
  isAccommodation,
}: ItineraryTimelineProps) {
  const t = useTranslations('itinerary');
  const tListing = useTranslations('listing');

  const sortedStops = [...stops].sort((a, b) => a.order - b.order);

  const formatDuration = (minutes: number | null) => {
    if (!minutes) return null;
    if (minutes < 60) return `${minutes}m`;
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
  };

  const calculateDistance = (stop: ItineraryStop, prevStop: ItineraryStop | null) => {
    if (!prevStop) return null;

    // Haversine formula for distance calculation
    const R = 6371; // Earth's radius in km
    const dLat = ((stop.lat - prevStop.lat) * Math.PI) / 180;
    const dLon = ((stop.lng - prevStop.lng) * Math.PI) / 180;
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos((prevStop.lat * Math.PI) / 180) *
        Math.cos((stop.lat * Math.PI) / 180) *
        Math.sin(dLon / 2) *
        Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distance = R * c;

    return distance.toFixed(1);
  };

  const renderStop = (
    stop: ItineraryStop,
    index: number,
    prevStop: ItineraryStop | null,
    color?: string
  ) => {
    const distance = calculateDistance(stop, prevStop);
    const title = typeof stop.title === 'string' ? stop.title : stop.title[locale as 'en' | 'fr'];
    const description =
      stop.description && typeof stop.description !== 'string'
        ? stop.description[locale as 'en' | 'fr']
        : stop.description;

    return (
      <div key={stop.id} className="relative pl-12">
        {/* Stop marker */}
        <div
          className="absolute left-0 top-0 flex h-8 w-8 items-center justify-center rounded-full border-4 border-white shadow-md"
          style={{ backgroundColor: color || '#1B2A4E' }}
        >
          <span className="text-xs font-bold text-white">{stop.order + 1}</span>
        </div>

        {/* Distance from previous stop */}
        {distance && (
          <div className="mb-1 text-xs text-neutral-500">
            <span className="inline-flex items-center gap-1">
              <MapPin className="h-3 w-3" />
              {distance} km {t('from_previous')}
            </span>
          </div>
        )}

        {/* Stop content */}
        <div className="rounded-lg border border-neutral-200 bg-white p-4">
          <div className="mb-2 flex items-start justify-between gap-4">
            <h4 className="font-semibold text-neutral-900">{title}</h4>
            {stop.durationMinutes && (
              <span className="inline-flex items-center gap-1 whitespace-nowrap text-sm text-neutral-600">
                <Clock className="h-4 w-4" />
                {formatDuration(stop.durationMinutes)}
              </span>
            )}
          </div>

          {description && <p className="text-sm text-neutral-600">{description}</p>}

          {stop.elevationMeters !== null && (
            <div className="mt-2 text-xs text-neutral-500">
              {t('elevation')}: {stop.elevationMeters}m
            </div>
          )}

          {/* Photos */}
          {stop.photos.length > 0 && (
            <div className="mt-3 grid grid-cols-3 gap-2">
              {stop.photos.slice(0, 3).map((photo, photoIndex) => (
                <div key={photoIndex} className="relative aspect-square overflow-hidden rounded">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img src={photo.url} alt={photo.alt} className="h-full w-full object-cover" />
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    );
  };

  // Séjour: group stops by day
  if (isAccommodation) {
    const dayGroups = new Map<number, ItineraryStop[]>();
    for (const stop of sortedStops) {
      const day = (stop as any).day ?? 1;
      if (!dayGroups.has(day)) {
        dayGroups.set(day, []);
      }
      dayGroups.get(day)!.push(stop);
    }

    const days = Array.from(dayGroups.keys()).sort((a, b) => a - b);

    return (
      <div className="space-y-6">
        <h3 className="text-xl font-semibold text-neutral-900">{tListing('day_by_day_program')}</h3>

        <div className="space-y-10">
          {days.map((day, dayIndex) => {
            const dayStops = dayGroups.get(day)!;
            const color = DAY_COLORS[dayIndex % DAY_COLORS.length];

            return (
              <div key={day}>
                {/* Day header */}
                <div className="flex items-center gap-3 mb-6">
                  <div
                    className="flex h-10 w-10 items-center justify-center rounded-full text-white font-bold text-sm shadow-md"
                    style={{ backgroundColor: color }}
                  >
                    {day}
                  </div>
                  <h4 className="text-lg font-bold text-neutral-900">
                    {tListing('day_number', { day })}
                  </h4>
                  <div
                    className="flex-1 h-0.5 rounded"
                    style={{ backgroundColor: color, opacity: 0.3 }}
                  />
                </div>

                {/* Day stops */}
                <div className="relative">
                  {/* Vertical line in day color */}
                  <div
                    className="absolute left-4 top-0 bottom-0 w-0.5"
                    style={{ backgroundColor: color, opacity: 0.4 }}
                  />

                  <div className="space-y-8">
                    {dayStops.map((stop, index) => {
                      const prevStop = index > 0 ? dayStops[index - 1] : null;
                      return renderStop(stop, index, prevStop, color);
                    })}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    );
  }

  // Standard flat timeline for tours/events
  return (
    <div className="space-y-6">
      <h3 className="text-xl font-semibold text-neutral-900">{t('title')}</h3>

      <div className="relative">
        {/* Vertical line */}
        <div className="absolute left-4 top-0 bottom-0 w-0.5 bg-neutral-200" />

        <div className="space-y-8">
          {sortedStops.map((stop, index) => {
            const prevStop = index > 0 ? sortedStops[index - 1] : null;
            return renderStop(stop, index, prevStop);
          })}
        </div>
      </div>
    </div>
  );
}
