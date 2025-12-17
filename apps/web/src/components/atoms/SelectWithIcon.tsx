import React, { forwardRef } from 'react';
import { Select, type SelectProps } from '@go-adventure/ui';
import { cn } from '@/lib/utils/cn';
import { ChevronDown } from 'lucide-react';

export interface SelectWithIconProps extends Omit<SelectProps, 'className'> {
  icon: React.ReactNode;
  wrapperClassName?: string;
  selectClassName?: string;
  placeholder?: string;
}

/**
 * Select component with left icon and chevron support.
 * Composes the UI package Select component with icon positioning.
 * Inherits all UI Select features: variants, sizes, error handling.
 */
export const SelectWithIcon = forwardRef<HTMLSelectElement, SelectWithIconProps>(
  ({ icon, wrapperClassName, selectClassName, options, placeholder, children, ...props }, ref) => {
    // Prepend placeholder option if provided
    const optionsWithPlaceholder = placeholder
      ? [{ value: '', label: placeholder }, ...(options || [])]
      : options;

    return (
      <div className={cn('relative', wrapperClassName)}>
        <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-neutral-500 z-10">
          {icon}
        </div>
        <Select
          ref={ref}
          className={cn('appearance-none pl-10 pr-10', selectClassName)}
          options={optionsWithPlaceholder}
          {...props}
        >
          {children}
        </Select>
        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-neutral-500 z-10">
          <ChevronDown className="h-4 w-4" />
        </div>
      </div>
    );
  }
);

SelectWithIcon.displayName = 'SelectWithIcon';
