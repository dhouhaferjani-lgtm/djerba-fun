import { Header } from '../organisms/Header';
import { Footer } from '../organisms/Footer';

interface MainLayoutProps {
  children: React.ReactNode;
  locale: string;
}

export function MainLayout({ children, locale }: MainLayoutProps) {
  return (
    <div className="flex min-h-screen flex-col">
      <Header locale={locale} />
      <main className="flex-1">{children}</main>
      <Footer locale={locale} />
    </div>
  );
}
