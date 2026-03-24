<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Display a listing of published pages.
     */
    public function index(Request $request)
    {
        $locale = $request->input('locale', 'en');

        $pages = Page::query()
            ->where(function ($query) {
                $query->whereNull('publishing_begins_at')
                    ->orWhere('publishing_begins_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('publishing_ends_at')
                    ->orWhere('publishing_ends_at', '>=', now());
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return PageResource::collection($pages);
    }

    /**
     * Display the specified page by slug.
     */
    public function show(Request $request, string $slug)
    {
        $locale = $request->input('locale', 'en');

        $page = Page::query()
            ->where("slug->{$locale}", $slug)
            ->where(function ($query) {
                $query->whereNull('publishing_begins_at')
                    ->orWhere('publishing_begins_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('publishing_ends_at')
                    ->orWhere('publishing_ends_at', '>=', now());
            })
            ->firstOrFail();

        return new PageResource($page);
    }

    /**
     * Display the specified page by code (for special pages like HOME).
     */
    public function showByCode(Request $request, string $code)
    {
        $locale = $request->input('locale', 'en');

        $page = Page::query()
            ->where('code', $code)
            ->where(function ($query) {
                $query->whereNull('publishing_begins_at')
                    ->orWhere('publishing_begins_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('publishing_ends_at')
                    ->orWhere('publishing_ends_at', '>=', now());
            })
            ->firstOrFail();

        return new PageResource($page);
    }

    /**
     * Get menus by code.
     */
    public function getMenu(Request $request, string $menuCode)
    {
        $locale = $request->input('locale', 'en');

        $menu = \Statikbe\FilamentFlexibleContentBlockPages\Models\Menu::query()
            ->where('code', $menuCode)
            ->with(['menuItems' => function ($query) {
                $query->orderBy('order');
            }])
            ->firstOrFail();

        return response()->json([
            'code' => $menu->code,
            'name' => $menu->name,
            'items' => $menu->menuItems->map(function ($item) use ($locale) {
                return [
                    'id' => $item->id,
                    'label' => $item->getTranslation('label', $locale),
                    'url' => $item->getTranslation('url', $locale),
                    'target' => $item->target,
                    'order' => $item->order,
                    'parent_id' => $item->parent_id,
                ];
            }),
        ]);
    }
}
