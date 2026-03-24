'use client';

import type { PageKeyFact, PageIcon } from '@djerba-fun/schemas';
import {
  Waves,
  Landmark,
  Mountain,
  Compass,
  Users,
  Eye,
  Moon,
  Sparkles,
  Map,
  Tent,
  Palette,
  ShoppingBag,
  Bird,
  Home,
  Film,
  Droplets,
  Footprints,
  Layers,
  MapPin,
  Calendar,
  Ruler,
  Star,
  UtensilsCrossed,
  Info,
  TreePalm,
  type LucideIcon,
} from 'lucide-react';

interface KeyFactsBarProps {
  facts: PageKeyFact[];
}

const iconMap: Record<PageIcon, LucideIcon> = {
  waves: Waves,
  landmark: Landmark,
  mountain: Mountain,
  compass: Compass,
  users: Users,
  eye: Eye,
  moon: Moon,
  'tree-palm': TreePalm,
  sparkles: Sparkles,
  map: Map,
  tent: Tent,
  palette: Palette,
  'shopping-bag': ShoppingBag,
  bird: Bird,
  home: Home,
  film: Film,
  droplets: Droplets,
  footprints: Footprints,
  layers: Layers,
  'map-pin': MapPin,
  calendar: Calendar,
  ruler: Ruler,
  star: Star,
  'utensils-crossed': UtensilsCrossed,
  info: Info,
};

export function KeyFactsBar({ facts }: KeyFactsBarProps) {
  if (!facts || facts.length === 0) return null;

  return (
    <section className="py-6 bg-primary-800 text-white" data-testid="key-facts-bar">
      <div className="container mx-auto px-4">
        <div className="flex flex-wrap justify-center items-center gap-8 md:gap-12">
          {facts.map((fact, index) => {
            const IconComponent = fact.icon ? iconMap[fact.icon as PageIcon] : Info;

            return (
              <div key={index} className="flex items-center gap-3 text-center">
                <IconComponent className="w-6 h-6 text-primary-300 flex-shrink-0" />
                <div>
                  <div className="text-xl md:text-2xl font-bold">{fact.value}</div>
                  <div className="text-xs md:text-sm text-primary-200 uppercase tracking-wide">
                    {fact.label}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
