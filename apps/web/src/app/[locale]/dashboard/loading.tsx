/**
 * Performance Optimization: Loading state for dashboard
 *
 * Displays skeleton UI while dashboard data is being fetched.
 * Matches dashboard layout for better UX.
 *
 * Benefits:
 * - Prevents layout shift
 * - Shows clear loading state for user data
 * - Better perceived performance
 */

export default function DashboardLoading() {
  return (
    <div className="container mx-auto px-4 py-8">
      {/* Header skeleton */}
      <div className="mb-8">
        <div className="h-10 bg-neutral-200 rounded w-64 mb-2 animate-pulse"></div>
        <div className="h-5 bg-neutral-100 rounded w-96 animate-pulse"></div>
      </div>

      {/* Stats cards skeleton */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {[1, 2, 3].map((i) => (
          <div key={i} className="bg-white rounded-lg border border-neutral-200 p-6">
            <div className="h-4 bg-neutral-100 rounded w-32 mb-4 animate-pulse"></div>
            <div className="h-8 bg-neutral-200 rounded w-20 animate-pulse"></div>
          </div>
        ))}
      </div>

      {/* Recent bookings skeleton */}
      <div className="bg-white rounded-lg border border-neutral-200 p-6">
        <div className="h-6 bg-neutral-200 rounded w-48 mb-6 animate-pulse"></div>
        <div className="space-y-4">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="border-b border-neutral-100 pb-4 last:border-0">
              <div className="flex justify-between items-start mb-2">
                <div className="h-5 bg-neutral-200 rounded w-48 animate-pulse"></div>
                <div className="h-5 bg-neutral-100 rounded w-24 animate-pulse"></div>
              </div>
              <div className="flex gap-4">
                <div className="h-4 bg-neutral-100 rounded w-32 animate-pulse"></div>
                <div className="h-4 bg-neutral-100 rounded w-24 animate-pulse"></div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
