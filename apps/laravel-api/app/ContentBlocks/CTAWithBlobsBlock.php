<?php

namespace App\ContentBlocks;

use Closure;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Spatie\MediaLibrary\HasMedia;
use Statikbe\FilamentFlexibleContentBlocks\ContentBlocks\AbstractFilamentFlexibleContentBlock;
use Statikbe\FilamentFlexibleContentBlocks\Models\Contracts\HasContentBlocks;

class CTAWithBlobsBlock extends AbstractFilamentFlexibleContentBlock
{
    public ?string $title = null;

    public ?string $text = null;

    public ?string $buttonLabel = null;

    public ?string $buttonUrl = null;

    public ?string $buttonVariant = 'secondary';

    public function __construct(HasContentBlocks&HasMedia $record, ?array $blockData)
    {
        parent::__construct($record, $blockData);

        $this->title = $blockData['title'] ?? null;
        $this->text = $blockData['text'] ?? null;
        $this->buttonLabel = $blockData['button_label'] ?? null;
        $this->buttonUrl = $blockData['button_url'] ?? null;
        $this->buttonVariant = $blockData['button_variant'] ?? 'secondary';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-megaphone';
    }

    public static function getNameSuffix(): string
    {
        return 'cta-with-blobs';
    }

    /**
     * Get the Filament form fields for the Filament editor.
     */
    protected static function makeFilamentSchema(): array|Closure
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),

                Textarea::make('text')
                    ->label('Text')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull(),

                TextInput::make('button_label')
                    ->label('Button Label')
                    ->required()
                    ->maxLength(50),

                TextInput::make('button_url')
                    ->label('Button URL')
                    ->required()
                    ->url(),

                Select::make('button_variant')
                    ->label('Button Style')
                    ->options([
                        'primary' => 'Primary (Dark Green)',
                        'secondary' => 'Secondary (Light Green)',
                        'white' => 'White',
                    ])
                    ->default('secondary')
                    ->required(),
            ]),
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected static function getViewPath(): string
    {
        return 'content-blocks.cta-with-blobs-block';
    }
}
