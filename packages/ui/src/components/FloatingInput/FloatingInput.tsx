'use client';

import { forwardRef, useState } from 'react';
import { cn } from '../../utils/cn';

export interface FloatingInputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label: string;
  error?: string;
  helperText?: string;
  icon?: React.ReactNode;
}

export const FloatingInput = forwardRef<HTMLInputElement, FloatingInputProps>(
  ({ label, error, helperText, icon, className, id, value, ...props }, ref) => {
    const [isFocused, setIsFocused] = useState(false);
    const hasValue = value !== undefined && value !== '';
    const isFloating = isFocused || hasValue;

    // Generate a unique ID if not provided
    const inputId = id || `floating-input-${label.toLowerCase().replace(/\s+/g, '-')}`;
    const errorId = error ? `${inputId}-error` : undefined;
    const helperId = helperText ? `${inputId}-helper` : undefined;

    return (
      <div className="w-full">
        <div className="relative">
          {icon && (
            <div className="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none">
              {icon}
            </div>
          )}

          <input
            ref={ref}
            id={inputId}
            value={value}
            className={cn(
              'peer w-full rounded-lg border bg-white px-3 pt-6 pb-2 text-base transition-all',
              'focus:outline-none focus:ring-2',
              icon && 'pl-10',
              error
                ? 'border-error focus:border-error focus:ring-error/20'
                : 'border-neutral-300 focus:border-primary-500 focus:ring-primary-500/20',
              'disabled:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-60',
              className
            )}
            onFocus={() => setIsFocused(true)}
            onBlur={() => setIsFocused(false)}
            placeholder=" "
            aria-invalid={!!error}
            aria-describedby={cn(errorId, helperId).trim() || undefined}
            {...props}
          />

          <label
            htmlFor={inputId}
            className={cn(
              'absolute left-3 transition-all duration-200 pointer-events-none',
              icon && 'left-10',
              isFloating
                ? 'top-1.5 text-xs text-neutral-600'
                : 'top-1/2 -translate-y-1/2 text-base text-neutral-500',
              error && isFloating && 'text-error',
              'peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-primary-600',
              error && 'peer-focus:text-error'
            )}
          >
            {label}
          </label>
        </div>

        {error && (
          <p id={errorId} className="mt-1.5 text-sm text-error flex items-start gap-1">
            <svg className="h-4 w-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path
                fillRule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                clipRule="evenodd"
              />
            </svg>
            {error}
          </p>
        )}

        {helperText && !error && (
          <p id={helperId} className="mt-1.5 text-sm text-neutral-500">
            {helperText}
          </p>
        )}
      </div>
    );
  }
);

FloatingInput.displayName = 'FloatingInput';
