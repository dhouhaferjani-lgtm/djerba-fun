import { forwardRef } from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '../../utils/cn';

const headingVariants = cva('font-display font-bold text-neutral-900', {
  variants: {
    level: {
      h1: 'text-4xl md:text-5xl lg:text-6xl',
      h2: 'text-3xl md:text-4xl lg:text-5xl',
      h3: 'text-2xl md:text-3xl lg:text-4xl',
      h4: 'text-xl md:text-2xl lg:text-3xl',
      h5: 'text-lg md:text-xl lg:text-2xl',
      h6: 'text-base md:text-lg lg:text-xl',
    },
    color: {
      default: 'text-neutral-900',
      primary: 'text-primary',
      secondary: 'text-secondary',
      white: 'text-white',
      muted: 'text-neutral-600',
    },
  },
  defaultVariants: {
    level: 'h2',
    color: 'default',
  },
});

export interface HeadingProps
  extends
    Omit<React.HTMLAttributes<HTMLHeadingElement>, 'color'>,
    VariantProps<typeof headingVariants> {
  level?: 'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6';
}

export const Heading = forwardRef<HTMLHeadingElement, HeadingProps>(
  ({ className, level = 'h2', color, children, ...props }, ref) => {
    const Component = level;

    return (
      <Component ref={ref} className={cn(headingVariants({ level, color }), className)} {...props}>
        {children}
      </Component>
    );
  }
);

Heading.displayName = 'Heading';

export { headingVariants };
