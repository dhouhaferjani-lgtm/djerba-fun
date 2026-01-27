'use client';

import { useTranslations } from 'next-intl';
import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { MapPin, Calendar, Compass } from 'lucide-react';
import { InputWithIcon } from '../atoms/InputWithIcon';
import { SelectWithIcon } from '../atoms/SelectWithIcon';
import { Button } from '@go-adventure/ui';

// CSS for shining light sweep animation and click pulse effect
const shineAnimationStyles = `
  @keyframes shine {
    0% {
      left: -100%;
    }
    50%, 100% {
      left: 100%;
    }
  }

  @keyframes pulse-click {
    0%, 100% {
      transform: scale(1);
      box-shadow: 0 4px 15px rgba(13, 100, 46, 0.4);
    }
    50% {
      transform: scale(0.97);
      box-shadow: 0 2px 8px rgba(13, 100, 46, 0.6);
    }
  }

  .shine-button {
    position: relative;
    overflow: hidden;
    animation: pulse-click 2s ease-in-out infinite;
  }

  .shine-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 50%;
    height: 100%;
    background: linear-gradient(
      90deg,
      transparent,
      rgba(255, 255, 255, 0.4),
      transparent
    );
    animation: shine 2.5s infinite;
    pointer-events: none;
  }
`;

interface HeroSearchFormProps {
  locale: string;
}

export function HeroSearchForm({ locale }: HeroSearchFormProps) {
  const t = useTranslations('home');
  const router = useRouter();

  const [destination, setDestination] = useState('');
  const [activityType, setActivityType] = useState('');
  const [dateRange, setDateRange] = useState('');

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    const params = new URLSearchParams();
    if (destination) params.set('location', destination);
    if (activityType) params.set('type', activityType);
    if (dateRange) params.set('date', dateRange);
    router.push(`/${locale}/listings?${params.toString()}`);
  };

  const destinationsOptions = [
    { value: 'djerba', label: 'Djerba' },
    { value: 'sahara-desert', label: 'Sahara Desert' },
    { value: 'tunis', label: 'Tunis' },
    { value: 'sidi-bou-said', label: 'Sidi Bou Said' },
    { value: 'tozeur', label: 'Tozeur' },
    { value: 'carthage', label: 'Carthage' },
  ];

  const activityTypeOptions = [
    { value: 'tour', label: 'Tours' },
    { value: 'event', label: 'Events' },
    { value: 'workshop', label: 'Workshops' },
    { value: 'adventure', label: 'Adventure' },
  ];

  return (
    <>
      <style dangerouslySetInnerHTML={{ __html: shineAnimationStyles }} />
      <form
        onSubmit={handleSearch}
        className="bg-white/95 backdrop-blur-md rounded-lg p-6 shadow-2xl"
      >
        <div className="grid md:grid-cols-4 gap-4">
          {/* Destination */}
          <div>
            <label className="block text-xs font-semibold text-primary mb-2 uppercase tracking-wider">
              {t('search_destination')}
            </label>
            <SelectWithIcon
              icon={<MapPin className="h-5 w-5 text-neutral-dark" />}
              value={destination}
              onChange={(e) => setDestination(e.target.value)}
              options={destinationsOptions}
              placeholder={t('search_destination_placeholder')}
            />
          </div>

          {/* Activity Type */}
          <div>
            <label className="block text-xs font-semibold text-primary mb-2 uppercase tracking-wider">
              {t('search_activity')}
            </label>
            <SelectWithIcon
              icon={<Compass className="h-5 w-5 text-neutral-dark" />}
              value={activityType}
              onChange={(e) => setActivityType(e.target.value)}
              options={activityTypeOptions}
              placeholder={t('search_activity_placeholder')}
            />
          </div>

          {/* Date */}
          <div>
            <label className="block text-xs font-semibold text-primary mb-2 uppercase tracking-wider">
              {t('search_date')}
            </label>
            <InputWithIcon
              icon={<Calendar className="h-5 w-5 text-neutral-dark" />}
              type="date"
              value={dateRange}
              onChange={(e) => setDateRange(e.target.value)}
              placeholder={t('search_date_placeholder')}
            />
          </div>

          {/* Search Button */}
          <div className="flex items-end">
            <Button
              type="submit"
              className="shine-button w-full h-[46px] px-6 text-base font-semibold bg-primary hover:bg-primary-700 text-white rounded-md whitespace-nowrap"
            >
              {t('search_button')}
            </Button>
          </div>
        </div>
      </form>
    </>
  );
}
