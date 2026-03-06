import { ListingCard } from '../molecules/ListingCard';
import type { ListingSummary } from '@djerba-fun/schemas';

interface ListingGridProps {
  listings: ListingSummary[];
  locale: string;
  emptyMessage?: string;
}

export function ListingGrid({
  listings,
  locale,
  emptyMessage = 'No listings found',
}: ListingGridProps) {
  if (listings.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center py-12 text-center">
        <p className="text-lg text-neutral-500">{emptyMessage}</p>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {listings.map((listing) => (
        <ListingCard key={listing.id} listing={listing} locale={locale} />
      ))}
    </div>
  );
}
