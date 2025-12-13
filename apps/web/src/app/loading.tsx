/**
 * Root Loading Component
 *
 * Displays a loading spinner while the initial page is being rendered.
 */
export default function Loading() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-b from-[#f5f0d1] to-white">
      <div className="text-center space-y-4">
        {/* Spinner */}
        <div className="relative w-16 h-16 mx-auto">
          <div className="absolute top-0 left-0 w-full h-full border-4 border-[#0D642E] border-t-transparent rounded-full animate-spin" />
        </div>

        {/* Loading Text */}
        <p className="text-lg text-neutral-600 font-medium">Loading your adventure...</p>
      </div>
    </div>
  );
}
