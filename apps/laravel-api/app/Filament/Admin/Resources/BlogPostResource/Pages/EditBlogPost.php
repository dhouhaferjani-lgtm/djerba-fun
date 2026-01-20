<?php

namespace App\Filament\Admin\Resources\BlogPostResource\Pages;

use App\Filament\Admin\Resources\BlogPostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\EditRecord\Concerns\Translatable;

class EditBlogPost extends EditRecord
{
    use Translatable;

    protected static string $resource = BlogPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label(__('filament.actions.preview'))
                ->icon('heroicon-o-eye')
                ->color('info')
                ->modalHeading(__('filament.actions.preview'))
                ->modalWidth('7xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('filament.actions.close'))
                ->modalContent(fn () => view('filament.pages.blog-preview', [
                    'title' => $this->data['title'] ?? $this->record->title ?? '',
                    'content' => $this->data['content'] ?? $this->record->content ?? '',
                    'featuredImage' => $this->data['featured_image'] ?? $this->record->featured_image ?? null,
                    'excerpt' => $this->data['excerpt'] ?? $this->record->excerpt ?? '',
                ])),
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
