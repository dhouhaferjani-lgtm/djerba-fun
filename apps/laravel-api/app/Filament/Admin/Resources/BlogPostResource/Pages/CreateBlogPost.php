<?php

namespace App\Filament\Admin\Resources\BlogPostResource\Pages;

use App\Filament\Admin\Resources\BlogPostResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\Translatable;
use Illuminate\Support\HtmlString;

class CreateBlogPost extends CreateRecord
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
                ->modalContent(fn () => new HtmlString(
                    view('filament.pages.blog-preview', [
                        'title' => $this->data['title'] ?? '',
                        'content' => $this->data['content'] ?? '',
                        'featuredImage' => $this->data['featured_image'] ?? null,
                        'excerpt' => $this->data['excerpt'] ?? '',
                    ])->render()
                )),
            Actions\LocaleSwitcher::make(),
        ];
    }
}
