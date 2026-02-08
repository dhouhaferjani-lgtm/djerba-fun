<?php

namespace App\Filament\Admin\Resources\AvailabilityRuleResource\Pages;

use App\Enums\AvailabilityRuleType;
use App\Filament\Admin\Resources\AvailabilityRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAvailabilityRule extends EditRecord
{
    protected static string $resource = AvailabilityRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Clean up virtual toggle field
        unset($data['enable_date_range']);

        // For WEEKLY/DAILY: if date pickers were hidden (toggle OFF), clear the range
        if (in_array($data['rule_type'] ?? '', [AvailabilityRuleType::WEEKLY->value, AvailabilityRuleType::DAILY->value])) {
            if (! array_key_exists('end_date', $data)) {
                $data['start_date'] = now()->format('Y-m-d');
                $data['end_date'] = null;
            }
        }

        // For SPECIFIC_DATES, clear stale start_date/end_date
        if (($data['rule_type'] ?? '') === AvailabilityRuleType::SPECIFIC_DATES->value) {
            $data['start_date'] = null;
            $data['end_date'] = null;
        }

        return $data;
    }
}
