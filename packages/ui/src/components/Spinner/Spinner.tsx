import { forwardRef } from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '../../utils/cn';

const spinnerVariants = cva(
  'inline-block animate-spin rounded-full border-solid border-t-transparent',
  {
    variants: {
      size: {
        sm: 'h-4 w-4 border-2',
        md: 'h-8 w-8 border-3',
        lg: 'h-12 w-12 border-4',
      },
      color: {
        primary: 'border-primary',
        secondary: 'border-secondary',
        white: 'border-white',
        neutral: 'border-neutral-500',
      },
    },
    defaultVariants: {
      size: 'md',
      color: 'primary',
    },
  }
);

export interface SpinnerProps
  extends
    Omit<React.HTMLAttributes<HTMLDivElement>, 'color'>,
    VariantProps<typeof spinnerVariants> {
  label?: string;
}

export const Spinner = forwardRef<HTMLDivElement, SpinnerProps>(
  ({ className, size, color, label = 'Loading...', ...props }, ref) => {
    return (
      <div ref={ref} className={cn('inline-flex items-center gap-2', className)} {...props}>
        <div className={cn(spinnerVariants({ size, color }))} role="status" aria-label={label}>
          <span className="sr-only">{label}</span>
        </div>
      </div>
    );
  }
);

Spinner.displayName = 'Spinner';

export { spinnerVariants };
