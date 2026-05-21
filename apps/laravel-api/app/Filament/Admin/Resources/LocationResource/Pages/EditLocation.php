<?php

namespace App\Filament\Admin\Resources\LocationResource\Pages;

use App\Filament\Admin\Resources\LocationResource;
use App\Models\Location;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLocation extends EditRecord
{
    use EditRecord\Concerns\Translatable;

    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make()
                ->modalDescription(function (Location $record): ?string {
                    // Mirror of LocationResource row DeleteAction: surface the
                    // cascade impact on the standard "Are you sure?" modal when
                    // smart-allow will hard-delete soft-deleted listings.
                    $trashedCount = $record->listings()->onlyTrashed()->count();

                    return $trashedCount > 0
                        ? __('filament.notifications.delete_location_cascade_warning', ['count' => $trashedCount])
                        : null;
                })
                ->before(function (Actions\DeleteAction $action, Location $record) {
                    // Same smart-allow guard as LocationResource (row + bulk).
                    // See LocationResource::table() for the cascade-chain rationale.
                    $activeCount = $record->listings()->count();

                    if ($activeCount > 0) {
                        Notification::make()
                            ->danger()
                            ->title(__('filament.notifications.cannot_delete_location_title'))
                            ->body(__('filament.notifications.cannot_delete_location_active_body', ['count' => $activeCount]))
                            ->send();

                        $action->cancel();

                        return;
                    }

                    $trashedListingIds = $record->listings()->onlyTrashed()->pluck('id');

                    if ($trashedListingIds->isEmpty()) {
                        return;
                    }

                    $liveCartItemCount = \App\Models\CartItem::query()
                        ->whereIn('listing_id', $trashedListingIds)
                        ->whereHas('cart', fn ($q) => $q->live())
                        ->count();

                    if ($liveCartItemCount > 0) {
                        Notification::make()
                            ->danger()
                            ->title(__('filament.notifications.cannot_delete_location_title'))
                            ->body(__('filament.notifications.cannot_delete_location_cart_items_body', ['count' => $liveCartItemCount]))
                            ->send();

                        $action->cancel();

                        return;
                    }

                    // Pre-clean DEAD cart_items pointing at these trashed listings
                    // so the cascadeOnDelete chain doesn't trip the cart_items.listing_id
                    // (or .hold_id via the booking_holds cascade) RESTRICT FKs.
                    \App\Models\CartItem::whereIn('listing_id', $trashedListingIds)->delete();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
