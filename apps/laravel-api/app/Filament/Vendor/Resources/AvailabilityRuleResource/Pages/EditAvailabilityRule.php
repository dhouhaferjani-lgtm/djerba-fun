<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\AvailabilityRuleResource\Pages;

use App\Enums\AvailabilityRuleType;
use App\Filament\Vendor\Resources\AvailabilityRuleResource;
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

        // For SPECIFIC_DATES, clear stale start_date/end_date
        if (($data['rule_type'] ?? '') === AvailabilityRuleType::SPECIFIC_DATES->value) {
            $data['start_date'] = null;
            $data['end_date'] = null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
