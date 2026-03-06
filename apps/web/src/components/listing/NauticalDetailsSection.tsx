'use client';

import { useTranslations } from 'next-intl';
import {
  Ship,
  Ruler,
  Users,
  Calendar,
  Clock,
  Shield,
  User,
  Fuel,
  LifeBuoy,
  Heart,
  Flame,
  Signal,
  Radio,
  Navigation,
  Compass,
  Eye,
  Fish,
  Anchor,
  Waves,
  Sun,
  Speaker,
  Droplet,
  Home,
  CheckCircle,
  XCircle,
} from 'lucide-react';

interface NauticalData {
  boatName?: string | null;
  boatLength?: number | null;
  boatCapacity?: number | null;
  boatYear?: number | null;
  licenseRequired?: boolean;
  licenseType?: string | null;
  equipmentIncluded?: string[];
  crewIncluded?: boolean;
  fuelIncluded?: boolean;
  minRentalHours?: number | null;
}

interface NauticalDetailsSectionProps {
  nautical: NauticalData;
}

// Map equipment keys to icons
const equipmentIcons: Record<string, React.ComponentType<{ className?: string }>> = {
  life_jackets: LifeBuoy,
  first_aid_kit: Heart,
  fire_extinguisher: Flame,
  flares: Signal,
  gps: Navigation,
  radio: Radio,
  depth_finder: Anchor,
  fish_finder: Fish,
  compass: Compass,
  snorkeling_gear: Eye,
  fishing_equipment: Fish,
  water_skis: Waves,
  wakeboard: Waves,
  diving_equipment: Anchor,
  paddle_board: Waves,
  kayak: Ship,
  cooler: Droplet,
  sun_shade: Sun,
  bluetooth_speakers: Speaker,
  shower: Droplet,
  toilet: Home,
  cabin: Home,
};

// Map equipment keys to labels
const equipmentLabels: Record<string, string> = {
  life_jackets: 'Life Jackets',
  first_aid_kit: 'First Aid Kit',
  fire_extinguisher: 'Fire Extinguisher',
  flares: 'Safety Flares',
  gps: 'GPS Navigation',
  radio: 'VHF Radio',
  depth_finder: 'Depth Finder',
  fish_finder: 'Fish Finder',
  compass: 'Compass',
  snorkeling_gear: 'Snorkeling Gear',
  fishing_equipment: 'Fishing Equipment',
  water_skis: 'Water Skis',
  wakeboard: 'Wakeboard',
  diving_equipment: 'Diving Equipment',
  paddle_board: 'Paddle Board',
  kayak: 'Kayak',
  cooler: 'Cooler / Ice Box',
  sun_shade: 'Sun Shade / Bimini',
  bluetooth_speakers: 'Bluetooth Speakers',
  shower: 'Shower',
  toilet: 'Toilet / Head',
  cabin: 'Cabin',
};

export function NauticalDetailsSection({ nautical }: NauticalDetailsSectionProps) {
  const t = useTranslations('listing');

  const {
    boatName,
    boatLength,
    boatCapacity,
    boatYear,
    licenseRequired,
    licenseType,
    equipmentIncluded,
    crewIncluded,
    fuelIncluded,
    minRentalHours,
  } = nautical;

  const hasBoatSpecs = boatName || boatLength || boatCapacity || boatYear;
  const hasEquipment = equipmentIncluded && equipmentIncluded.length > 0;

  if (!hasBoatSpecs && !hasEquipment && !licenseRequired && !crewIncluded && !fuelIncluded) {
    return null;
  }

  return (
    <section className="space-y-6">
      <h2 className="text-2xl font-semibold text-gray-900">{t('boat_details')}</h2>

      {/* Boat Name */}
      {boatName && (
        <div className="flex items-center gap-3">
          <Ship className="w-6 h-6 text-primary" />
          <span className="text-xl font-medium text-gray-900">{boatName}</span>
        </div>
      )}

      {/* Boat Specifications */}
      {hasBoatSpecs && (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {boatLength !== null && boatLength !== undefined && (
            <div className="flex items-center gap-3 p-4 bg-blue-50 rounded-lg">
              <Ruler className="w-6 h-6 text-blue-600" />
              <div>
                <div className="text-lg font-semibold">{boatLength}m</div>
                <div className="text-sm text-gray-600">{t('length')}</div>
              </div>
            </div>
          )}
          {boatCapacity !== null && boatCapacity !== undefined && (
            <div className="flex items-center gap-3 p-4 bg-blue-50 rounded-lg">
              <Users className="w-6 h-6 text-blue-600" />
              <div>
                <div className="text-lg font-semibold">{boatCapacity}</div>
                <div className="text-sm text-gray-600">{t('passengers')}</div>
              </div>
            </div>
          )}
          {boatYear !== null && boatYear !== undefined && (
            <div className="flex items-center gap-3 p-4 bg-blue-50 rounded-lg">
              <Calendar className="w-6 h-6 text-blue-600" />
              <div>
                <div className="text-lg font-semibold">{boatYear}</div>
                <div className="text-sm text-gray-600">{t('year_built')}</div>
              </div>
            </div>
          )}
          {minRentalHours !== null && minRentalHours !== undefined && (
            <div className="flex items-center gap-3 p-4 bg-blue-50 rounded-lg">
              <Clock className="w-6 h-6 text-blue-600" />
              <div>
                <div className="text-lg font-semibold">{minRentalHours}h</div>
                <div className="text-sm text-gray-600">{t('min_rental')}</div>
              </div>
            </div>
          )}
        </div>
      )}

      {/* License & Inclusions */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {/* License Required */}
        <div className="flex items-center gap-3 p-4 border border-gray-200 rounded-lg">
          <Shield className="w-5 h-5 text-gray-600" />
          <div className="flex-1">
            <div className="text-sm text-gray-600">{t('license_required')}</div>
            <div className="font-semibold flex items-center gap-2">
              {licenseRequired ? (
                <>
                  <CheckCircle className="w-4 h-4 text-amber-600" />
                  <span>{licenseType || t('yes')}</span>
                </>
              ) : (
                <>
                  <XCircle className="w-4 h-4 text-green-600" />
                  <span>{t('no_license_needed')}</span>
                </>
              )}
            </div>
          </div>
        </div>

        {/* Crew Included */}
        <div className="flex items-center gap-3 p-4 border border-gray-200 rounded-lg">
          <User className="w-5 h-5 text-gray-600" />
          <div className="flex-1">
            <div className="text-sm text-gray-600">{t('captain_crew')}</div>
            <div className="font-semibold flex items-center gap-2">
              {crewIncluded ? (
                <>
                  <CheckCircle className="w-4 h-4 text-green-600" />
                  <span>{t('included')}</span>
                </>
              ) : (
                <>
                  <XCircle className="w-4 h-4 text-gray-400" />
                  <span>{t('not_included')}</span>
                </>
              )}
            </div>
          </div>
        </div>

        {/* Fuel Included */}
        <div className="flex items-center gap-3 p-4 border border-gray-200 rounded-lg">
          <Fuel className="w-5 h-5 text-gray-600" />
          <div className="flex-1">
            <div className="text-sm text-gray-600">{t('fuel')}</div>
            <div className="font-semibold flex items-center gap-2">
              {fuelIncluded ? (
                <>
                  <CheckCircle className="w-4 h-4 text-green-600" />
                  <span>{t('included')}</span>
                </>
              ) : (
                <>
                  <XCircle className="w-4 h-4 text-gray-400" />
                  <span>{t('not_included')}</span>
                </>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Equipment Grid */}
      {hasEquipment && (
        <div>
          <h3 className="text-lg font-medium text-gray-900 mb-3">{t('equipment_included')}</h3>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            {equipmentIncluded.map((equipment) => {
              const Icon = equipmentIcons[equipment] || Anchor;
              const label = equipmentLabels[equipment] || equipment;
              return (
                <div key={equipment} className="flex items-center gap-2 p-2">
                  <Icon className="w-5 h-5 text-blue-600 flex-shrink-0" />
                  <span className="text-sm text-gray-700">{label}</span>
                </div>
              );
            })}
          </div>
        </div>
      )}
    </section>
  );
}
