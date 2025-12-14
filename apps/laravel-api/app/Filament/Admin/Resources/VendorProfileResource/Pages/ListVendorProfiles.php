<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\VendorProfileResource\Pages;

use App\Enums\KycStatus;
use App\Filament\Admin\Resources\VendorProfileResource;
use App\Models\VendorProfile;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListVendorProfiles extends ListRecords
{
    protected static string $resource = VendorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Vendor Profile'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Vendors')
                ->badge(fn () => VendorProfile::count()),

            'pending_review' => Tab::make('Pending Review')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('kyc_status', KycStatus::SUBMITTED))
                ->badge(fn () => VendorProfile::where('kyc_status', KycStatus::SUBMITTED)->count())
                ->badgeColor('warning'),

            'verified' => Tab::make('Verified')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('kyc_status', KycStatus::VERIFIED))
                ->badge(fn () => VendorProfile::where('kyc_status', KycStatus::VERIFIED)->count())
                ->badgeColor('success'),

            'pending' => Tab::make('Pending KYC')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('kyc_status', KycStatus::PENDING))
                ->badge(fn () => VendorProfile::where('kyc_status', KycStatus::PENDING)->count()),

            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('kyc_status', KycStatus::REJECTED))
                ->badge(fn () => VendorProfile::where('kyc_status', KycStatus::REJECTED)->count())
                ->badgeColor('danger'),
        ];
    }
}
