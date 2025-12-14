import React from 'react';
import { cn } from '@/lib/utils/cn';

interface InputWithIconProps extends React.InputHTMLAttributes<HTMLInputElement> {
  icon: React.ReactNode;
  wrapperClassName?: string;
  inputClassName?: string;
}

export function InputWithIcon({
  icon,
  wrapperClassName,
  inputClassName,
  ...props
}: InputWithIconProps) {
  return (
    <div className={cn('relative', wrapperClassName)}>
      <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
        {icon}
      </div>
      <input
        className={cn(
          'block w-full rounded-lg border-neutral-light bg-neutral-white py-3 pl-10 pr-4 text-sm text-neutral-darker placeholder-neutral-dark focus:border-primary focus:ring-primary',
          inputClassName
        )}
        {...props}
      />
    </div>
  );
}
