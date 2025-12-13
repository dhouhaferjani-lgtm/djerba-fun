/**
 * Listing Detail Page Loading State
 *
 * Displays skeleton loaders for the listing detail page.
 */
export default function ListingDetailLoading() {
  return (
    <div>
      {/* Hero Image Skeleton */}
      <div className="relative h-96 w-full bg-neutral-200 animate-pulse" />

      <div className="container mx-auto px-4 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Content Skeleton */}
          <div className="lg:col-span-2 space-y-8">
            {/* Title and Rating */}
            <div className="space-y-4">
              <div className="h-10 bg-neutral-200 rounded w-3/4 animate-pulse" />
              <div className="h-5 bg-neutral-200 rounded w-48 animate-pulse" />
            </div>

            {/* Quick Info */}
            <div className="flex gap-6">
              {[1, 2, 3].map((i) => (
                <div key={i} className="h-6 bg-neutral-200 rounded w-32 animate-pulse" />
              ))}
            </div>

            {/* Description */}
            <div className="space-y-3">
              <div className="h-8 bg-neutral-200 rounded w-32 animate-pulse" />
              <div className="space-y-2">
                <div className="h-4 bg-neutral-200 rounded w-full animate-pulse" />
                <div className="h-4 bg-neutral-200 rounded w-full animate-pulse" />
                <div className="h-4 bg-neutral-200 rounded w-3/4 animate-pulse" />
              </div>
            </div>

            {/* Highlights */}
            <div className="space-y-3">
              <div className="h-8 bg-neutral-200 rounded w-40 animate-pulse" />
              <div className="space-y-2">
                {[1, 2, 3, 4].map((i) => (
                  <div key={i} className="h-6 bg-neutral-200 rounded w-full animate-pulse" />
                ))}
              </div>
            </div>

            {/* Included / Not Included */}
            <div className="grid md:grid-cols-2 gap-6">
              {[1, 2].map((section) => (
                <div key={section} className="space-y-3">
                  <div className="h-6 bg-neutral-200 rounded w-32 animate-pulse" />
                  <div className="space-y-2">
                    {[1, 2, 3].map((i) => (
                      <div key={i} className="h-5 bg-neutral-200 rounded w-full animate-pulse" />
                    ))}
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Sidebar - Booking Card Skeleton */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-lg shadow-lg p-6 space-y-6">
              {/* Price Skeleton */}
              <div className="space-y-2">
                <div className="h-10 bg-neutral-200 rounded w-40 animate-pulse" />
                <div className="h-4 bg-neutral-200 rounded w-24 animate-pulse" />
              </div>

              {/* Button Skeleton */}
              <div className="space-y-3">
                <div className="h-12 bg-neutral-200 rounded w-full animate-pulse" />
                <div className="h-4 bg-neutral-200 rounded w-48 animate-pulse mx-auto" />
              </div>

              {/* Features Skeleton */}
              <div className="pt-6 border-t border-neutral-200 space-y-3">
                {[1, 2].map((i) => (
                  <div key={i} className="h-5 bg-neutral-200 rounded w-full animate-pulse" />
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
