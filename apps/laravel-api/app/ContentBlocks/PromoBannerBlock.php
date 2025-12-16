<?php

namespace App\ContentBlocks;

use Closure;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Spatie\MediaLibrary\HasMedia;
use Statikbe\FilamentFlexibleContentBlocks\ContentBlocks\AbstractFilamentFlexibleContentBlock;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Form\Fields\Blocks\BackgroundColourField;
use Statikbe\FilamentFlexibleContentBlocks\Models\Contracts\HasContentBlocks;

class PromoBannerBlock extends AbstractFilamentFlexibleContentBlock
{
    public ?string $title = null;

    public ?string $subtitle = null;

    public ?string $tag = null;

    public ?string $primaryButtonLabel = null;

    public ?string $primaryButtonUrl = null;

    public ?string $secondaryButtonLabel = null;

    public ?string $secondaryButtonUrl = null;

    public ?string $backgroundColour = 'primary';

    public function __construct(HasContentBlocks&HasMedia $record, ?array $blockData)
    {
        parent::__construct($record, $blockData);

        $this->title = $blockData['title'] ?? null;
        $this->subtitle = $blockData['subtitle'] ?? null;
        $this->tag = $blockData['tag'] ?? null;
        $this->primaryButtonLabel = $blockData['primary_button_label'] ?? null;
        $this->primaryButtonUrl = $blockData['primary_button_url'] ?? null;
        $this->secondaryButtonLabel = $blockData['secondary_button_label'] ?? null;
        $this->secondaryButtonUrl = $blockData['secondary_button_url'] ?? null;
        $this->backgroundColour = $blockData['background_colour'] ?? 'primary';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-megaphone';
    }

    public static function getNameSuffix(): string
    {
        return 'promo-banner';
    }

    /**
     * Get the Filament form fields for the Filament editor.
     */
    protected static function makeFilamentSchema(): array|Closure
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('tag')
                    ->label('Tag/Badge Text')
                    ->placeholder('Limited Time Offer')
                    ->maxLength(50),

                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(200),

                Textarea::make('subtitle')
                    ->label('Subtitle')
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull(),

                TextInput::make('primary_button_label')
                    ->label('Primary Button Label')
                    ->placeholder('Learn More'),

                TextInput::make('primary_button_url')
                    ->label('Primary Button URL')
                    ->url(),

                TextInput::make('secondary_button_label')
                    ->label('Secondary Button Label')
                    ->placeholder('View All'),

                TextInput::make('secondary_button_url')
                    ->label('Secondary Button URL')
                    ->url(),

                BackgroundColourField::create(
                    label: 'Background Color',
                    colours: [
                        'primary' => 'Primary (Dark Green)',
                        'secondary' => 'Secondary (Light Green)',
                        'accent' => 'Accent (Cream)',
                        'dark' => 'Dark',
                    ],
                    default: 'primary',
                ),
            ]),
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected static function getViewPath(): string
    {
        return 'content-blocks.promo-banner-block';
    }
}
