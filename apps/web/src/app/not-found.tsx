import Link from 'next/link';
import { Button } from '@go-adventure/ui';
import { Home, Search } from 'lucide-react';

/**
 * 404 Not Found Page
 *
 * Displayed when a user navigates to a non-existent route.
 */
export default function NotFound() {
  return (
    <html lang="en">
      <body>
        <div className="min-h-screen bg-gradient-to-b from-[#f5f0d1] to-white flex items-center justify-center px-4">
          <div className="max-w-2xl w-full text-center space-y-8">
            {/* 404 Illustration */}
            <div className="space-y-4">
              <h1 className="text-9xl font-bold text-[#0D642E]">404</h1>
              <h2 className="text-3xl font-semibold text-neutral-900">Page Not Found</h2>
              <p className="text-lg text-neutral-600">
                Oops! The adventure you&apos;re looking for doesn&apos;t exist. Perhaps it&apos;s
                time to explore new horizons.
              </p>
            </div>

            {/* Search and Navigation */}
            <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
              <Link href="/en">
                <Button variant="primary" size="lg">
                  <Home className="h-5 w-5 mr-2" />
                  Back to Home
                </Button>
              </Link>
              <Link href="/en/listings">
                <Button variant="outline" size="lg">
                  <Search className="h-5 w-5 mr-2" />
                  Browse Adventures
                </Button>
              </Link>
            </div>

            {/* Helpful Suggestions */}
            <div className="pt-8 border-t border-neutral-200">
              <h3 className="text-lg font-semibold text-neutral-900 mb-4">Popular Destinations</h3>
              <div className="flex flex-wrap gap-3 justify-center">
                <Link
                  href="/en/listings?type=tour"
                  className="px-4 py-2 bg-white rounded-full text-sm text-neutral-700 hover:bg-[#8BC34A] hover:text-white transition-colors"
                >
                  Tours
                </Link>
                <Link
                  href="/en/listings?type=event"
                  className="px-4 py-2 bg-white rounded-full text-sm text-neutral-700 hover:bg-[#8BC34A] hover:text-white transition-colors"
                >
                  Events
                </Link>
                <Link
                  href="/en/listings?category=outdoor"
                  className="px-4 py-2 bg-white rounded-full text-sm text-neutral-700 hover:bg-[#8BC34A] hover:text-white transition-colors"
                >
                  Outdoor Activities
                </Link>
              </div>
            </div>
          </div>
        </div>
      </body>
    </html>
  );
}
