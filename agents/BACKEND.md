# Backend Agent Instructions

> **Model**: Claude Sonnet 4.5
> **Scope**: Laravel 12 API, Filament 3 Admin, all server-side logic
> **Reports to**: Orchestrator (Opus 4.5)

---

## 🎯 Your Responsibilities

1. Laravel application code (models, controllers, services, jobs)
2. Database migrations and seeders
3. Filament admin panels (Vendor + Admin)
4. API endpoints under `/api/v1/`
5. Authentication and authorization (Sanctum + Policies)
6. Queue jobs and event listeners
7. Pest/PHPUnit tests

---

## 📁 Directory Structure

```
apps/laravel-api/
├── app/
│   ├── Domain/
│   │   ├── Bookings/
│   │   │   ├── Actions/
│   │   │   ├── Data/
│   │   │   ├── Events/
│   │   │   └── Jobs/
│   │   ├── Listings/
│   │   ├── Payments/
│   │   └── Users/
│   ├── Enums/
│   ├── Filament/
│   │   ├── Admin/
│   │   │   └── Resources/
│   │   └── Vendor/
│   │       └── Resources/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── V1/
│   │   │       └── Agent/
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Models/
│   ├── Observers/
│   ├── Policies/
│   └── Services/
│       └── Payments/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── routes/
│   ├── api.php
│   ├── web.php
│   └── channels.php
└── tests/
    ├── Feature/
    └── Unit/
```

---

## 🔧 Code Patterns

### Controllers (Thin)

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Bookings\Actions\CreateBooking;
use App\Http\Requests\CreateBookingRequest;
use App\Http\Resources\BookingResource;

class BookingController extends Controller
{
    public function store(
        CreateBookingRequest $request,
        CreateBooking $action
    ): BookingResource {
        $booking = $action->handle(
            $request->user(),
            $request->toData()
        );

        return BookingResource::make($booking);
    }
}
```

### FormRequests (Always Use)

```php
<?php

namespace App\Http\Requests;

use App\Domain\Bookings\Data\CreateBookingData;
use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Booking::class);
    }

    public function rules(): array
    {
        return [
            'holdId' => ['required', 'string', 'exists:booking_holds,id'],
            'travelers' => ['required', 'array', 'min:1'],
            'travelers.*.firstName' => ['required', 'string', 'max:100'],
            'travelers.*.lastName' => ['required', 'string', 'max:100'],
            'travelers.*.email' => ['required', 'email'],
            'couponCode' => ['nullable', 'string'],
        ];
    }

    public function toData(): CreateBookingData
    {
        return CreateBookingData::from($this->validated());
    }
}
```

### Actions (Business Logic)

```php
<?php

namespace App\Domain\Bookings\Actions;

use App\Domain\Bookings\Data\CreateBookingData;
use App\Domain\Bookings\Events\BookingCreated;
use App\Models\Booking;
use App\Models\User;
use App\Services\Payments\PaymentIntentFactory;
use Illuminate\Support\Facades\DB;

class CreateBooking
{
    public function __construct(
        private HoldService $holds,
        private PaymentIntentFactory $payments,
    ) {}

    public function handle(User $user, CreateBookingData $data): Booking
    {
        return DB::transaction(function () use ($user, $data) {
            // Validate hold is still valid
            $hold = $this->holds->consume($data->holdId);

            // Create booking
            $booking = Booking::create([
                'code' => Booking::generateCode(),
                'listing_id' => $hold->listing_id,
                'traveler_id' => $user->id,
                'status' => BookingStatus::PaymentPending,
                'starts_at' => $hold->starts_at,
                'ends_at' => $hold->ends_at,
                'guests' => $hold->guests,
                'total_amount' => $hold->calculated_total,
                'currency' => $hold->currency,
            ]);

            // Create travelers
            foreach ($data->travelers as $traveler) {
                $booking->travelers()->create($traveler);
            }

            // Create payment intent
            $this->payments->createForBooking($booking);

            // Dispatch event
            event(new BookingCreated($booking));

            return $booking->fresh(['paymentIntent', 'travelers']);
        });
    }
}
```

### Resources (JSON Serialization)

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status->value,
            'listing' => ListingResource::make($this->whenLoaded('listing')),
            'startsAt' => $this->starts_at->toIso8601String(),
            'endsAt' => $this->ends_at->toIso8601String(),
            'guests' => $this->guests,
            'total' => $this->total_amount,
            'currency' => $this->currency,
            'travelers' => TravelerResource::collection($this->whenLoaded('travelers')),
            'paymentIntent' => PaymentIntentResource::make($this->whenLoaded('paymentIntent')),
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
```

### Policies (Authorization)

```php
<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->traveler_id
            || $user->isVendorOf($booking->listing)
            || $user->isAdmin();
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->isVendorOf($booking->listing)
            || $user->isAdmin();
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $user->id === $booking->traveler_id
            && $booking->isCancellable();
    }
}
```

### Enums (Status Values)

```php
<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Draft = 'draft';
    case PaymentPending = 'payment_pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::Draft => 'Draft',
            self::PaymentPending => 'Awaiting Payment',
            self::Confirmed => 'Confirmed',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }
}
```

---

## 💳 Payment Gateway Abstraction

### Interface

```php
<?php

namespace App\Services\Payments\Contracts;

use App\Models\PaymentIntent;

interface PaymentGateway
{
    public function createIntent(int $amount, string $currency, array $metadata = []): PaymentIntent;

    public function captureIntent(PaymentIntent $intent): PaymentIntent;

    public function refundIntent(PaymentIntent $intent, ?int $amount = null): PaymentIntent;

    public function handleWebhook(array $payload, string $signature): void;

    public function getClientSecret(PaymentIntent $intent): ?string;
}
```

### Mock Implementation (for MVP)

```php
<?php

namespace App\Services\Payments\Gateways;

use App\Services\Payments\Contracts\PaymentGateway;

class MockPaymentGateway implements PaymentGateway
{
    public function createIntent(int $amount, string $currency, array $metadata = []): PaymentIntent
    {
        return PaymentIntent::create([
            'gateway' => 'mock',
            'amount' => $amount,
            'currency' => $currency,
            'status' => PaymentStatus::Pending,
            'provider_ref' => 'mock_' . Str::random(24),
            'metadata' => $metadata,
        ]);
    }

    // ... implement other methods
}
```

### Service Provider Registration

```php
<?php

namespace App\Providers;

use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\Gateways\MockPaymentGateway;
use App\Services\Payments\Gateways\OfflinePaymentGateway;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, function ($app) {
            return match(config('payments.default')) {
                'mock' => new MockPaymentGateway(),
                'offline' => new OfflinePaymentGateway(),
                // Future: 'stripe' => new StripePaymentGateway(...),
                default => throw new \InvalidArgumentException('Unknown payment gateway'),
            };
        });
    }
}
```

---

## 🗺️ Map Data Models

### Location Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Location extends Model
{
    use HasSpatial;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'coordinates',
        'address',
        'city',
        'country',
        'timezone',
    ];

    protected $casts = [
        'coordinates' => Point::class,
    ];
}
```

### Itinerary Stop Model

```php
<?php

namespace App\Models;

class ItineraryStop extends Model
{
    protected $fillable = [
        'listing_id',
        'location_id',
        'order',
        'title',
        'description',
        'duration_minutes',
        'stop_type', // 'start', 'waypoint', 'highlight', 'end'
        'elevation_meters',
        'coordinates',
    ];

    protected $casts = [
        'coordinates' => Point::class,
        'elevation_meters' => 'integer',
    ];
}
```

### Elevation Profile (for trails)

```php
<?php

namespace App\Models;

class ElevationProfile extends Model
{
    protected $fillable = [
        'listing_id',
        'points', // JSON array of {distance, elevation} pairs
        'total_ascent',
        'total_descent',
        'max_elevation',
        'min_elevation',
    ];

    protected $casts = [
        'points' => 'array',
    ];
}
```

---

## 🌍 Localization

### Translatable Fields

```php
<?php

namespace App\Models;

use Spatie\Translatable\HasTranslations;

class Listing extends Model
{
    use HasTranslations;

    public $translatable = [
        'title',
        'summary',
        'description',
        'highlights',
        'included',
        'not_included',
    ];
}
```

### Usage

```php
// Setting translations
$listing->setTranslation('title', 'en', 'Atlas Mountain Hike');
$listing->setTranslation('title', 'fr', 'Randonnée dans l\'Atlas');

// Getting current locale
$listing->title; // Uses app()->getLocale()

// Getting specific
$listing->getTranslation('title', 'fr');
```

---

## 🧪 Testing

### Feature Test Example

```php
<?php

use App\Models\User;
use App\Models\BookingHold;
use App\Models\Listing;

it('creates booking from valid hold', function () {
    $user = User::factory()->traveler()->create();
    $listing = Listing::factory()->published()->create();
    $hold = BookingHold::factory()
        ->for($listing)
        ->for($user)
        ->create();

    actingAs($user)
        ->postJson('/api/v1/bookings', [
            'holdId' => $hold->id,
            'travelers' => [
                ['firstName' => 'John', 'lastName' => 'Doe', 'email' => 'john@example.com'],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'payment_pending')
        ->assertJsonPath('data.code', fn($code) => str_starts_with($code, 'GA-'));
});

it('rejects expired hold', function () {
    $user = User::factory()->traveler()->create();
    $hold = BookingHold::factory()
        ->for($user)
        ->expired()
        ->create();

    actingAs($user)
        ->postJson('/api/v1/bookings', [
            'holdId' => $hold->id,
            'travelers' => [['firstName' => 'John', 'lastName' => 'Doe', 'email' => 'john@test.com']],
        ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'HOLD_EXPIRED');
});
```

---

## ✅ Checklist Before Completion

For each task, ensure:

- [ ] Migration created with proper indexes
- [ ] Model with fillable, casts, relationships
- [ ] Factory for testing
- [ ] Policy registered
- [ ] FormRequest for validation
- [ ] JsonResource for serialization
- [ ] Controller using Action pattern
- [ ] Routes defined in api.php
- [ ] Pest tests written and passing
- [ ] Filament Resource if applicable

---

## 🚫 What NOT To Do

1. **Never skip FormRequests** - always validate input
2. **Never return Eloquent models directly** - always use Resources
3. **Never put business logic in controllers** - use Actions
4. **Never hardcode config values** - use config() or env()
5. **Never skip policies** - always authorize
6. **Never block in controllers** - queue heavy tasks
7. **Never store non-UTC times** - always UTC, convert in Resources
8. **Never define types locally** - always reference packages/schemas
