/* eslint-disable @typescript-eslint/no-explicit-any -- next-intl Link requires typed routes, using any for dynamic hrefs */
import Link from 'next/link';
import { useTranslations } from 'next-intl';

interface FooterProps {
  locale: string;
}

export function Footer({ locale }: FooterProps) {
  const t = useTranslations('footer');
  const tNav = useTranslations('navigation');

  const currentYear = new Date().getFullYear();

  const links = [
    { href: `/${locale}`, label: tNav('home') },
    { href: `/${locale}/listings?type=tour`, label: tNav('tours') },
    { href: `/${locale}/listings?type=event`, label: tNav('events') },
  ];

  const legal = [
    { href: `/${locale}/terms`, label: t('terms') },
    { href: `/${locale}/privacy`, label: t('privacy') },
  ];

  return (
    <footer className="border-t border-neutral-200 bg-white">
      <div className="container mx-auto px-4 py-12">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
          {/* Brand */}
          <div className="col-span-1 md:col-span-2">
            <h3 className="text-2xl font-bold text-[#0D642E] mb-4">Go Adventure</h3>
            <p className="text-sm text-neutral-600 max-w-md">{tNav('home')}</p>
          </div>

          {/* Navigation Links */}
          <div>
            <h4 className="font-semibold text-neutral-900 mb-4">{tNav('home')}</h4>
            <ul className="space-y-2">
              {links.map((link) => (
                <li key={link.href}>
                  <Link
                    href={link.href as any}
                    className="text-sm text-neutral-600 hover:text-[#0D642E] transition-colors"
                  >
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Legal Links */}
          <div>
            <h4 className="font-semibold text-neutral-900 mb-4">Legal</h4>
            <ul className="space-y-2">
              {legal.map((link) => (
                <li key={link.href}>
                  <Link
                    href={link.href as any}
                    className="text-sm text-neutral-600 hover:text-[#0D642E] transition-colors"
                  >
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        </div>

        {/* Copyright */}
        <div className="mt-8 pt-8 border-t border-neutral-200">
          <p className="text-center text-sm text-neutral-500">
            © {currentYear} {t('copyright')}
          </p>
        </div>
      </div>
    </footer>
  );
}
