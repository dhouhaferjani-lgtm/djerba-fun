'use client';

import type { KeyTakeaway, TakeawayIcon } from '@/lib/api/blog';

interface KeyTakeawaysProps {
  takeaways: KeyTakeaway[];
}

const iconMap: Record<TakeawayIcon, string> = {
  check: '✓',
  star: '⭐',
  arrow: '→',
  bulb: '💡',
  heart: '❤️',
  map: '📍',
  clock: '⏰',
  money: '💰',
};

export function KeyTakeaways({ takeaways }: KeyTakeawaysProps) {
  if (!takeaways || takeaways.length === 0) {
    return null;
  }

  return (
    <div className="bg-gradient-to-br from-primary/5 to-primary/10 border border-primary/20 rounded-xl p-6 my-8">
      <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <span className="text-primary">📋</span>
        Key Takeaways
      </h2>
      <ul className="space-y-3">
        {takeaways.map((takeaway, index) => (
          <li key={index} className="flex items-start gap-3 text-gray-700">
            <span
              className="flex-shrink-0 w-6 h-6 flex items-center justify-center text-lg"
              aria-hidden="true"
            >
              {iconMap[takeaway.icon] || iconMap.check}
            </span>
            <span className="leading-relaxed">{takeaway.text}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}
