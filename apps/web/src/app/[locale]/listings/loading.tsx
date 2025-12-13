/**
 * Listings Page Loading State
 *
 * Displays skeleton loaders for the listings grid and filters.
 */
export default function ListingsLoading() {
  return (
    <div className="container mx-auto px-4 py-8">
      {/* Header Skeleton */}
      <div className="mb-8 space-y-4">
        <div className="h-10 bg-neutral-200 rounded w-64 animate-pulse" />
        <div className="h-6 bg-neutral-200 rounded w-96 animate-pulse" />
      </div>

      {/* Filters Skeleton */}
      <div className="mb-6 flex gap-4 flex-wrap">
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="h-10 bg-neutral-200 rounded w-32 animate-pulse" />
        ))}
      </div>

      {/* Grid Skeleton */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {[1, 2, 3, 4, 5, 6].map((i) => (
          <div key={i} className="bg-white rounded-lg shadow overflow-hidden">
            {/* Image Skeleton */}
            <div className="h-48 bg-neutral-200 animate-pulse" />

            {/* Content Skeleton */}
            <div className="p-4 space-y-3">
              <div className="h-6 bg-neutral-200 rounded w-3/4 animate-pulse" />
              <div className="h-4 bg-neutral-200 rounded w-full animate-pulse" />
              <div className="h-4 bg-neutral-200 rounded w-2/3 animate-pulse" />

              {/* Footer Skeleton */}
              <div className="flex items-center justify-between pt-2">
                <div className="h-5 bg-neutral-200 rounded w-20 animate-pulse" />
                <div className="h-8 bg-neutral-200 rounded w-24 animate-pulse" />
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
