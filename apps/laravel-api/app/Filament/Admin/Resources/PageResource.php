<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PageResource\Pages;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Statikbe\FilamentFlexibleContentBlockPages\Actions\LinkedToMenuItemBulkDeleteAction;
use Statikbe\FilamentFlexibleContentBlockPages\Facades\FilamentFlexibleContentBlockPages;
use Statikbe\FilamentFlexibleContentBlockPages\Form\Components\UndeletableToggle;
use Statikbe\FilamentFlexibleContentBlockPages\Models\Page;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Form\Actions\CopyContentBlocksToLocalesAction;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Form\Fields\AuthorField;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Form\Fields\CodeField;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Form\Fields\ContentBlocksField;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Form\Fields\Groups\HeroCallToActionSection;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Form\Fields\Groups\HeroImageSection;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Form\Fields\Groups\OverviewFields;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Form\Fields\Groups\PublicationSection;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Form\Fields\Groups\SEOFields;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Form\Fields\IntroField;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Form\Fields\SlugField;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Form\Fields\TitleField;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Table\Actions\PublishAction;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Table\Actions\ReplicateAction;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Table\Actions\ViewAction;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Table\Columns\PublishedColumn;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Table\Columns\TitleColumn;
use Statikbe\FilamentFlexibleContentBlocks\Filament\Table\Filters\PublishedFilter;
use Statikbe\FilamentFlexibleContentBlocks\FilamentFlexibleBlocksConfig;

/**
 * Custom PageResource that extends the vendor's PageResource with draft support.
 */
class PageResource extends Resource
{
    use Translatable;

    protected static ?string $navigationIcon = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.pages');
    }

    protected static ?string $recordRouteKeyName = 'id';

    protected static ?string $recordTitleAttribute = 'title';

    protected static int $globalSearchResultsLimit = 10;

    protected static ?bool $isGlobalSearchForcedCaseInsensitive = true;

    public static function getModel(): string
    {
        return FilamentFlexibleContentBlockPages::config()->getPageModel()::class;
    }

    public static function getLabel(): ?string
    {
        return __('filament.page.page');
    }

    public static function getPluralLabel(): ?string
    {
        return __('filament.page.pages');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['parent']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make(__('filament.page.page'))
                    ->columnSpan(2)
                    ->tabs([
                        Tab::make(__('filament.page.general'))
                            ->icon('heroicon-m-globe-alt')
                            ->schema(static::getGeneralTabFields()),
                        Tab::make(__('filament.page.content'))
                            ->icon('heroicon-o-rectangle-group')
                            ->schema(static::getContentTabFields()),
                        Tab::make(__('filament.page.overview'))
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema(static::getOverviewTabFields()),
                        Tab::make(__('filament.page.seo'))
                            ->icon('heroicon-o-globe-alt')
                            ->schema(static::getSEOTabFields()),
                        Tab::make(__('filament.page.advanced'))
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema(static::getAdvancedTabFields()),
                    ])
                    ->persistTabInQueryString(),
            ]);
    }

    protected static function getGeneralTabFields(): array
    {
        $fields = [
            // Title is NOT required for drafts - just recommended
            TitleField::create(true)
                ->required(false)
                ->helperText(__('filament.page.required_for_publishing')),
            IntroField::create(),
            // Hero image is optional
            HeroImageSection::create(true),
        ];

        if (FilamentFlexibleContentBlockPages::config()->isHeroCallToActionsEnabled(static::getModel())) {
            $fields[] = HeroCallToActionSection::create();
        }

        return $fields;
    }

    protected static function getContentTabFields(): array
    {
        return [
            CopyContentBlocksToLocalesAction::create(),
            ContentBlocksField::create(),
        ];
    }

    protected static function getSEOTabFields(): array
    {
        return [
            SEOFields::create(1, true),
        ];
    }

    protected static function getOverviewTabFields(): array
    {
        return [
            OverviewFields::create(1, true),
        ];
    }

    protected static function getAdvancedTabFields(): array
    {
        $config = FilamentFlexibleContentBlockPages::config();
        $modelClass = static::getModel();

        $fields = [
            PublicationSection::create(),
            CodeField::create(),
            SlugField::create(false),
        ];

        $gridFields = [];

        if ($config->isAuthorEnabled($modelClass)) {
            $gridFields[] = AuthorField::create();
        }

        if ($config->isUndeletableEnabled($modelClass)) {
            $gridFields[] = UndeletableToggle::create();
        }

        if (! empty($gridFields)) {
            $fields[] = Grid::make()->schema($gridFields);
        }

        return $fields;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TitleColumn::create()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('filament.page.created'))
                    ->dateTime(FilamentFlexibleBlocksConfig::getPublishingDateFormatting())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('filament.page.updated'))
                    ->dateTime(FilamentFlexibleBlocksConfig::getPublishingDateFormatting())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('code')
                    ->label(__('filament.page.code'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                PublishedColumn::create()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                PublishedFilter::create(),
            ])
            ->actions([
                EditAction::make(),
                PublishAction::make(),
                ViewAction::make(),
                ReplicateAction::make()
                    ->visible(FilamentFlexibleContentBlockPages::config()->isReplicateActionOnTableEnabled(static::getModel()))
                    ->successRedirectUrl(fn (ReplicateAction $action) => PageResource::getUrl('edit', ['record' => $action->getReplica()])),
            ])
            ->bulkActions([
                LinkedToMenuItemBulkDeleteAction::make(),
            ])
            ->recordUrl(
                fn ($record): string => static::getUrl('edit', ['record' => $record])
            )
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['menuItem']);
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record:id}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'title',
            'intro',
            'content_blocks',
            'seo_title',
            'seo_description',
            'seo_keywords',
            'overview_title',
            'overview_description',
            'code',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return method_exists($record, 'getTranslation')
            ? $record->getTranslation('title', app()->getLocale())
            : $record->getAttribute('title');
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Page $record */
        $published = __('filament.page.draft');

        if ($record->isPublished()) {
            $published = __('filament.page.published');
        }

        return [
            __('filament.page.intro') => Str::limit(strip_tags($record->intro ?? ''), 50),
            __('filament.page.status') => $published,
        ];
    }
}
