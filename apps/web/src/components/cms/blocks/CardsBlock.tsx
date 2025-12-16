'use client';

import Image from 'next/image';
import Link from 'next/link';
import { CardsBlockData } from '@/types/cms';

export function CardsBlock({ cards, columns = 3 }: CardsBlockData) {
  if (!cards || cards.length === 0) return null;

  const gridCols =
    {
      1: 'grid-cols-1',
      2: 'md:grid-cols-2',
      3: 'md:grid-cols-3',
      4: 'md:grid-cols-4',
    }[columns] || 'md:grid-cols-3';

  return (
    <div className={`cards-block grid gap-6 ${gridCols}`}>
      {cards.map((card, index) => (
        <div
          key={index}
          className="card bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden"
        >
          {card.image && (
            <div className="relative w-full h-48">
              <Image src={card.image} alt={card.title} fill className="object-cover" />
            </div>
          )}

          <div className="p-6">
            <h3 className="text-xl font-bold mb-2">{card.title}</h3>

            {card.description && <p className="text-gray-600 mb-4">{card.description}</p>}

            {card.link && (
              <Link href={card.link} className="text-primary font-semibold hover:underline">
                Learn more →
              </Link>
            )}
          </div>
        </div>
      ))}
    </div>
  );
}
