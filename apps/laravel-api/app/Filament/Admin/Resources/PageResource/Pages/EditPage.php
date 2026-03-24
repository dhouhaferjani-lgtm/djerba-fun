<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PageResource\Pages;

use App\Filament\Admin\Resources\PageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Combine title translations into Spatie translatable format
        $data['title'] = [
            'en' => $data['title_en'] ?? '',
            'fr' => $data['title_fr'] ?? $data['title_en'] ?? '',
        ];
        unset($data['title_en'], $data['title_fr']);

        // Combine slug translations into Spatie translatable format
        $data['slug'] = [
            'en' => $data['slug_en'] ?? '',
            'fr' => $data['slug_fr'] ?? $data['slug_en'] ?? '',
        ];
        unset($data['slug_en'], $data['slug_fr']);

        // Handle published toggle
        $isPublished = $this->form->getState()['is_published'] ?? false;

        if ($isPublished) {
            $data['publishing_begins_at'] = now();
            $data['publishing_ends_at'] = null;
        } else {
            $data['publishing_begins_at'] = null;
            $data['publishing_ends_at'] = null;
        }

        // Ensure JSON fields are properly set (not empty strings)
        $jsonFields = ['highlights', 'key_facts', 'gallery', 'points_of_interest'];

        foreach ($jsonFields as $field) {
            if (empty($data[$field])) {
                $data[$field] = null;
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Page saved successfully';
    }
}
