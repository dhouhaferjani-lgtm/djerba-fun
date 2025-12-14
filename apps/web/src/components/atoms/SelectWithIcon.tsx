import React from 'react';
import { cn } from '@/lib/utils/cn';

interface SelectWithIconProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  icon: React.ReactNode;
  wrapperClassName?: string;
  selectClassName?: string;
  options: { value: string; label: string }[];
  placeholder?: string;
}

export function SelectWithIcon({
  icon,
  wrapperClassName,
  selectClassName,
  options,
  placeholder,
  ...props
}: SelectWithIconProps) {
  return (
    <div className={cn('relative', wrapperClassName)}>
      <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
        {icon}
      </div>
      <select
        className={cn(
          'block w-full rounded-lg border-neutral-light bg-neutral-white py-3 pl-10 pr-4 text-sm text-neutral-darker focus:border-primary focus:ring-primary appearance-none',
          selectClassName
        )}
        {...props}
      >
        {placeholder && <option value="">{placeholder}</option>}
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
      <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-neutral-darker">
        <svg
          className="h-4 w-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M19 9l-7 7-7-7"
          ></path>
        </svg>
      </div>
    </div>
  );
}
