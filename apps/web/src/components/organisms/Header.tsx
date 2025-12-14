'use client';

import { useTranslations } from 'next-intl';
import { Button } from '@go-adventure/ui';
import { useAuth } from '@/lib/contexts/AuthContext';
import { Menu, User, LogOut } from 'lucide-react';
import { LocaleSwitcher } from './LocaleSwitcher';
import { useState } from 'react';
import { Logo } from '../atoms/Logo';
import { NavLink } from '../atoms/NavLink';
import { useScroll } from '@/lib/hooks/useScroll';
import { cn } from '@/lib/utils/cn';
import { MobileMenu } from './MobileMenu';

interface HeaderProps {
  locale: string;
}

export function Header({ locale }: HeaderProps) {
  const t = useTranslations('navigation');
  const tAuth = useTranslations('auth');
  const { isAuthenticated, user, logout } = useAuth();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const scrolled = useScroll(50);

  const navLinks = [
    { href: `/${locale}`, label: t('home') },
    { href: `/${locale}/listings?type=tour`, label: t('tours') },
    { href: `/${locale}/listings?type=event`, label: t('events') },
  ];

  return (
    <header
      className={cn(
        'sticky top-0 z-50 w-full transition-all duration-300',
        scrolled ? 'bg-primary shadow-lg' : 'bg-primary/90'
      )}
    >
      <div className="container mx-auto px-4">
        <div className="flex h-16 items-center justify-between">
          <Logo />

          {/* Desktop Navigation */}
          <nav className="hidden md:flex items-center gap-6">
            {navLinks.map((link) => (
              <NavLink key={link.href} href={link.href}>
                {link.label}
              </NavLink>
            ))}
          </nav>

          {/* Right Side: Locale + Auth */}
          <div className="flex items-center gap-4">
            <LocaleSwitcher locale={locale} />

            {isAuthenticated && user ? (
              <div className="hidden md:flex items-center gap-3">
                <NavLink href={`/${locale}/dashboard`}>
                  <Button variant="ghost" size="sm" className="text-white hover:bg-primary-light">
                    <User className="h-4 w-4 mr-2" />
                    {user.displayName}
                  </Button>
                </NavLink>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => logout()}
                  className="text-white hover:bg-primary-light"
                >
                  <LogOut className="h-4 w-4 mr-2" />
                  {tAuth('logout')}
                </Button>
              </div>
            ) : (
              <div className="hidden md:flex items-center gap-2">
                <NavLink href={`/${locale}/auth/login`}>
                  <Button variant="ghost" size="sm" className="text-white hover:bg-primary-light">
                    {tAuth('login')}
                  </Button>
                </NavLink>
                <NavLink href={`/${locale}/auth/register`}>
                  <Button variant="secondary" size="sm">
                    {tAuth('register')}
                  </Button>
                </NavLink>
              </div>
            )}

            {/* Mobile Menu Button */}
            <button
              className="md:hidden p-2 text-white"
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              aria-label="Open mobile menu"
            >
              <Menu className="h-6 w-6" />
            </button>
          </div>
        </div>
      </div>
      <MobileMenu
        isOpen={mobileMenuOpen}
        setIsOpen={setMobileMenuOpen}
        navLinks={navLinks}
        locale={locale}
      />
    </header>
  );
}
