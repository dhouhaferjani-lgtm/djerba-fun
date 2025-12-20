'use client';

import { useEffect, useCallback } from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '../../utils/cn';
import { X } from 'lucide-react';

const dialogVariants = cva('relative bg-white shadow-2xl w-full overflow-y-auto', {
  variants: {
    size: {
      sm: 'max-w-sm rounded-2xl max-h-[90vh]',
      md: 'max-w-md rounded-2xl max-h-[90vh]',
      lg: 'max-w-lg rounded-2xl max-h-[90vh]',
      xl: 'max-w-xl rounded-2xl max-h-[90vh]',
      full: 'max-w-full mx-4 sm:max-w-lg md:max-w-2xl lg:max-w-4xl rounded-2xl max-h-[90vh]',
    },
    variant: {
      default: '',
      bottomSheet: '',
    },
  },
  defaultVariants: {
    size: 'md',
    variant: 'default',
  },
});

export interface DialogProps extends VariantProps<typeof dialogVariants> {
  isOpen: boolean;
  onClose: () => void;
  title?: string;
  children: React.ReactNode;
  className?: string;
  showCloseButton?: boolean;
}

export function Dialog({
  isOpen,
  onClose,
  title,
  children,
  size,
  variant = 'default',
  className,
  showCloseButton = true,
}: DialogProps) {
  // Handle escape key
  const handleEscape = useCallback(
    (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        onClose();
      }
    },
    [onClose]
  );

  useEffect(() => {
    if (isOpen) {
      document.addEventListener('keydown', handleEscape);
      document.body.style.overflow = 'hidden';
    }

    return () => {
      document.removeEventListener('keydown', handleEscape);
      document.body.style.overflow = 'unset';
    };
  }, [isOpen, handleEscape]);

  if (!isOpen) return null;

  const isBottomSheet = variant === 'bottomSheet';

  return (
    <div
      className={cn(
        'fixed inset-0 z-50',
        isBottomSheet ? 'flex items-end justify-center' : 'flex items-center justify-center p-4'
      )}
    >
      {/* Backdrop */}
      <div
        className="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity animate-in fade-in duration-200"
        onClick={onClose}
        aria-hidden="true"
      />

      {/* Dialog */}
      <div
        className={cn(
          dialogVariants({ size: isBottomSheet ? undefined : size, variant }),
          isBottomSheet && [
            // Bottom sheet specific styles
            'w-full max-w-none rounded-t-3xl rounded-b-none',
            'max-h-[90vh]',
            'animate-in slide-in-from-bottom duration-300 ease-out',
            // Add safe area padding for notched devices
            'pb-safe',
          ],
          !isBottomSheet && 'animate-in zoom-in-95 duration-200',
          className
        )}
        role="dialog"
        aria-modal="true"
        aria-labelledby={title ? 'dialog-title' : undefined}
      >
        {/* Drag handle for bottom sheet */}
        {isBottomSheet && (
          <div className="flex justify-center pt-3 pb-1">
            <div className="w-10 h-1 bg-gray-300 rounded-full" />
          </div>
        )}

        {/* Header */}
        {(title || showCloseButton) && (
          <div
            className={cn(
              'sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10',
              !isBottomSheet && 'rounded-t-2xl'
            )}
          >
            {title && (
              <h2 id="dialog-title" className="text-lg font-semibold text-gray-900">
                {title}
              </h2>
            )}
            {showCloseButton && (
              <button
                type="button"
                onClick={onClose}
                className="p-2 -mr-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors"
                aria-label="Close dialog"
              >
                <X className="h-5 w-5" />
              </button>
            )}
          </div>
        )}

        {/* Content */}
        <div className="p-6">{children}</div>
      </div>
    </div>
  );
}

Dialog.displayName = 'Dialog';
