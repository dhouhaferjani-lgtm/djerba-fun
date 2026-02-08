<?php

namespace App\Filament\Admin\Resources\AvailabilityRuleResource\Pages;

use App\Enums\AvailabilityRuleType;
use App\Filament\Admin\Resources\AvailabilityRuleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAvailabilityRule extends CreateRecord
{
    protected static string $resource = AvailabilityRuleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Clean up virtual toggle field
        unset($data['enable_date_range']);

        // For SPECIFIC_DATES, clear stale start_date/end_date
        if (($data['rule_type'] ?? '') === AvailabilityRuleType::SPECIFIC_DATES->value) {
            $data['start_date'] = null;
            $data['end_date'] = null;
        }

        return $data;
    }
}
