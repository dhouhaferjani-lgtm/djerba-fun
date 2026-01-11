<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TravelTip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TravelTipController extends Controller
{
    /**
     * Get all active travel tips.
     */
    public function index(Request $request): JsonResponse
    {
        $locale = $request->header('Accept-Language', 'en');

        // Normalize locale (e.g., "en-US" -> "en")
        $locale = substr($locale, 0, 2);
        if (!in_array($locale, ['en', 'fr'])) {
            $locale = 'en';
        }

        $tips = TravelTip::active()
            ->ordered()
            ->get()
            ->map(function ($tip) use ($locale) {
                return [
                    'id' => $tip->id,
                    'content' => $tip->getTranslation('content', $locale, false)
                        ?? $tip->getTranslation('content', 'en'),
                ];
            });

        return response()->json([
            'data' => $tips,
            'locale' => $locale,
        ]);
    }
}
