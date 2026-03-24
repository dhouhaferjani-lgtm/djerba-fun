'use client';

import type { PageHighlight, PageIcon } from '@djerba-fun/schemas';
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

interface HighlightsSectionProps {
  highlights: PageHighlight[];
  title?: string;
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

export function HighlightsSection({ highlights, title }: HighlightsSectionProps) {
  if (!highlights || highlights.length === 0) return null;

  return (
    <section className="py-12 bg-secondary-cream/30" data-testid="highlights-section">
      <div className="container mx-auto px-4">
        {title && <h2 className="text-2xl md:text-3xl font-bold text-center mb-8">{title}</h2>}

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {highlights.map((highlight, index) => {
            const IconComponent = highlight.icon ? iconMap[highlight.icon as PageIcon] : Sparkles;

            return (
              <div
                key={index}
                className="bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow"
              >
                <div className="flex items-start gap-4">
                  <div className="flex-shrink-0 w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                    <IconComponent className="w-6 h-6 text-primary-800" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-lg mb-2">{highlight.title}</h3>
                    <p className="text-gray-600 text-sm leading-relaxed">{highlight.description}</p>
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
