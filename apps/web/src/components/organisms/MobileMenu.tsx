'use client';

import { useTranslations } from 'next-intl';
import { Button } from '@go-adventure/ui';
import { useAuth } from '@/lib/contexts/AuthContext';
import { User, LogOut } from 'lucide-react';
import { NavLink } from '../atoms/NavLink';

interface NavLinkItem {
  href: string;
  label: string;
}

interface MobileMenuProps {
  isOpen: boolean;
  setIsOpen: (isOpen: boolean) => void;
  navLinks: NavLinkItem[];
  locale: string;
}

export function MobileMenu({ isOpen, setIsOpen, navLinks, locale }: MobileMenuProps) {
  const tAuth = useTranslations('auth');
  const { isAuthenticated, user, logout } = useAuth();

  if (!isOpen) return null;

  return (
    <div className="md:hidden bg-primary/95 backdrop-blur-sm absolute top-full left-0 w-full h-screen">
      <nav className="flex flex-col gap-4 p-4">
        {navLinks.map((link) => (
          <NavLink key={link.href} href={link.href} onClick={() => setIsOpen(false)}>
            {link.label}
          </NavLink>
        ))}
        <div className="border-t border-primary-light pt-4 mt-2">
          {isAuthenticated && user ? (
            <>
              <NavLink href={`/${locale}/dashboard`} onClick={() => setIsOpen(false)}>
                <Button
                  variant="ghost"
                  size="sm"
                  className="w-full justify-start font-bold text-white mb-2 hover:bg-primary-light"
                >
                  <User className="h-4 w-4 mr-2" />
                  {user.displayName}
                </Button>
              </NavLink>
              <Button
                variant="ghost"
                size="sm"
                className="w-full justify-start font-bold text-white hover:bg-primary-light"
                onClick={() => {
                  logout();
                  setIsOpen(false);
                }}
              >
                <LogOut className="h-4 w-4 mr-2" />
                {tAuth('logout')}
              </Button>
            </>
          ) : (
            <>
              <NavLink href={`/${locale}/auth/login`} onClick={() => setIsOpen(false)}>
                <Button
                  variant="ghost"
                  size="sm"
                  className="w-full justify-start font-bold text-white mb-2 hover:bg-primary-light"
                >
                  {tAuth('login')}
                </Button>
              </NavLink>
              <NavLink href={`/${locale}/auth/register`} onClick={() => setIsOpen(false)}>
                <Button variant="secondary" size="sm" className="w-full font-bold">
                  {tAuth('register')}
                </Button>
              </NavLink>
            </>
          )}
        </div>
      </nav>
    </div>
  );
}
