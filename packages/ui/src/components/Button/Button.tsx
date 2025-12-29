import { forwardRef } from 'react';
import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '../../utils/cn';

const buttonVariants = cva(
  'inline-flex items-center justify-center rounded-lg font-medium transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none disabled:cursor-not-allowed',
  {
    variants: {
      variant: {
        // Primary: Forest green with proper color scale and shadow elevation
        primary:
          'bg-primary-600 text-white hover:bg-primary-700 active:bg-primary-800 active:scale-[0.98] shadow-sm hover:shadow-md focus-visible:ring-primary-500',
        // Secondary: Lime green with proper color scale
        secondary:
          'bg-secondary-500 text-white hover:bg-secondary-600 active:bg-secondary-700 active:scale-[0.98] shadow-sm hover:shadow-md focus-visible:ring-secondary-500',
        // Accent: Cream with proper contrast text color
        accent:
          'bg-accent-300 text-primary-800 hover:bg-accent-400 active:bg-accent-500 active:scale-[0.98] shadow-sm hover:shadow-md focus-visible:ring-accent-400',
        // Outline: Transparent with brand border
        outline:
          'border-2 border-primary-600 text-primary-700 bg-transparent hover:bg-primary-50 active:bg-primary-100 focus-visible:ring-primary-500',
        // Ghost: Minimal style with subtle hover
        ghost:
          'text-primary-700 bg-transparent hover:bg-primary-50 active:bg-primary-100 focus-visible:ring-primary-500',
        // Destructive: Brand-aligned error color (earthy terracotta, NOT generic red)
        destructive:
          'bg-error text-white hover:bg-error-dark active:bg-error-dark active:scale-[0.98] shadow-sm hover:shadow-md focus-visible:ring-error',
      },
      size: {
        sm: 'h-8 px-3 text-sm gap-1.5 min-h-[44px] sm:min-h-0', // Touch-friendly on mobile, more compact on desktop
        md: 'h-10 px-4 text-sm gap-2', // More compact, consistent sizing
        lg: 'h-11 px-6 text-base font-semibold gap-2', // Slightly smaller
        icon: 'h-9 w-9 min-h-[44px] min-w-[44px]', // WCAG touch target, more compact on desktop
      },
    },
    defaultVariants: {
      variant: 'primary',
      size: 'md',
    },
  }
);

export interface ButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>, VariantProps<typeof buttonVariants> {
  /** If true, the button will be rendered as a child element (e.g., Link) */
  asChild?: boolean;
  isLoading?: boolean;
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant, size, asChild = false, isLoading, children, disabled, ...props }, ref) => {
    const Comp = asChild ? Slot : 'button';

    return (
      <Comp
        ref={ref}
        className={cn(buttonVariants({ variant, size }), className)}
        disabled={isLoading || disabled}
        {...props}
      >
        {isLoading ? (
          <>
            <svg
              className="mr-2 h-4 w-4 animate-spin"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                className="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                strokeWidth="4"
              />
              <path
                className="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              />
            </svg>
            {children}
          </>
        ) : (
          children
        )}
      </Comp>
    );
  }
);

Button.displayName = 'Button';

export { buttonVariants };
