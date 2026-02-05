<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\ExtraResource\Pages;

use App\Filament\Vendor\Resources\ExtraResource;
use App\Models\ExtraTemplate;
use App\Models\ListingExtra;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateExtra extends CreateRecord
{
    protected static string $resource = ExtraResource::class;

    protected ?string $templateId = null;

    protected ?string $listingId = null;

    public function mount(): void
    {
        parent::mount();

        // Capture URL params
        $this->templateId = request()->query('template_id');
        $this->listingId = request()->query('listing_id');

        // If template_id provided, pre-fill form with template data
        if ($this->templateId) {
            $template = ExtraTemplate::find($this->templateId);

            if ($template) {
                $this->form->fill([
                    'name' => $template->getTranslations('name'),
                    'description' => $template->getTranslations('description'),
                    'short_description' => $template->getTranslations('short_description'),
                    'pricing_type' => $template->pricing_type?->value,
                    'base_price_tnd' => $template->suggested_price_tnd,
                    'base_price_eur' => $template->suggested_price_eur,
                    'person_type_prices' => $template->person_type_prices,
                    'category' => $template->category?->value,
                    'min_quantity' => $template->min_quantity ?? 0,
                    'max_quantity' => $template->max_quantity,
                    'capacity_per_unit' => $template->capacity_per_unit,
                    'track_inventory' => $template->track_inventory ?? false,
                    'is_active' => true,
                    'default_quantity' => 1,
                    'is_required' => false,
                    'auto_add' => false,
                    'allow_quantity_change' => true,
                    'display_order' => 0,
                ]);
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['vendor_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        // If listing_id was provided, attach the new extra to that listing
        if ($this->listingId) {
            ListingExtra::create([
                'id' => (string) Str::uuid(),
                'listing_id' => $this->listingId,
                'extra_id' => $this->record->id,
                'display_order' => 0,
                'is_featured' => false,
                'is_active' => true,
            ]);

            // Get extra name for notification
            $extraName = $this->record->getTranslation('name', app()->getLocale());

            if (is_array($extraName)) {
                $extraName = $extraName[app()->getLocale()] ?? $extraName['en'] ?? $extraName['fr'] ?? 'Extra';
            }
            $extraName = $extraName ?: 'Extra';

            Notification::make()
                ->title('Extra created and attached')
                ->body("'{$extraName}' has been created and added to the listing.")
                ->success()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        // If listing_id was provided, redirect back to listing edit page
        if ($this->listingId) {
            return route('filament.vendor.resources.listings.edit', ['record' => $this->listingId]);
        }

        return $this->getResource()::getUrl('index');
    }
}
