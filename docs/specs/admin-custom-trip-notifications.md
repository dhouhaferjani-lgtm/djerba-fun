# Admin Custom Trip Request Notifications - BDD Specification

## Overview

When a traveler submits a custom trip request, the admin should:

1. Receive a real-time notification in the Filament admin panel
2. See the request in a dedicated "Custom Trip Requests" resource tab
3. Be able to view full request details and manage the request status

---

## Feature: Admin Notification for Custom Trip Requests

### Background

```gherkin
Given the admin panel is running at /admin
And there is at least one admin user in the system
And the custom_trip_requests table exists with the migration already run
```

---

## Scenario 1: Admin receives notification when new request is submitted

```gherkin
Feature: Real-time Admin Notifications
  As an admin
  I want to be notified when a new custom trip request arrives
  So that I can respond quickly to potential customers

  Scenario: New custom trip request triggers admin notification
    Given I am logged in as an admin
    And I am viewing the admin dashboard
    When a traveler submits a custom trip request with:
      | Field           | Value                    |
      | name            | John Doe                 |
      | email           | john@example.com         |
      | travel_dates    | 2026-03-15 to 2026-03-22 |
      | adults          | 2                        |
      | budget          | 5000 TND                 |
    Then I should see a notification badge appear
    And the notification should show "New Custom Trip Request"
    And the notification should include the traveler's name "John Doe"

  Scenario: Clicking notification navigates to request details
    Given I have received a notification for a new custom trip request
    When I click on the notification
    Then I should be redirected to the custom trip request details page
    And I should see all the request information
```

---

## Scenario 2: Custom Trip Requests Resource in Admin Panel

```gherkin
Feature: Custom Trip Requests Admin Resource
  As an admin
  I want a dedicated section to manage custom trip requests
  So that I can track and respond to all inquiries

  Scenario: Admin sees Custom Trip Requests in navigation
    Given I am logged in as an admin
    When I view the admin sidebar
    Then I should see "Custom Trip Requests" menu item
    And it should show a badge with the count of pending requests

  Scenario: Admin views list of custom trip requests
    Given there are 5 custom trip requests in the system
    When I navigate to "Custom Trip Requests"
    Then I should see a table with columns:
      | Column          |
      | Reference       |
      | Traveler Name   |
      | Email           |
      | Travel Dates    |
      | Budget          |
      | Status          |
      | Created At      |
    And requests should be sorted by newest first

  Scenario: Admin filters requests by status
    Given there are requests with different statuses
    When I filter by status "pending"
    Then I should only see requests with status "pending"
```

---

## Scenario 3: Custom Trip Request Detail View

```gherkin
Feature: Custom Trip Request Details
  As an admin
  I want to view full details of a custom trip request
  So that I can prepare a personalized response

  Scenario: Admin views request details
    Given a custom trip request exists with reference "CTR-2026-ABC123"
    When I click "View" on that request
    Then I should see the request details page with sections:
      | Section              | Fields                                    |
      | Contact Information  | Name, Email, Phone, WhatsApp, Country     |
      | Trip Details         | Travel Dates, Duration, Flexible Dates    |
      | Travelers            | Adults, Children                          |
      | Interests            | Selected interests as tags                |
      | Budget & Style       | Budget per person, Accommodation, Pace    |
      | Special Requests     | Free text notes                           |
      | Special Occasions    | Selected occasions                        |

  Scenario: Admin updates request status
    Given I am viewing a custom trip request with status "pending"
    When I change the status to "contacted"
    Then the status should be updated to "contacted"
    And I should see a success notification

  Scenario: Admin adds internal notes
    Given I am viewing a custom trip request
    When I add an internal note "Called customer, will send proposal tomorrow"
    Then the note should be saved with timestamp
    And I should see the note in the activity log
```

---

## Scenario 4: Request Status Workflow

```gherkin
Feature: Custom Trip Request Status Management
  As an admin
  I want to track the status of each request
  So that I can manage the sales pipeline

  Background:
    Given the following statuses exist:
      | Status      | Color   | Description                    |
      | pending     | warning | New request, not yet reviewed  |
      | contacted   | info    | Admin has contacted traveler   |
      | proposal    | primary | Proposal sent to traveler      |
      | confirmed   | success | Traveler confirmed the trip    |
      | cancelled   | danger  | Request cancelled              |
      | completed   | success | Trip completed                 |

  Scenario: Status transitions
    Given a request with status "pending"
    Then the available status transitions should be:
      | From      | To Available                              |
      | pending   | contacted, cancelled                      |
      | contacted | proposal, cancelled                       |
      | proposal  | confirmed, contacted, cancelled           |
      | confirmed | completed, cancelled                      |
```

---

## Implementation Plan

### Backend (Laravel/Filament)

#### 1. Create CustomTripRequestResource for Filament Admin

**File:** `apps/laravel-api/app/Filament/Admin/Resources/CustomTripRequestResource.php`

```php
<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CustomTripRequestResource\Pages;
use App\Models\CustomTripRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class CustomTripRequestResource extends Resource
{
    protected static ?string $model = CustomTripRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // ... form, table, infolist definitions
}
```

#### 2. Send Notification on Request Creation

**Update:** `apps/laravel-api/app/Http/Controllers/Api/V1/CustomTripRequestController.php`

```php
// After saving the request, send notification to all admins
$admins = User::where('role', UserRole::ADMIN)->get();

foreach ($admins as $admin) {
    Notification::make()
        ->title('New Custom Trip Request')
        ->icon('heroicon-o-paper-airplane')
        ->body("New request from {$request->contact['name']} - {$request->reference}")
        ->actions([
            \Filament\Notifications\Actions\Action::make('view')
                ->label('View Request')
                ->url("/admin/custom-trip-requests/{$customTripRequest->id}")
                ->button(),
        ])
        ->sendToDatabase($admin);
}
```

#### 3. Add Status Enum

**File:** `apps/laravel-api/app/Enums/CustomTripRequestStatus.php`

```php
<?php

namespace App\Enums;

enum CustomTripRequestStatus: string
{
    case PENDING = 'pending';
    case CONTACTED = 'contacted';
    case PROPOSAL = 'proposal';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::CONTACTED => 'Contacted',
            self::PROPOSAL => 'Proposal Sent',
            self::CONFIRMED => 'Confirmed',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::CONTACTED => 'info',
            self::PROPOSAL => 'primary',
            self::CONFIRMED => 'success',
            self::CANCELLED => 'danger',
            self::COMPLETED => 'success',
        };
    }
}
```

#### 4. Update CustomTripRequest Model

Add status casting and relationship for admin notes:

```php
protected $casts = [
    'status' => CustomTripRequestStatus::class,
    // ... other casts
];

public function notes(): HasMany
{
    return $this->hasMany(CustomTripRequestNote::class);
}
```

#### 5. Create Admin Notes Migration (optional)

```php
Schema::create('custom_trip_request_notes', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('custom_trip_request_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->text('content');
    $table->timestamps();
});
```

---

## Files to Create/Modify

| File                                                                                      | Action | Description                     |
| ----------------------------------------------------------------------------------------- | ------ | ------------------------------- |
| `app/Filament/Admin/Resources/CustomTripRequestResource.php`                              | CREATE | Main resource file              |
| `app/Filament/Admin/Resources/CustomTripRequestResource/Pages/ListCustomTripRequests.php` | CREATE | List page                       |
| `app/Filament/Admin/Resources/CustomTripRequestResource/Pages/ViewCustomTripRequest.php`  | CREATE | View page                       |
| `app/Enums/CustomTripRequestStatus.php`                                                   | CREATE | Status enum                     |
| `app/Models/CustomTripRequest.php`                                                        | MODIFY | Add status cast, relationships  |
| `app/Http/Controllers/Api/V1/CustomTripRequestController.php`                             | MODIFY | Add admin notification          |
| `database/migrations/xxx_add_status_to_custom_trip_requests.php`                          | CREATE | Add status column if not exists |
| `database/migrations/xxx_create_custom_trip_request_notes.php`                            | CREATE | Admin notes table               |

---

## Testing Checklist

### Manual Testing

- [ ] Submit a custom trip request from frontend
- [ ] Verify admin receives notification in Filament
- [ ] Click notification and verify navigation to details
- [ ] Verify "Custom Trip Requests" appears in admin sidebar
- [ ] Verify badge shows pending count
- [ ] View request list with all columns
- [ ] Filter by status
- [ ] View request details
- [ ] Change request status
- [ ] Add internal note (if implemented)

### Regression Testing

- [ ] Existing admin panel functionality still works
- [ ] Custom trip submission from frontend still works
- [ ] Other notifications still work
- [ ] No breaking changes to API

---

## Estimated Effort

| Task                                   | Complexity        |
| -------------------------------------- | ----------------- |
| CustomTripRequestResource (list, view) | Medium            |
| Admin notifications on submit          | Low               |
| Status enum and workflow               | Low               |
| Admin notes feature                    | Medium (optional) |
| **Total**                              | 3-4 hours         |
