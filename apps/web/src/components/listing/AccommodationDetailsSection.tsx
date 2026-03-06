'use client';

import { useTranslations } from 'next-intl';
import {
  Wifi,
  Wind,
  Flame,
  UtensilsCrossed,
  WashingMachine,
  Waves,
  Car,
  TreePine,
  Home,
  Tv,
  Laptop,
  Lock,
  Shirt,
  Sun,
  Mountain,
  Bed,
  Bath,
  Users,
  Ruler,
  Clock,
} from 'lucide-react';

interface AccommodationData {
  accommodationType?: string | null;
  bedrooms?: number | null;
  bathrooms?: number | null;
  maxGuests?: number | null;
  propertySize?: number | null;
  checkInTime?: string | null;
  checkOutTime?: string | null;
  houseRules?: string | null;
  amenities?: string[];
  mealsIncluded?: {
    breakfast?: boolean;
    lunch?: boolean;
    dinner?: boolean;
  } | null;
}

interface AccommodationDetailsSectionProps {
  accommodation: AccommodationData;
}

// Map amenity keys to icons
const amenityIcons: Record<string, React.ComponentType<{ className?: string }>> = {
  wifi: Wifi,
  air_conditioning: Wind,
  heating: Flame,
  kitchen: UtensilsCrossed,
  washer: WashingMachine,
  dryer: WashingMachine,
  pool: Waves,
  hot_tub: Waves,
  parking: Car,
  garden: TreePine,
  terrace: Home,
  bbq: Flame,
  tv: Tv,
  workspace: Laptop,
  safe: Lock,
  iron: Shirt,
  hair_dryer: Wind,
  beach_access: Sun,
  sea_view: Waves,
  mountain_view: Mountain,
};

// Map amenity keys to labels
const amenityLabels: Record<string, string> = {
  wifi: 'WiFi',
  air_conditioning: 'Air Conditioning',
  heating: 'Heating',
  kitchen: 'Kitchen',
  washer: 'Washer',
  dryer: 'Dryer',
  pool: 'Pool',
  hot_tub: 'Hot Tub',
  parking: 'Parking',
  garden: 'Garden',
  terrace: 'Terrace',
  bbq: 'BBQ / Grill',
  tv: 'TV',
  workspace: 'Workspace',
  safe: 'Safe',
  iron: 'Iron',
  hair_dryer: 'Hair Dryer',
  beach_access: 'Beach Access',
  sea_view: 'Sea View',
  mountain_view: 'Mountain View',
};

export function AccommodationDetailsSection({ accommodation }: AccommodationDetailsSectionProps) {
  const t = useTranslations('listing');

  const {
    accommodationType,
    bedrooms,
    bathrooms,
    maxGuests,
    propertySize,
    checkInTime,
    checkOutTime,
    houseRules,
    amenities,
    mealsIncluded,
  } = accommodation;

  const hasPropertyDetails = bedrooms || bathrooms || maxGuests || propertySize;
  const hasCheckTimes = checkInTime || checkOutTime;
  const hasAmenities = amenities && amenities.length > 0;
  const hasMeals =
    mealsIncluded && (mealsIncluded.breakfast || mealsIncluded.lunch || mealsIncluded.dinner);

  if (!hasPropertyDetails && !hasCheckTimes && !hasAmenities && !hasMeals && !houseRules) {
    return null;
  }

  return (
    <section className="space-y-6">
      <h2 className="text-2xl font-semibold text-gray-900">{t('property_details')}</h2>

      {/* Property Quick Stats */}
      {hasPropertyDetails && (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {bedrooms !== null && bedrooms !== undefined && (
            <div className="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
              <Bed className="w-6 h-6 text-primary" />
              <div>
                <div className="text-lg font-semibold">{bedrooms}</div>
                <div className="text-sm text-gray-600">
                  {bedrooms === 1 ? t('bedroom') : t('bedrooms')}
                </div>
              </div>
            </div>
          )}
          {bathrooms !== null && bathrooms !== undefined && (
            <div className="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
              <Bath className="w-6 h-6 text-primary" />
              <div>
                <div className="text-lg font-semibold">{bathrooms}</div>
                <div className="text-sm text-gray-600">
                  {bathrooms === 1 ? t('bathroom') : t('bathrooms')}
                </div>
              </div>
            </div>
          )}
          {maxGuests !== null && maxGuests !== undefined && (
            <div className="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
              <Users className="w-6 h-6 text-primary" />
              <div>
                <div className="text-lg font-semibold">{maxGuests}</div>
                <div className="text-sm text-gray-600">{t('max_guests')}</div>
              </div>
            </div>
          )}
          {propertySize !== null && propertySize !== undefined && (
            <div className="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
              <Ruler className="w-6 h-6 text-primary" />
              <div>
                <div className="text-lg font-semibold">{propertySize} m²</div>
                <div className="text-sm text-gray-600">{t('property_size')}</div>
              </div>
            </div>
          )}
        </div>
      )}

      {/* Check-in / Check-out Times */}
      {hasCheckTimes && (
        <div className="grid grid-cols-2 gap-4">
          {checkInTime && (
            <div className="flex items-center gap-3 p-4 border border-gray-200 rounded-lg">
              <Clock className="w-5 h-5 text-green-600" />
              <div>
                <div className="text-sm text-gray-600">{t('check_in')}</div>
                <div className="font-semibold">{checkInTime}</div>
              </div>
            </div>
          )}
          {checkOutTime && (
            <div className="flex items-center gap-3 p-4 border border-gray-200 rounded-lg">
              <Clock className="w-5 h-5 text-red-600" />
              <div>
                <div className="text-sm text-gray-600">{t('check_out')}</div>
                <div className="font-semibold">{checkOutTime}</div>
              </div>
            </div>
          )}
        </div>
      )}

      {/* Meals Included */}
      {hasMeals && (
        <div>
          <h3 className="text-lg font-medium text-gray-900 mb-3">{t('meals_included')}</h3>
          <div className="flex flex-wrap gap-2">
            {mealsIncluded.breakfast && (
              <span className="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                {t('breakfast')}
              </span>
            )}
            {mealsIncluded.lunch && (
              <span className="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                {t('lunch')}
              </span>
            )}
            {mealsIncluded.dinner && (
              <span className="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                {t('dinner')}
              </span>
            )}
          </div>
        </div>
      )}

      {/* Amenities Grid */}
      {hasAmenities && (
        <div>
          <h3 className="text-lg font-medium text-gray-900 mb-3">{t('amenities')}</h3>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            {amenities.map((amenity) => {
              const Icon = amenityIcons[amenity] || Home;
              const label = amenityLabels[amenity] || amenity;
              return (
                <div key={amenity} className="flex items-center gap-2 p-2">
                  <Icon className="w-5 h-5 text-primary flex-shrink-0" />
                  <span className="text-sm text-gray-700">{label}</span>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* House Rules */}
      {houseRules && (
        <div>
          <h3 className="text-lg font-medium text-gray-900 mb-3">{t('house_rules')}</h3>
          <div className="p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <p className="text-gray-700 whitespace-pre-line">{houseRules}</p>
          </div>
        </div>
      )}
    </section>
  );
}
