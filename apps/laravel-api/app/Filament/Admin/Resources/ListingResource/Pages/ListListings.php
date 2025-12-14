<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ListingResource\Pages;

use App\Enums\ListingStatus;
use App\Filament\Admin\Resources\ListingResource;
use App\Models\Listing;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListListings extends ListRecords
{
    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Listing'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => Listing::count()),

            'pending' => Tab::make('Pending Review')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ListingStatus::PENDING_REVIEW))
                ->badge(fn () => Listing::where('status', ListingStatus::PENDING_REVIEW)->count())
                ->badgeColor('warning'),

            'published' => Tab::make('Published')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ListingStatus::PUBLISHED))
                ->badge(fn () => Listing::where('status', ListingStatus::PUBLISHED)->count())
                ->badgeColor('success'),

            'draft' => Tab::make('Drafts')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ListingStatus::DRAFT))
                ->badge(fn () => Listing::where('status', ListingStatus::DRAFT)->count()),

            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ListingStatus::REJECTED))
                ->badge(fn () => Listing::where('status', ListingStatus::REJECTED)->count())
                ->badgeColor('danger'),

            'archived' => Tab::make('Archived')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ListingStatus::ARCHIVED))
                ->badge(fn () => Listing::where('status', ListingStatus::ARCHIVED)->count()),
        ];
    }
}
