<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\ListingResource\RelationManagers;

use App\Enums\MediaCategory;
use App\Models\Media;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = 'Images & Media';

    protected static ?string $icon = 'heroicon-o-photo';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('url')
                    ->label('Image')
                    ->image()
                    ->required()
                    ->disk('public')
                    ->directory('listings/media')
                    ->maxSize(10240) // 10MB
                    ->imageResizeMode('contain')
                    ->imageResizeTargetWidth('1920')
                    ->imageResizeTargetHeight('1080')
                    ->imageCropAspectRatio(null) // Allow any aspect ratio
                    ->helperText('Recommended: 1920x1080px or similar, max 10MB')
                    ->getUploadedFileUrlUsing(function ($file) {
                        if ($file instanceof TemporaryUploadedFile) {
                            return $file->temporaryUrl();
                        }

                        return route('admin.storage.proxy', ['path' => 'listings/media/' . $file]);
                    })
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('alt')
                    ->label('Alt Text (for accessibility)')
                    ->maxLength(255)
                    ->helperText('Describe the image for screen readers and SEO')
                    ->columnSpanFull(),

                Forms\Components\Select::make('category')
                    ->label('Image Type')
                    ->options([
                        MediaCategory::HERO->value => MediaCategory::HERO->label() . ' (Main cover image)',
                        MediaCategory::GALLERY->value => MediaCategory::GALLERY->label() . ' (Additional photos)',
                        MediaCategory::FEATURED->value => MediaCategory::FEATURED->label() . ' (Highlighted images)',
                    ])
                    ->default(MediaCategory::GALLERY->value)
                    ->required()
                    ->helperText('Hero image appears as the main cover. Only one hero image is recommended.'),

                Forms\Components\TextInput::make('order')
                    ->label('Display Order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->helperText('Lower numbers appear first'),

                Forms\Components\Hidden::make('type')
                    ->default('image'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('url')
                    ->label('Preview')
                    ->disk('public')
                    ->height(80)
                    ->width(120),

                Tables\Columns\TextColumn::make('alt')
                    ->label('Alt Text')
                    ->limit(40)
                    ->placeholder('No alt text'),

                Tables\Columns\TextColumn::make('category')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label())
                    ->color(fn (MediaCategory $state): string => match ($state) {
                        MediaCategory::HERO => 'primary',
                        MediaCategory::GALLERY => 'gray',
                        MediaCategory::FEATURED => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->reorderable('order')
            ->defaultSort('order')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Image Type')
                    ->options([
                        MediaCategory::HERO->value => MediaCategory::HERO->label(),
                        MediaCategory::GALLERY->value => MediaCategory::GALLERY->label(),
                        MediaCategory::FEATURED->value => MediaCategory::FEATURED->label(),
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Image')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['type'] = 'image';
                        $data['alt'] = $data['alt'] ?? '';

                        return $data;
                    })
                    ->after(function () {
                        Notification::make()
                            ->title('Image added successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit'),

                Tables\Actions\Action::make('set_as_hero')
                    ->label('Set as Hero')
                    ->icon('heroicon-o-star')
                    ->color('primary')
                    ->visible(fn (Media $record) => $record->category !== MediaCategory::HERO)
                    ->requiresConfirmation()
                    ->modalHeading('Set as Hero Image')
                    ->modalDescription('This will make this image the main cover image. Other hero images will be changed to gallery images.')
                    ->action(function (Media $record) {
                        // Change all existing hero images for this listing to gallery
                        $this->getOwnerRecord()->media()
                            ->where('category', MediaCategory::HERO)
                            ->update(['category' => MediaCategory::GALLERY]);

                        // Set this image as hero
                        $record->update([
                            'category' => MediaCategory::HERO,
                            'order' => 0, // Hero should always be first
                        ]);

                        Notification::make()
                            ->title('Hero image updated')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Remove'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Remove Selected'),
                ]),
            ])
            ->emptyStateHeading('No images uploaded')
            ->emptyStateDescription('Upload images to showcase your listing. The hero image will be used as the main cover.')
            ->emptyStateIcon('heroicon-o-photo')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Upload your first image')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['type'] = 'image';
                        $data['alt'] = $data['alt'] ?? '';

                        // First image should be hero by default
                        if ($this->getOwnerRecord()->media()->count() === 0) {
                            $data['category'] = MediaCategory::HERO->value;
                        }

                        return $data;
                    }),
            ]);
    }
}
