'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { Card, Button, Dialog } from '@djerba-fun/ui';
import { Calendar } from 'lucide-react';
import { useMediaQuery } from '@/hooks/useMediaQuery';
import { PriceDisplay } from '@/components/molecules/PriceDisplay';
import { getServiceTypeColors, getServiceTypeButtonClasses } from '@/lib/utils/serviceTypeColors';
import { cn } from '@/lib/utils/cn';
import type { Pricing, ServiceType } from '@djerba-fun/schemas';

interface BookingPanelProps {
  children: React.ReactNode;
  pricing: Pricing;
  serviceType: ServiceType;
  isOpen?: boolean;
  onOpenChange?: (open: boolean) => void;
}

export function BookingPanel({
  children,
  pricing,
  serviceType,
  isOpen: controlledIsOpen,
  onOpenChange,
}: BookingPanelProps) {
  const t = useTranslations('listing');

  const [internalIsOpen, setInternalIsOpen] = useState(false);

  // Use controlled state if provided, otherwise use internal state
  const isOpen = controlledIsOpen !== undefined ? controlledIsOpen : internalIsOpen;
  const setIsOpen = onOpenChange || setInternalIsOpen;

  // Check if we're on mobile (less than 1024px)
  const isMobile = useMediaQuery('(max-width: 1023px)');

  const basePrice = pricing.displayPrice || pricing.tndPrice || 0;
  const currency = pricing.displayCurrency || 'EUR';

  // Get service-type colors for theming
  const colors = getServiceTypeColors(serviceType);
  const buttonClasses = getServiceTypeButtonClasses(serviceType);

  // Desktop: Render as sticky sidebar card
  if (!isMobile) {
    return (
      <Card className="sticky top-20">
        <div className="p-6 space-y-6">{children}</div>
      </Card>
    );
  }

  // Mobile: Render with floating button + dialog
  return (
    <>
      {/* Floating Book Now button at bottom of screen - Service-type colored border */}
      <div
        className={cn(
          'fixed bottom-0 left-0 right-0 bg-white border-t-2 z-40 shadow-lg safe-area-bottom',
          colors.border
        )}
      >
        <div className="px-4 py-3 max-w-lg mx-auto space-y-3">
          {/* Price - stacked on top for better visibility */}
          <div className="flex items-center justify-between" data-testid="listing-price">
            <PriceDisplay amount={basePrice} currency={currency} size="md" showFrom perPerson />
          </div>
          {/* Full-width button for better mobile UX - Service-type colored */}
          <Button
            variant="primary"
            size="lg"
            onClick={() => setIsOpen(true)}
            className={cn('w-full py-4 text-base font-semibold', buttonClasses)}
            data-testid="book-now-button"
          >
            <Calendar className="h-5 w-5 mr-2" />
            {t('check_availability')}
          </Button>
        </div>
      </div>

      {/* Bottom sheet dialog for availability selection on mobile */}
      <Dialog
        isOpen={isOpen}
        onClose={() => setIsOpen(false)}
        title={t('check_availability')}
        variant="bottomSheet"
      >
        {children}
      </Dialog>

      {/* Spacer to prevent content from being hidden behind fixed button */}
      <div className="h-32 lg:hidden" />
    </>
  );
}

BookingPanel.displayName = 'BookingPanel';
