<?php

namespace App\ContentBlocks;

use Closure;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Spatie\MediaLibrary\HasMedia;
use Statikbe\FilamentFlexibleContentBlocks\ContentBlocks\AbstractFilamentFlexibleContentBlock;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Form\Fields\ImageField;
use Statikbe\FilamentFlexibleContentBlocks\Models\Contracts\HasContentBlocks;

class CategoriesGridBlock extends AbstractFilamentFlexibleContentBlock
{
    public array $categories = [];

    public function __construct(HasContentBlocks&HasMedia $record, ?array $blockData)
    {
        parent::__construct($record, $blockData);

        $this->categories = $blockData['categories'] ?? [];
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-squares-2x2';
    }

    public static function getNameSuffix(): string
    {
        return 'categories-grid';
    }

    /**
     * Get the Filament form fields for the Filament editor.
     */
    protected static function makeFilamentSchema(): array|Closure
    {
        return [
            Repeater::make('categories')
                ->label('Categories')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label('Category Name')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('count')
                            ->label('Item Count')
                            ->numeric()
                            ->default(0),

                        TextInput::make('url')
                            ->label('Link URL')
                            ->url()
                            ->columnSpanFull(),

                        ImageField::create('image')
                            ->label('Category Image')
                            ->required()
                            ->columnSpanFull(),
                    ]),
                ])
                ->minItems(1)
                ->maxItems(8)
                ->defaultItems(4)
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected static function getViewPath(): string
    {
        return 'content-blocks.categories-grid-block';
    }
}
