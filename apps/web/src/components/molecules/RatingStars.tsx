import { Star } from 'lucide-react';

interface RatingStarsProps {
  rating: number;
  maxRating?: number;
  size?: 'sm' | 'md' | 'lg';
  showNumber?: boolean;
  className?: string;
}

const sizeMap = {
  sm: 'h-3 w-3',
  md: 'h-4 w-4',
  lg: 'h-5 w-5',
};

export function RatingStars({
  rating,
  maxRating = 5,
  size = 'md',
  showNumber = false,
  className = '',
}: RatingStarsProps) {
  const stars = Array.from({ length: maxRating }, (_, i) => {
    const filled = i < Math.floor(rating);
    const partial = i === Math.floor(rating) && rating % 1 !== 0;
    return { filled, partial, key: i };
  });

  return (
    <div className={`flex items-center gap-1 ${className}`}>
      <div className="flex items-center">
        {stars.map(({ filled, partial, key }) => (
          <Star
            key={key}
            className={`${sizeMap[size]} ${
              filled
                ? 'fill-[#f59e0b] text-[#f59e0b]'
                : partial
                  ? 'fill-[#f59e0b] text-[#f59e0b] opacity-50'
                  : 'fill-none text-neutral-300'
            }`}
          />
        ))}
      </div>
      {showNumber && (
        <span className="text-sm font-medium text-neutral-700">{rating.toFixed(1)}</span>
      )}
    </div>
  );
}
