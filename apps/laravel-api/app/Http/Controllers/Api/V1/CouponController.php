<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidateCouponRequest;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;

class CouponController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService
    ) {}

    /**
     * Validate a coupon code.
     */
    public function validate(ValidateCouponRequest $request): JsonResponse
    {
        $result = $this->couponService->validate(
            code: $request->validated('code'),
            listingId: $request->validated('listing_id'),
            amount: (float) $request->validated('amount'),
            userId: $request->user()?->id
        );

        return response()->json($result, $result['valid'] ? 200 : 422);
    }
}
