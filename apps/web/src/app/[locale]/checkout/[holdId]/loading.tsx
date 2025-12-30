/**
 * Performance Optimization: Loading state for checkout page
 *
 * Displays skeleton UI while checkout data is being loaded.
 * Shows booking wizard structure for better UX during data fetch.
 *
 * Benefits:
 * - Prevents layout shift
 * - Shows clear loading state for critical checkout flow
 * - Better perceived performance
 */

export default function CheckoutLoading() {
  return (
    <div className="container mx-auto px-4 py-8 max-w-4xl">
      {/* Hold timer skeleton */}
      <div className="mb-6 bg-primary-50 border border-primary-200 rounded-lg p-4">
        <div className="h-4 bg-primary-200 rounded w-48 animate-pulse"></div>
      </div>

      {/* Progress indicator skeleton */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          {[1, 2, 3].map((i) => (
            <div key={i} className="flex items-center flex-1">
              <div className="flex flex-col items-center flex-1">
                <div className="w-10 h-10 rounded-full bg-neutral-200 animate-pulse"></div>
                <div className="h-4 bg-neutral-100 rounded w-24 mt-2 animate-pulse"></div>
              </div>
              {i < 3 && <div className="h-1 flex-1 mx-2 bg-neutral-200 animate-pulse"></div>}
            </div>
          ))}
        </div>
      </div>

      {/* Form skeleton */}
      <div className="bg-white rounded-lg shadow-sm border border-neutral-200 p-6 space-y-6">
        <div className="h-6 bg-neutral-200 rounded w-48 mb-4 animate-pulse"></div>

        {/* Form fields skeleton */}
        {[1, 2, 3, 4].map((i) => (
          <div key={i}>
            <div className="h-4 bg-neutral-100 rounded w-32 mb-2 animate-pulse"></div>
            <div className="h-10 bg-neutral-50 rounded animate-pulse"></div>
          </div>
        ))}

        {/* Button skeleton */}
        <div className="h-12 bg-neutral-200 rounded w-full mt-6 animate-pulse"></div>
      </div>

      {/* Summary sidebar skeleton */}
      <div className="mt-8 bg-white rounded-lg shadow-sm border border-neutral-200 p-6">
        <div className="h-6 bg-neutral-200 rounded w-32 mb-4 animate-pulse"></div>
        <div className="space-y-3">
          {[1, 2, 3].map((i) => (
            <div key={i} className="flex justify-between">
              <div className="h-4 bg-neutral-100 rounded w-24 animate-pulse"></div>
              <div className="h-4 bg-neutral-100 rounded w-16 animate-pulse"></div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
