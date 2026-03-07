'use client';

import { Heart } from 'lucide-react';
import { useAuth } from '@/lib/contexts/AuthContext';
import { useWishlistIds, useToggleWishlist } from '@/lib/api/hooks';
import { cn } from '@/lib/utils/cn';
import { useTranslations } from 'next-intl';
import { useRouter } from '@/i18n/navigation';

interface WishlistButtonProps {
  listingId: string;
  className?: string;
  size?: 'sm' | 'md' | 'lg';
  variant?: 'default' | 'overlay';
}

export function WishlistButton({
  listingId,
  className,
  size = 'md',
  variant = 'default',
}: WishlistButtonProps) {
  const t = useTranslations('wishlist');
  const { isAuthenticated } = useAuth();
  const router = useRouter();

  const { data: wishlistIds = [], isLoading } = useWishlistIds(isAuthenticated);
  const toggleMutation = useToggleWishlist();

  const isInWishlist = wishlistIds.includes(listingId);

  const sizeClasses = {
    sm: 'h-8 w-8',
    md: 'h-10 w-10',
    lg: 'h-12 w-12',
  };

  const iconSizeClasses = {
    sm: 'h-4 w-4',
    md: 'h-5 w-5',
    lg: 'h-6 w-6',
  };

  const handleClick = async (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();

    if (!isAuthenticated) {
      router.push('/auth/login');
      return;
    }

    try {
      await toggleMutation.mutateAsync(listingId);
      // Visual feedback is handled by the optimistic update changing the heart fill
    } catch {
      // Error handled by optimistic update rollback
    }
  };

  return (
    <button
      type="button"
      onClick={handleClick}
      disabled={isLoading || toggleMutation.isPending}
      data-testid="wishlist-button"
      data-saved={isInWishlist}
      aria-label={isInWishlist ? t('remove_aria') : t('add_aria')}
      className={cn(
        'flex items-center justify-center rounded-full transition-all duration-200',
        'focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2',
        'disabled:cursor-not-allowed disabled:opacity-50',
        variant === 'overlay' && 'bg-white/80 backdrop-blur-sm hover:bg-white',
        variant === 'default' && 'bg-gray-100 hover:bg-gray-200',
        sizeClasses[size],
        className
      )}
    >
      <Heart
        className={cn(
          iconSizeClasses[size],
          'transition-colors duration-200',
          isInWishlist
            ? 'fill-red-500 stroke-red-500'
            : 'fill-transparent stroke-gray-600 hover:stroke-red-500'
        )}
      />
    </button>
  );
}
