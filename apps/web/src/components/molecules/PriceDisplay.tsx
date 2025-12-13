import { useTranslations } from 'next-intl';

interface PriceDisplayProps {
  amount: number;
  currency: string;
  size?: 'sm' | 'md' | 'lg';
  showFrom?: boolean;
  perPerson?: boolean;
  className?: string;
}

const currencySymbols: Record<string, string> = {
  EUR: '€',
  USD: '$',
  GBP: '£',
  CAD: '$',
};

const sizeClasses = {
  sm: 'text-lg',
  md: 'text-2xl',
  lg: 'text-3xl',
};

export function PriceDisplay({
  amount,
  currency,
  size = 'md',
  showFrom = false,
  perPerson = true,
  className = '',
}: PriceDisplayProps) {
  const t = useTranslations('common');
  const symbol = currencySymbols[currency] || currency;
  const formattedAmount = (amount / 100).toFixed(2);

  return (
    <div className={`flex flex-col ${className}`}>
      <div className="flex items-baseline gap-1">
        {showFrom && <span className="text-sm font-normal text-neutral-600">{t('from')}</span>}
        <span className={`font-bold text-[#0D642E] ${sizeClasses[size]}`}>
          {symbol}
          {formattedAmount}
        </span>
      </div>
      {perPerson && <span className="text-xs text-neutral-500">{t('per_person')}</span>}
    </div>
  );
}
