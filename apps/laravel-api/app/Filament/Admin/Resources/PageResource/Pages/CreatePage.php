<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PageResource\Pages;

use App\Filament\Admin\Resources\PageResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Actions\FlexibleLocaleSwitcher;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Pages\CreateRecord\Concerns\TranslatableWithMedia;

class CreatePage extends CreateRecord
{
    use TranslatableWithMedia;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('saveDraft')
                ->label('Save as Draft')
                ->icon('heroicon-o-bookmark')
                ->color('gray')
                ->action(function () {
                    $this->saveDraft();
                }),
            FlexibleLocaleSwitcher::make(),
        ];
    }

    protected function saveDraft(): void
    {
        $data = $this->form->getState();

        // Generate a slug if missing
        if (empty($data['slug']['en']) && !empty($data['title']['en'])) {
            $data['slug'] = ['en' => Str::slug($data['title']['en'])];
        } elseif (empty($data['slug']['en'])) {
            $data['slug'] = ['en' => 'draft-' . Str::random(8)];
        }

        // Set default title if missing
        if (empty($data['title']['en'])) {
            $data['title'] = ['en' => 'Untitled Draft'];
        }

        // Ensure it's NOT published (no publishing dates)
        $data['publishing_begins_at'] = null;
        $data['publishing_ends_at'] = null;

        // Set author if available
        $data['author_id'] = auth()->id();

        // Create the record
        $record = $this->getModel()::create($data);

        Notification::make()
            ->title('Draft Saved')
            ->body('Your page has been saved as a draft. You can continue editing it later.')
            ->success()
            ->send();

        $this->redirect(PageResource::getUrl('edit', ['record' => $record]));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set author
        $data['author_id'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Page created successfully';
    }
}
