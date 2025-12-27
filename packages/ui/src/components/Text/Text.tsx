import { forwardRef } from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '../../utils/cn';

const textVariants = cva('font-sans', {
  variants: {
    size: {
      xs: 'text-xs',
      sm: 'text-sm',
      base: 'text-base',
      lg: 'text-lg',
      xl: 'text-xl',
      '2xl': 'text-2xl',
    },
    weight: {
      light: 'font-light',
      normal: 'font-normal',
      medium: 'font-medium',
      semibold: 'font-semibold',
      bold: 'font-bold',
    },
    color: {
      default: 'text-neutral-900',
      body: 'text-neutral-700',
      muted: 'text-neutral-500',
      primary: 'text-primary',
      secondary: 'text-secondary',
      white: 'text-white',
      error: 'text-red-500',
      success: 'text-green-500',
      warning: 'text-yellow-500',
    },
    align: {
      left: 'text-left',
      center: 'text-center',
      right: 'text-right',
      justify: 'text-justify',
    },
  },
  defaultVariants: {
    size: 'base',
    weight: 'normal',
    color: 'default',
    align: 'left',
  },
});

export interface TextProps
  extends Omit<React.HTMLAttributes<HTMLElement>, 'color'>, VariantProps<typeof textVariants> {
  as?: 'p' | 'span' | 'div' | 'label';
}

export const Text = forwardRef<HTMLElement, TextProps>(
  ({ className, size, weight, color, align, as: Component = 'p', children, ...props }, ref) => {
    return (
      <Component
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        ref={ref as any}
        className={cn(textVariants({ size, weight, color, align }), className)}
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        {...(props as any)}
      >
        {children}
      </Component>
    );
  }
);

Text.displayName = 'Text';

export { textVariants };
