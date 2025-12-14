import Link from 'next/link';
import { useLocale } from 'next-intl';

export function Logo() {
  const locale = useLocale();
  return (
    <Link href={`/${locale}`} className="flex items-center">
      <span className="text-2xl font-bold text-white">Go Adventure</span>
    </Link>
  );
}
