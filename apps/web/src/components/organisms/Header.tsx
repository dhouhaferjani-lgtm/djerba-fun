'use client';

/* eslint-disable @typescript-eslint/no-explicit-any -- next-intl Link requires typed routes, using any for dynamic hrefs */
import Link from 'next/link';
import { useTranslations } from 'next-intl';
import { Button } from '@go-adventure/ui';
import { useAuth } from '@/lib/contexts/AuthContext';
import { Menu, User, LogOut } from 'lucide-react';
import { LocaleSwitcher } from './LocaleSwitcher';
import { useState } from 'react';

interface HeaderProps {
  locale: string;
}

export function Header({ locale }: HeaderProps) {
  const t = useTranslations('navigation');
  const tAuth = useTranslations('auth');
  const { isAuthenticated, user, logout } = useAuth();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const navLinks = [
    { href: `/${locale}`, label: t('home') },
    { href: `/${locale}/listings?type=tour`, label: t('tours') },
    { href: `/${locale}/listings?type=event`, label: t('events') },
  ];

  return (
    <header className="sticky top-0 z-50 w-full border-b border-neutral-200 bg-white">
      <div className="container mx-auto px-4">
        <div className="flex h-16 items-center justify-between">
          {/* Logo */}
          <Link href={`/${locale}` as any} className="flex items-center">
            <span className="text-2xl font-bold text-[#0D642E]">Go Adventure</span>
          </Link>

          {/* Desktop Navigation */}
          <nav className="hidden md:flex items-center gap-6">
            {navLinks.map((link) => (
              <Link
                key={link.href}
                href={link.href as any}
                className="text-sm font-medium text-neutral-700 hover:text-[#0D642E] transition-colors"
              >
                {link.label}
              </Link>
            ))}
          </nav>

          {/* Right Side: Locale + Auth */}
          <div className="flex items-center gap-4">
            <LocaleSwitcher locale={locale} />

            {isAuthenticated && user ? (
              <div className="hidden md:flex items-center gap-3">
                <Link href={`/${locale}/dashboard` as any}>
                  <Button variant="ghost" size="sm">
                    <User className="h-4 w-4 mr-2" />
                    {user.displayName}
                  </Button>
                </Link>
                <Button variant="ghost" size="sm" onClick={() => logout()}>
                  <LogOut className="h-4 w-4 mr-2" />
                  {tAuth('logout')}
                </Button>
              </div>
            ) : (
              <div className="hidden md:flex items-center gap-2">
                <Link href={`/${locale}/auth/login` as any}>
                  <Button variant="ghost" size="sm">
                    {tAuth('login')}
                  </Button>
                </Link>
                <Link href={`/${locale}/auth/register` as any}>
                  <Button variant="primary" size="sm">
                    {tAuth('register')}
                  </Button>
                </Link>
              </div>
            )}

            {/* Mobile Menu Button */}
            <button className="md:hidden p-2" onClick={() => setMobileMenuOpen(!mobileMenuOpen)}>
              <Menu className="h-6 w-6 text-neutral-700" />
            </button>
          </div>
        </div>

        {/* Mobile Menu */}
        {mobileMenuOpen && (
          <div className="md:hidden border-t border-neutral-200 py-4">
            <nav className="flex flex-col gap-3">
              {navLinks.map((link) => (
                <Link
                  key={link.href}
                  href={link.href as any}
                  className="text-sm font-medium text-neutral-700 hover:text-[#0D642E] py-2"
                  onClick={() => setMobileMenuOpen(false)}
                >
                  {link.label}
                </Link>
              ))}
              <div className="border-t border-neutral-200 pt-3 mt-2">
                {isAuthenticated && user ? (
                  <>
                    <Link href={`/${locale}/dashboard` as any}>
                      <Button variant="ghost" size="sm" className="w-full mb-2">
                        <User className="h-4 w-4 mr-2" />
                        {user.displayName}
                      </Button>
                    </Link>
                    <Button
                      variant="ghost"
                      size="sm"
                      className="w-full"
                      onClick={() => {
                        logout();
                        setMobileMenuOpen(false);
                      }}
                    >
                      <LogOut className="h-4 w-4 mr-2" />
                      {tAuth('logout')}
                    </Button>
                  </>
                ) : (
                  <>
                    <Link href={`/${locale}/auth/login` as any}>
                      <Button variant="ghost" size="sm" className="w-full mb-2">
                        {tAuth('login')}
                      </Button>
                    </Link>
                    <Link href={`/${locale}/auth/register` as any}>
                      <Button variant="primary" size="sm" className="w-full">
                        {tAuth('register')}
                      </Button>
                    </Link>
                  </>
                )}
              </div>
            </nav>
          </div>
        )}
      </div>
    </header>
  );
}
