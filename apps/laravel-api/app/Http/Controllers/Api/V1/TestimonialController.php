<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TestimonialResource;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TestimonialController extends Controller
{
    /**
     * Get all active testimonials.
     *
     * Returns testimonials ordered by sort_order for homepage display.
     * Supports locale via Accept-Language header (fr default, en).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = (int) $request->query('limit', 10);
        $limit = min(max($limit, 1), 20); // Clamp between 1 and 20

        $testimonials = Testimonial::active()
            ->ordered()
            ->limit($limit)
            ->get();

        return TestimonialResource::collection($testimonials);
    }

    /**
     * Get a single testimonial by UUID.
     */
    public function show(Request $request, Testimonial $testimonial): TestimonialResource
    {
        // Only return active testimonials via public API
        if (! $testimonial->is_active) {
            abort(404);
        }

        return new TestimonialResource($testimonial);
    }
}
