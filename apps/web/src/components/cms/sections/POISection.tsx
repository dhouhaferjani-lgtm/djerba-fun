'use client';

import type { PagePOI } from '@djerba-fun/schemas';
import { MapPin } from 'lucide-react';

interface POISectionProps {
  pois: PagePOI[];
  title?: string;
}

export function POISection({ pois, title }: POISectionProps) {
  if (!pois || pois.length === 0) return null;

  return (
    <section className="py-12 bg-gray-50" data-testid="poi-section">
      <div className="container mx-auto px-4">
        {title && <h2 className="text-2xl md:text-3xl font-bold text-center mb-8">{title}</h2>}

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
          {pois.map((poi, index) => (
            <div key={index} className="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
              <div className="flex items-start gap-4">
                <div className="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                  <MapPin className="w-5 h-5 text-red-600" />
                </div>
                <div>
                  <h3 className="font-semibold text-lg mb-2">{poi.name}</h3>
                  <p className="text-gray-600 text-sm leading-relaxed">{poi.description}</p>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
