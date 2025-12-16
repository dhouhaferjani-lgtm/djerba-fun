<?php

namespace App\ContentBlocks;

use Closure;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Spatie\MediaLibrary\HasMedia;
use Statikbe\FilamentFlexibleContentBlocks\ContentBlocks\AbstractFilamentFlexibleContentBlock;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Form\Fields\Blocks\BlockStyleField;
use Statikbe\FilamentFlexibleContentBlocks\Models\Contracts\HasContentBlocks;

class ToursListingBlock extends AbstractFilamentFlexibleContentBlock
{
    public ?string $listingType = null;

    public ?int $count = 6;

    public ?string $sortBy = 'created_at';

    public ?string $style = 'grid';

    public function __construct(HasContentBlocks&HasMedia $record, ?array $blockData)
    {
        parent::__construct($record, $blockData);

        $this->listingType = $blockData['listing_type'] ?? 'all';
        $this->count = $blockData['count'] ?? 6;
        $this->sortBy = $blockData['sort_by'] ?? 'created_at';
        $this->style = $blockData['style'] ?? 'grid';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-map';
    }

    public static function getNameSuffix(): string
    {
        return 'tours-listing';
    }

    /**
     * Get the Filament form fields for the Filament editor.
     */
    protected static function makeFilamentSchema(): array|Closure
    {
        return [
            Grid::make(2)->schema([
                Select::make('listing_type')
                    ->label('Listing Type')
                    ->options([
                        'all' => 'All Listings',
                        'tour' => 'Tours Only',
                        'event' => 'Events Only',
                    ])
                    ->default('all')
                    ->required(),

                TextInput::make('count')
                    ->label('Number of Items')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(12)
                    ->default(6)
                    ->required(),

                Select::make('sort_by')
                    ->label('Sort By')
                    ->options([
                        'created_at' => 'Newest First',
                        'title' => 'Title (A-Z)',
                        'price' => 'Price (Low to High)',
                        '-price' => 'Price (High to Low)',
                    ])
                    ->default('created_at')
                    ->required(),

                BlockStyleField::create(
                    name: 'style',
                    label: 'Display Style',
                    styles: [
                        'grid' => 'Grid',
                        'carousel' => 'Carousel',
                        'list' => 'List',
                    ],
                    default: 'grid',
                ),
            ]),
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected static function getViewPath(): string
    {
        return 'content-blocks.tours-listing-block';
    }
}
