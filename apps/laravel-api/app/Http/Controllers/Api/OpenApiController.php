<?php

namespace App\Http\Controllers\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Go Adventure API",
 *     version="1.0.0",
 *     description="API documentation for Go Adventure marketplace - tourism booking platform",
 *
 *     @OA\Contact(
 *         email="api@goadventure.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="token",
 *     description="Sanctum token authentication for users"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="agent",
 *     type="apiKey",
 *     in="header",
 *     name="X-Agent-Key",
 *     description="Agent API key authentication (requires both X-Agent-Key and X-Agent-Secret headers)"
 * )
 *
 * @OA\Tag(
 *     name="Health",
 *     description="API health check endpoints"
 * )
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication endpoints"
 * )
 * @OA\Tag(
 *     name="Listings",
 *     description="Browse and search listings (tours and events)"
 * )
 * @OA\Tag(
 *     name="Bookings",
 *     description="Booking management endpoints"
 * )
 * @OA\Tag(
 *     name="Agent API",
 *     description="Endpoints for AI agents and partner integrations"
 * )
 * @OA\Tag(
 *     name="Feeds",
 *     description="Public product feeds for partners"
 * )
 */
class OpenApiController
{
    // This controller serves only as a container for OpenAPI annotations
}
