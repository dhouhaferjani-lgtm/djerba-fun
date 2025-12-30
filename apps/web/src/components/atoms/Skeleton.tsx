/**
 * Performance Optimization: Reusable skeleton component
 *
 * Provides consistent loading skeletons throughout the application.
 * Used in loading.tsx files and dynamic components for better UX.
 *
 * Benefits:
 * - Consistent loading experience
 * - Prevents layout shift
 * - Reusable across all loading states
 */

import { cn } from '@/lib/utils/cn';

interface SkeletonProps {
  className?: string;
  variant?: 'text' | 'circular' | 'rectangular' | 'rounded';
  animation?: 'pulse' | 'wave' | 'none';
}

export function Skeleton({
  className,
  variant = 'rectangular',
  animation = 'pulse',
}: SkeletonProps) {
  const variantClasses = {
    text: 'h-4 rounded',
    circular: 'rounded-full',
    rectangular: 'rounded-none',
    rounded: 'rounded-lg',
  };

  const animationClasses = {
    pulse: 'animate-pulse',
    wave: 'animate-shimmer',
    none: '',
  };

  return (
    <div
      className={cn(
        'bg-neutral-200',
        variantClasses[variant],
        animationClasses[animation],
        className
      )}
      aria-busy="true"
      aria-live="polite"
    />
  );
}

// Compound components for common patterns
export function SkeletonText({ lines = 3, className }: { lines?: number; className?: string }) {
  return (
    <div className={cn('space-y-2', className)}>
      {Array.from({ length: lines }).map((_, i) => (
        <Skeleton key={i} variant="text" className={cn('w-full', i === lines - 1 && 'w-3/4')} />
      ))}
    </div>
  );
}

export function SkeletonCard({ className }: { className?: string }) {
  return (
    <div className={cn('bg-white rounded-lg border border-neutral-200 overflow-hidden', className)}>
      <Skeleton variant="rectangular" className="h-48 w-full" />
      <div className="p-4 space-y-3">
        <Skeleton variant="text" className="w-3/4" />
        <Skeleton variant="text" className="w-1/2" />
        <Skeleton variant="text" className="w-2/3" />
        <Skeleton variant="rounded" className="h-8 w-24 mt-4" />
      </div>
    </div>
  );
}

export function SkeletonAvatar({ size = 'md' }: { size?: 'sm' | 'md' | 'lg' }) {
  const sizeClasses = {
    sm: 'h-8 w-8',
    md: 'h-12 w-12',
    lg: 'h-16 w-16',
  };

  return <Skeleton variant="circular" className={sizeClasses[size]} />;
}

export function SkeletonButton({ className }: { className?: string }) {
  return <Skeleton variant="rounded" className={cn('h-10 w-32', className)} />;
}
