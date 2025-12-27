'use client';

import { ReactNode } from 'react';

interface ConsentCheckboxProps {
  id: string;
  checked: boolean;
  onChange: (checked: boolean) => void;
  required?: boolean;
  disabled?: boolean;
  label: ReactNode;
  description?: string;
  error?: string;
}

export default function ConsentCheckbox({
  id,
  checked,
  onChange,
  required = false,
  disabled = false,
  label,
  description,
  error,
}: ConsentCheckboxProps) {
  return (
    <div className="flex items-start">
      <div className="flex items-center h-5">
        <input
          type="checkbox"
          id={id}
          checked={checked}
          onChange={(e) => onChange(e.target.checked)}
          required={required}
          disabled={disabled}
          className={`
            h-4 w-4 rounded border-gray-300 text-primary
            focus:ring-primary focus:ring-offset-0 focus:ring-2
            ${disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}
            ${error ? 'border-error' : ''}
          `}
        />
      </div>
      <div className="ml-3">
        <label
          htmlFor={id}
          className={`
            text-sm text-gray-700
            ${disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}
          `}
        >
          {label}
          {required && <span className="text-error ml-1">*</span>}
        </label>
        {description && <p className="text-xs text-gray-500 mt-0.5">{description}</p>}
        {error && <p className="text-xs text-error mt-0.5">{error}</p>}
      </div>
    </div>
  );
}
