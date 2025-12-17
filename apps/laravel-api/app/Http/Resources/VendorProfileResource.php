<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
class VendorProfileResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'userId' => $this->user->uuid,
            'companyName' => $this->company_name,
            'companyType' => $this->company_type,
            'taxId' => $this->tax_id,
            'kycStatus' => $this->kyc_status->value,
            'commissionTier' => $this->commission_tier,
            'payoutAccountId' => $this->payout_account_id,
            'description' => $this->description,
            'websiteUrl' => $this->website_url,
            'phone' => $this->phone,
            'address' => is_array($this->address) ? $this->toCamelCase($this->address) : $this->address,
            'verifiedAt' => $this->verified_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
