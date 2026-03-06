'use client';

import { useTranslations } from 'next-intl';
import { Button, Dialog } from '@djerba-fun/ui';
import { useAuth } from '@/lib/contexts/AuthContext';
import { Menu, User, LogOut } from 'lucide-react';
import { CartIcon } from '../cart/CartIcon';
import { LocaleSwitcher } from './LocaleSwitcher';
import { AuthMascot, type MascotState } from '../molecules/AuthMascot';
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
  const tCommon = useTranslations('common');
  const { isAuthenticated, user, logout } = useAuth();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [showLogoutConfirm, setShowLogoutConfirm] = useState(false);
  const [logoutMascotState, setLogoutMascotState] = useState<MascotState>('idle');
  const scrolled = useScroll(50);

  const handleLogout = () => {
    setLogoutMascotState('error'); // sad — user is leaving
    setTimeout(() => {
      logout();
      setShowLogoutConfirm(false);
      setLogoutMascotState('idle');
    }, 800);
  };

  const handleCloseLogout = () => {
    setLogoutMascotState('success'); // happy — user stays!
    setTimeout(() => {
      setShowLogoutConfirm(false);
      setLogoutMascotState('idle');
    }, 800);
  };

  const navLinks = [
    { href: `/${locale}`, label: t('home') },
    { href: `/${locale}/listings?type=tour`, label: t('tours') },
    { href: `/${locale}/listings?type=nautical`, label: t('nautical') },
    { href: `/${locale}/listings?type=accommodation`, label: t('accommodations') },
    { href: `/${locale}/listings?type=event`, label: t('events') },
    { href: `/${locale}/blog`, label: t('blog') },
    { href: `/${locale}/custom-trip`, label: t('customTrip') },
  ];

  return (
    <header
      className={cn(
        'sticky top-0 z-50 w-full transition-all duration-300',
        scrolled ? 'bg-primary shadow-lg' : 'bg-primary'
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

          {/* Right Side: Cart + Locale + Auth */}
          <div className="flex items-center gap-4">
            <CartIcon locale={locale} />
            <LocaleSwitcher locale={locale} />

            {isAuthenticated && user ? (
              <div className="hidden md:flex items-center gap-2">
                <NavLink href={`/${locale}/dashboard`}>
                  <Button variant="ghost" size="sm" className="text-white hover:bg-white/10">
                    <User className="h-4 w-4 mr-2" />
                    {user.displayName}
                  </Button>
                </NavLink>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => setShowLogoutConfirm(true)}
                  className="text-white hover:bg-white/10"
                >
                  <LogOut className="h-4 w-4 mr-2" />
                  {tAuth('logout')}
                </Button>
              </div>
            ) : (
              <div className="hidden md:flex items-center gap-2">
                <NavLink href={`/${locale}/auth/login`}>
                  <Button variant="ghost" size="sm" className="text-white hover:bg-white/10">
                    {tAuth('login')}
                  </Button>
                </NavLink>
                <NavLink href={`/${locale}/auth/register`}>
                  <Button
                    size="sm"
                    className="bg-white text-primary hover:bg-white/90 font-semibold"
                  >
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
        onLogoutClick={() => {
          setMobileMenuOpen(false);
          setShowLogoutConfirm(true);
        }}
      />

      {/* Logout Confirmation Dialog */}
      <Dialog
        isOpen={showLogoutConfirm}
        onClose={handleCloseLogout}
        size="sm"
        showCloseButton={false}
        className="max-w-xs"
      >
        <div className="text-center">
          <div className="mx-auto w-32 h-32 mb-2 overflow-hidden flex items-center justify-center">
            <div className="scale-75 origin-center">
              <AuthMascot state={logoutMascotState} watchDirection={0.5} />
            </div>
          </div>
          <h3 className="text-lg font-semibold text-gray-900 mb-2">
            {tAuth('logout_confirm_title')}
          </h3>
          <p className="text-sm text-gray-500 mb-5">{tAuth('logout_confirm_message')}</p>
          <div className="flex flex-col-reverse sm:flex-row gap-3 sm:justify-center">
            <Button variant="outline" onClick={handleCloseLogout} className="sm:min-w-[120px]">
              {tCommon('cancel')}
            </Button>
            <Button variant="destructive" onClick={handleLogout} className="sm:min-w-[120px]">
              {tAuth('logout')}
            </Button>
          </div>
        </div>
      </Dialog>
    </header>
  );
}
