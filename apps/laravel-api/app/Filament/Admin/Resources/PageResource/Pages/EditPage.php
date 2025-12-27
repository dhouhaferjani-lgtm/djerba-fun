<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PageResource\Pages;

use App\Filament\Admin\Resources\PageResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Actions\FlexibleLocaleSwitcher;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Pages\EditRecord\Concerns\TranslatableWithMedia;

class EditPage extends EditRecord
{
    use TranslatableWithMedia;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Add "Publish Now" action if page is a draft
        if (! $this->record->isPublished()) {
            $actions[] = Actions\Action::make('publishNow')
                ->label('Publish Now')
                ->icon('heroicon-o-globe-alt')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Publish Page')
                ->modalDescription('This will make the page visible to the public immediately.')
                ->action(function () {
                    $this->record->update([
                        'publishing_begins_at' => now(),
                        'publishing_ends_at' => null,
                    ]);

                    Notification::make()
                        ->title('Page Published')
                        ->body('The page is now live and visible to the public.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['publishing_begins_at', 'publishing_ends_at']);
                });
        }

        // Add "Unpublish" action if page is published
        if ($this->record->isPublished()) {
            $actions[] = Actions\Action::make('unpublish')
                ->label('Unpublish')
                ->icon('heroicon-o-eye-slash')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Unpublish Page')
                ->modalDescription('This will hide the page from the public.')
                ->action(function () {
                    $this->record->update([
                        'publishing_begins_at' => null,
                        'publishing_ends_at' => null,
                    ]);

                    Notification::make()
                        ->title('Page Unpublished')
                        ->body('The page is now a draft and hidden from the public.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['publishing_begins_at', 'publishing_ends_at']);
                });
        }

        $actions[] = FlexibleLocaleSwitcher::make();
        $actions[] = Actions\DeleteAction::make();

        return $actions;
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
