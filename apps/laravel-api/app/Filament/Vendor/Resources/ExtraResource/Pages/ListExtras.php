<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\ExtraResource\Pages;

use App\Enums\ExtraCategory;
use App\Filament\Vendor\Resources\ExtraResource;
use App\Models\ExtraTemplate;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListExtras extends ListRecords
{
    protected static string $resource = ExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createFromTemplate')
                ->label('Create from Template')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->modalHeading('Create Extra from Template')
                ->modalDescription('Select a template to create a new extra based on common tourism add-ons. You can customize it after creation.')
                ->form([
                    Forms\Components\Select::make('category_filter')
                        ->label('Filter by Category')
                        ->options(ExtraCategory::class)
                        ->placeholder('All categories')
                        ->live(),

                    Forms\Components\Select::make('template_id')
                        ->label('Select Template')
                        ->options(function (Forms\Get $get) {
                            $query = ExtraTemplate::active()->ordered();

                            $categoryFilter = $get('category_filter');

                            if ($categoryFilter) {
                                $category = ExtraCategory::tryFrom($categoryFilter);

                                if ($category) {
                                    $query->byCategory($category);
                                }
                            }

                            return $query->get()
                                ->mapWithKeys(function (ExtraTemplate $template) {
                                    $name = $template->getTranslation('name', app()->getLocale());
                                    $category = $template->category?->label() ?? 'Other';
                                    $price = $template->suggested_price_tnd
                                        ? number_format((float) $template->suggested_price_tnd, 2) . ' TND'
                                        : '';

                                    return [
                                        $template->id => "{$name} ({$category}) - {$price}",
                                    ];
                                });
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('The extra will be created as inactive so you can customize it first.'),
                ])
                ->action(function (array $data) {
                    $template = ExtraTemplate::find($data['template_id']);

                    if (! $template) {
                        Notification::make()
                            ->title('Template not found')
                            ->danger()
                            ->send();

                        return;
                    }

                    $extra = $template->cloneForVendor(auth()->id());

                    Notification::make()
                        ->title('Extra created from template')
                        ->body("'{$extra->getTranslation('name', 'en')}' has been created. It's inactive - customize and activate it when ready.")
                        ->success()
                        ->send();

                    return redirect(ExtraResource::getUrl('edit', ['record' => $extra]));
                })
                ->visible(fn () => ExtraTemplate::active()->exists()),

            Actions\CreateAction::make(),
        ];
    }
}
