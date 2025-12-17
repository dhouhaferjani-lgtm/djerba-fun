import React, { forwardRef } from 'react';
import { Input, type InputProps } from '@go-adventure/ui';
import { cn } from '@/lib/utils/cn';

export interface InputWithIconProps extends Omit<InputProps, 'className'> {
  icon: React.ReactNode;
  wrapperClassName?: string;
  inputClassName?: string;
}

/**
 * Input component with left icon support.
 * Composes the UI package Input component with icon positioning.
 * Inherits all UI Input features: variants, sizes, error handling.
 */
export const InputWithIcon = forwardRef<HTMLInputElement, InputWithIconProps>(
  ({ icon, wrapperClassName, inputClassName, ...props }, ref) => {
    return (
      <div className={cn('relative', wrapperClassName)}>
        <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-neutral-500 z-10">
          {icon}
        </div>
        <Input ref={ref} className={cn('pl-10', inputClassName)} {...props} />
      </div>
    );
  }
);

InputWithIcon.displayName = 'InputWithIcon';
