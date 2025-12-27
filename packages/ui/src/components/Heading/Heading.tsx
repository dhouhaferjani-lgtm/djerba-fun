import { forwardRef } from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '../../utils/cn';

const headingVariants = cva('font-display font-bold', {
  variants: {
    level: {
      // Reduced scale: H1 now maxes at 5xl (48px), not 6xl (60px)
      // Industry standard: GetYourGuide uses ~40-48px for titles
      h1: 'text-3xl md:text-4xl lg:text-5xl leading-tight tracking-tight', // 30→36→48px
      h2: 'text-2xl md:text-3xl lg:text-4xl leading-snug tracking-tight', // 24→30→36px
      h3: 'text-xl md:text-2xl lg:text-3xl leading-snug tracking-normal', // 20→24→30px
      h4: 'text-lg md:text-xl lg:text-2xl leading-snug tracking-normal', // 18→20→24px
      h5: 'text-base md:text-lg lg:text-xl leading-normal tracking-normal', // 16→18→20px
      h6: 'text-sm md:text-base lg:text-lg leading-normal tracking-wide', // 14→16→18px
    },
    color: {
      default: 'text-neutral-700', // Softer than pure black (#44403c)
      primary: 'text-primary-700',
      secondary: 'text-secondary-700',
      white: 'text-white',
      muted: 'text-neutral-500',
    },
    weight: {
      normal: 'font-normal',
      medium: 'font-medium',
      semibold: 'font-semibold',
      bold: 'font-bold',
      extrabold: 'font-extrabold',
    },
  },
  defaultVariants: {
    level: 'h2',
    color: 'default',
    weight: 'bold',
  },
});

export interface HeadingProps
  extends
    Omit<React.HTMLAttributes<HTMLHeadingElement>, 'color'>,
    VariantProps<typeof headingVariants> {
  level?: 'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6';
}

export const Heading = forwardRef<HTMLHeadingElement, HeadingProps>(
  ({ className, level = 'h2', color, weight, children, ...props }, ref) => {
    const Component = level;

    return (
      <Component
        ref={ref}
        className={cn(headingVariants({ level, color, weight }), className)}
        {...props}
      >
        {children}
      </Component>
    );
  }
);

Heading.displayName = 'Heading';

export { headingVariants };
