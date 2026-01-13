# Request Custom Trip Page - BDD Specification & Wireframes

> **Document Version**: 1.0
> **Created**: 2026-01-12
> **Status**: Pending Approval
> **Author**: Claude Opus 4.5

---

## Overview

This document defines the behavior-driven development (BDD) specification and wireframes for the "Request Custom Trip" page. The page allows travelers to submit a personalized trip request through a multi-step wizard form.

---

## BDD Scenarios

```gherkin
Feature: Request Custom Trip
  As a traveler
  I want to submit a custom trip request
  So that I can receive a personalized Tunisia itinerary

  Background:
    Given I am on the "Request Custom Trip" page
    And the page is available in English and French

  # ============================================
  # SCENARIO 1: Page Load & Hero Section
  # ============================================

  Scenario: User sees the hero section on page load
    When the page loads
    Then I should see a hero section with:
      | Element          | Content                                      |
      | Headline         | "Design Your Perfect Tunisia Adventure"      |
      | Subheadline      | "Tell us your dreams, we'll craft the journey" |
      | Background       | Tunisia landscape image with overlay         |
      | Trust Badge      | "Free consultation • Response within 24h"    |
      | CTA Button       | "Start Planning" (scrolls to form)           |
    And I should see a progress indicator showing "Step 1 of 4"

  # ============================================
  # SCENARIO 2: Step 1 - Trip Basics
  # ============================================

  Scenario: User completes Step 1 - Trip Basics
    Given I am on Step 1 "Trip Basics"
    When I fill in the trip basics form
    Then I should see the following fields:
      | Field              | Type           | Required | Validation                    |
      | Travel Dates       | Date Range     | Yes      | Start date >= today           |
      | Flexibility        | Toggle         | No       | "My dates are flexible"       |
      | Number of Adults   | Counter (1-20) | Yes      | Min: 1                        |
      | Number of Children | Counter (0-10) | No       | Default: 0                    |
      | Trip Duration      | Slider (3-21)  | Yes      | Shows "X days" label          |
    And clicking "Continue" should validate and go to Step 2
    And clicking "Continue" with empty required fields shows error messages

  Scenario: User sees live summary update
    Given I am on Step 1
    When I select "2 Adults, 1 Child, 7 days"
    Then the floating summary panel should show:
      | Item       | Value           |
      | Travelers  | 2 Adults, 1 Child |
      | Duration   | 7 days          |
      | Estimate   | "From 2,500 TND/person" |

  # ============================================
  # SCENARIO 3: Step 2 - Interests Selection
  # ============================================

  Scenario: User selects interests in Step 2
    Given I completed Step 1
    And I am on Step 2 "Your Interests"
    Then I should see a grid of clickable interest cards:
      | Interest           | Icon    | Description                    |
      | History & Culture  | 🏛️     | Ancient ruins, museums, medinas |
      | Desert Adventures  | 🏜️     | Sahara, camel treks, oasis     |
      | Beach & Relaxation | 🏖️     | Mediterranean coast, resorts   |
      | Food & Gastronomy  | 🍽️     | Local cuisine, cooking classes |
      | Hiking & Nature    | 🥾     | Mountains, national parks      |
      | Photography        | 📸     | Scenic spots, golden hour tours |
      | Local Festivals    | 🎭     | Traditional events, music      |
      | Star Wars Sites    | ⭐     | Filming locations tour         |
    When I click on a card
    Then it should toggle selected state with green border
    And I must select at least 1 interest to continue
    And I can select up to 5 interests

  Scenario: User sees popular combinations
    Given I am on Step 2
    When I select "Desert Adventures"
    Then I should see a suggestion: "Travelers also love: Star Wars Sites, Photography"

  # ============================================
  # SCENARIO 4: Step 3 - Budget & Style
  # ============================================

  Scenario: User sets budget and style preferences
    Given I completed Step 2
    And I am on Step 3 "Budget & Style"
    Then I should see:
      | Field                | Type              | Options                           |
      | Budget per Person    | Range Slider      | 500 - 10,000 TND                  |
      | Accommodation Style  | Card Selection    | Budget / Mid-range / Luxury       |
      | Travel Pace          | Card Selection    | Relaxed / Moderate / Active       |
      | Special Occasions    | Checkboxes        | Honeymoon, Birthday, Anniversary  |

  Scenario: Budget slider shows dynamic feedback
    Given I am on Step 3
    When I set budget to "3,000 TND"
    Then I should see: "Mid-range trips • Comfortable hotels • Local experiences"
    When I set budget to "7,000 TND"
    Then I should see: "Premium trips • 4-5 star hotels • Private guides"

  # ============================================
  # SCENARIO 5: Step 4 - Contact Details
  # ============================================

  Scenario: User provides contact information
    Given I completed Step 3
    And I am on Step 4 "Contact Details"
    Then I should see:
      | Field              | Type      | Required | Validation           |
      | Full Name          | Text      | Yes      | Min 2 characters     |
      | Email              | Email     | Yes      | Valid email format   |
      | Phone              | Tel       | Yes      | Valid phone format   |
      | WhatsApp           | Tel       | No       | "Same as phone" checkbox |
      | Country            | Dropdown  | Yes      | Country list         |
      | Special Requests   | Textarea  | No       | Max 1000 chars       |
      | Preferred Contact  | Radio     | Yes      | Email / Phone / WhatsApp |
      | Newsletter Consent | Checkbox  | No       | GDPR compliant       |

  Scenario: Form validation on submit
    Given I am on Step 4
    When I click "Submit Request" with invalid email
    Then I should see error: "Please enter a valid email address"
    And the email field should have red border
    And focus should move to the error field

  # ============================================
  # SCENARIO 6: Submission & Confirmation
  # ============================================

  Scenario: Successful form submission
    Given I completed all 4 steps with valid data
    When I click "Submit Request"
    Then I should see a loading state with "Sending your request..."
    And after success, I should see confirmation page with:
      | Element              | Content                                    |
      | Success Icon         | Animated checkmark                         |
      | Headline             | "Your Adventure Awaits!"                   |
      | Reference Number     | "Request #XXXXXX"                          |
      | Email Confirmation   | "We've sent details to your@email.com"     |
      | Timeline             | What happens next (4 steps)                |
      | CTA                  | "Explore Popular Tours" button             |

  Scenario: What happens next timeline
    Given I submitted the form successfully
    Then I should see the timeline:
      | Step | Title                  | Description                      | Time      |
      | 1    | Request Received       | Our team reviews your preferences | Now       |
      | 2    | Expert Assignment      | A Tunisia specialist contacts you | Within 24h |
      | 3    | Custom Itinerary       | Receive your personalized plan   | 2-3 days  |
      | 4    | Refine & Book          | Adjust details and confirm       | Your pace |

  # ============================================
  # SCENARIO 7: Mobile Responsiveness
  # ============================================

  Scenario: Mobile user experience
    Given I am on a mobile device (< 768px)
    Then I should see:
      | Element              | Behavior                           |
      | Progress bar         | Fixed at top, minimal height       |
      | Form steps           | Full-width, stacked vertically     |
      | Interest cards       | 2-column grid                      |
      | Summary panel        | Hidden, accessible via toggle      |
      | Continue button      | Fixed at bottom, full-width        |
      | Back button          | Top-left, always visible           |

  # ============================================
  # SCENARIO 8: Error Handling
  # ============================================

  Scenario: Network error during submission
    Given I completed all steps
    When I submit and network fails
    Then I should see: "Connection error. Please try again."
    And my form data should be preserved
    And I should see a "Retry" button

  Scenario: Session timeout
    Given I started filling the form
    When I am inactive for 30 minutes
    Then I should see a warning: "Your session will expire soon"
    And clicking "Continue" should extend the session
```

---

## Wireframe Specifications

### Page Layout Structure

```
┌─────────────────────────────────────────────────────────────────┐
│                        HEADER (sticky)                          │
│  Logo          Navigation                    Lang | Login       │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                    HERO SECTION                           │  │
│  │                                                           │  │
│  │     "Design Your Perfect Tunisia Adventure"               │  │
│  │     Tell us your dreams, we'll craft the journey          │  │
│  │                                                           │  │
│  │  ┌─────────────────────────────────────────────────────┐  │  │
│  │  │ ✓ Free consultation  ✓ Response within 24h          │  │  │
│  │  └─────────────────────────────────────────────────────┘  │  │
│  │                                                           │  │
│  │              [ Start Planning ↓ ]                         │  │
│  │                                                           │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  PROGRESS BAR                                                   │
│  ●━━━━━━━○━━━━━━━○━━━━━━━○                                      │
│  Step 1      Step 2      Step 3      Step 4                     │
│  Trip Basics  Interests   Budget     Contact                    │
│                                                                 │
├──────────────────────────────────┬──────────────────────────────┤
│                                  │                              │
│  FORM SECTION (65%)              │  SUMMARY PANEL (35%)         │
│                                  │                              │
│  ┌────────────────────────────┐  │  ┌────────────────────────┐  │
│  │ Step 1: Trip Basics        │  │  │ Your Trip Summary      │  │
│  │                            │  │  │                        │  │
│  │ When do you want to travel?│  │  │ 📅 Dec 15-22, 2025    │  │
│  │ ┌────────────────────────┐ │  │  │ 👥 2 Adults, 1 Child  │  │
│  │ │ Dec 15, 2025 → Dec 22  │ │  │  │ ⏱️ 7 days             │  │
│  │ └────────────────────────┘ │  │  │                        │  │
│  │ ☐ My dates are flexible    │  │  │ ───────────────────    │  │
│  │                            │  │  │                        │  │
│  │ How many travelers?        │  │  │ Estimated from:        │  │
│  │ Adults    [−] 2 [+]        │  │  │ 2,500 TND /person      │  │
│  │ Children  [−] 1 [+]        │  │  │                        │  │
│  │                            │  │  │ ───────────────────    │  │
│  │ Trip duration              │  │  │                        │  │
│  │ ○━━━━━━━●━━━━━━━○          │  │  │ 💬 Questions?          │  │
│  │ 3      7 days      21      │  │  │ Chat with us           │  │
│  │                            │  │  │                        │  │
│  │         [ Continue → ]     │  │  └────────────────────────┘  │
│  └────────────────────────────┘  │                              │
│                                  │                              │
└──────────────────────────────────┴──────────────────────────────┘
```

---

### Step 2: Interests Selection Wireframe

```
┌─────────────────────────────────────────────────────────────────┐
│  PROGRESS BAR                                                   │
│  ●━━━━━━━●━━━━━━━○━━━━━━━○                                      │
│  Step 1 ✓    Step 2      Step 3      Step 4                     │
├──────────────────────────────────┬──────────────────────────────┤
│                                  │                              │
│  Step 2: What interests you?     │  Your Trip Summary           │
│  Select up to 5 experiences      │                              │
│                                  │  📅 Dec 15-22, 2025          │
│  ┌─────────┐ ┌─────────┐        │  👥 2 Adults, 1 Child        │
│  │   🏛️   │ │   🏜️   │        │  ⏱️ 7 days                   │
│  │         │ │ ██████ │        │                              │
│  │ History │ │ Desert │        │  Selected interests:         │
│  │& Culture│ │Adventure│        │  • Desert Adventures         │
│  │         │ │ ✓      │        │  • Star Wars Sites           │
│  └─────────┘ └─────────┘        │                              │
│  ┌─────────┐ ┌─────────┐        │  ───────────────────         │
│  │   🏖️   │ │   🍽️   │        │                              │
│  │         │ │         │        │  💡 Travelers also love:     │
│  │  Beach  │ │  Food   │        │  Photography tours           │
│  │& Relax  │ │& Gastro │        │                              │
│  └─────────┘ └─────────┘        │                              │
│  ┌─────────┐ ┌─────────┐        │                              │
│  │   🥾   │ │   📸   │        │                              │
│  │         │ │         │        │                              │
│  │ Hiking  │ │  Photo  │        │                              │
│  │& Nature │ │  Tours  │        │                              │
│  └─────────┘ └─────────┘        │                              │
│  ┌─────────┐ ┌─────────┐        │                              │
│  │   🎭   │ │   ⭐   │        │                              │
│  │         │ │ ██████ │        │                              │
│  │ Local   │ │Star Wars│        │                              │
│  │Festivals│ │ Sites ✓ │        │                              │
│  └─────────┘ └─────────┘        │                              │
│                                  │                              │
│  [ ← Back ]      [ Continue → ]  │                              │
└──────────────────────────────────┴──────────────────────────────┘

Legend: ██████ = Selected card (green border)
```

---

### Step 3: Budget & Style Wireframe

```
┌─────────────────────────────────────────────────────────────────┐
│  PROGRESS BAR                                                   │
│  ●━━━━━━━●━━━━━━━●━━━━━━━○                                      │
│  Step 1 ✓   Step 2 ✓    Step 3     Step 4                       │
├──────────────────────────────────┬──────────────────────────────┤
│                                  │                              │
│  Step 3: Budget & Style          │  Your Trip Summary           │
│                                  │                              │
│  Budget per person (TND)         │  📅 Dec 15-22, 2025          │
│  ┌────────────────────────────┐  │  👥 2 Adults, 1 Child        │
│  │ 500 ────────●───── 10,000  │  │  ⏱️ 7 days                   │
│  │           3,500 TND        │  │  🎯 Desert, Star Wars        │
│  └────────────────────────────┘  │                              │
│                                  │  Budget: 3,500 TND/person    │
│  "Mid-range • Comfortable hotels │  Style: Mid-range            │
│   • Local experiences included"  │  Pace: Moderate              │
│                                  │                              │
│  ─────────────────────────────   │  ───────────────────         │
│                                  │                              │
│  Accommodation Style             │  Estimated Total:            │
│  ┌────────┐┌────────┐┌────────┐  │  10,500 TND                  │
│  │        ││████████││        │  │  (3 travelers)               │
│  │ Budget ││Mid-range││ Luxury │  │                              │
│  │        ││   ✓    ││        │  │                              │
│  └────────┘└────────┘└────────┘  │                              │
│                                  │                              │
│  Travel Pace                     │                              │
│  ┌────────┐┌────────┐┌────────┐  │                              │
│  │        ││████████││        │  │                              │
│  │Relaxed ││Moderate ││ Active │  │                              │
│  │ 🐢    ││   ✓ 🚶  ││  🏃   │  │                              │
│  └────────┘└────────┘└────────┘  │                              │
│                                  │                              │
│  Special Occasion? (optional)    │                              │
│  ☐ Honeymoon  ☐ Birthday        │                              │
│  ☐ Anniversary ☐ Other          │                              │
│                                  │                              │
│  [ ← Back ]      [ Continue → ]  │                              │
└──────────────────────────────────┴──────────────────────────────┘
```

---

### Step 4: Contact Details Wireframe

```
┌─────────────────────────────────────────────────────────────────┐
│  PROGRESS BAR                                                   │
│  ●━━━━━━━●━━━━━━━●━━━━━━━●                                      │
│  Step 1 ✓   Step 2 ✓   Step 3 ✓   Step 4                        │
├──────────────────────────────────┬──────────────────────────────┤
│                                  │                              │
│  Step 4: How can we reach you?   │  Your Trip Summary           │
│                                  │                              │
│  Full Name *                     │  📅 Dec 15-22, 2025          │
│  ┌────────────────────────────┐  │  👥 2 Adults, 1 Child        │
│  │ John Smith                 │  │  ⏱️ 7 days                   │
│  └────────────────────────────┘  │  🎯 Desert, Star Wars        │
│                                  │  💰 3,500 TND/person         │
│  Email *                         │  🏨 Mid-range                │
│  ┌────────────────────────────┐  │  🚶 Moderate pace            │
│  │ john@example.com           │  │                              │
│  └────────────────────────────┘  │  ───────────────────         │
│                                  │                              │
│  Phone *           Country *     │  Total Estimate:             │
│  ┌──────────────┐ ┌───────────┐  │  ~10,500 TND                 │
│  │ +1 555-1234  │ │ USA     ▼ │  │                              │
│  └──────────────┘ └───────────┘  │  ───────────────────         │
│                                  │                              │
│  WhatsApp (optional)             │  🔒 Your data is secure      │
│  ┌────────────────────────────┐  │  We never share your info    │
│  │ ☑ Same as phone number     │  │                              │
│  └────────────────────────────┘  │                              │
│                                  │                              │
│  Special Requests (optional)     │                              │
│  ┌────────────────────────────┐  │                              │
│  │ We're celebrating our      │  │                              │
│  │ anniversary! Would love    │  │                              │
│  │ romantic dinner setup...   │  │                              │
│  └────────────────────────────┘  │                              │
│                                  │                              │
│  Preferred contact method *      │                              │
│  ○ Email  ● WhatsApp  ○ Phone    │                              │
│                                  │                              │
│  ☐ Send me travel tips & offers  │                              │
│                                  │                              │
│  [ ← Back ]   [ Submit Request ] │                              │
└──────────────────────────────────┴──────────────────────────────┘
```

---

### Success Page Wireframe

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│                         ┌─────────┐                             │
│                         │   ✓    │  (animated checkmark)       │
│                         └─────────┘                             │
│                                                                 │
│                  Your Adventure Awaits! 🎉                      │
│                                                                 │
│            Request #GA-2025-001234 submitted                    │
│                                                                 │
│     We've sent a confirmation to john@example.com               │
│                                                                 │
│  ─────────────────────────────────────────────────────────────  │
│                                                                 │
│                    What Happens Next?                           │
│                                                                 │
│     ●━━━━━━━━━━━━○━━━━━━━━━━━━○━━━━━━━━━━━━○                    │
│     │            │            │            │                    │
│   ┌─┴─┐        ┌─┴─┐        ┌─┴─┐        ┌─┴─┐                  │
│   │ 1 │        │ 2 │        │ 3 │        │ 4 │                  │
│   └───┘        └───┘        └───┘        └───┘                  │
│  Received    Expert      Itinerary    Refine                   │
│    Now      < 24 hours    2-3 days    & Book                   │
│                                                                 │
│  ─────────────────────────────────────────────────────────────  │
│                                                                 │
│        [ Explore Popular Tours ]  [ Back to Home ]              │
│                                                                 │
│  ─────────────────────────────────────────────────────────────  │
│                                                                 │
│  Questions? Contact us:                                         │
│  📧 contact@go-adventure.net  📞 +216 52 665 202                │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

### Mobile Wireframe (< 768px)

```
┌───────────────────────┐
│ ← Back    Step 2 of 4 │
├───────────────────────┤
│ ●━━●━━○━━○            │
├───────────────────────┤
│                       │
│ What interests you?   │
│ Select up to 5        │
│                       │
│ ┌─────────┐┌─────────┐│
│ │   🏛️   ││   🏜️   ││
│ │ History ││ Desert  ││
│ │& Culture││Adventure││
│ └─────────┘└─────────┘│
│ ┌─────────┐┌─────────┐│
│ │   🏖️   ││   🍽️   ││
│ │  Beach  ││  Food   ││
│ │& Relax  ││& Gastro ││
│ └─────────┘└─────────┘│
│ ┌─────────┐┌─────────┐│
│ │   🥾   ││   📸   ││
│ │ Hiking  ││  Photo  ││
│ │& Nature ││  Tours  ││
│ └─────────┘└─────────┘│
│ ┌─────────┐┌─────────┐│
│ │   🎭   ││   ⭐   ││
│ │Festivals││Star Wars││
│ └─────────┘└─────────┘│
│                       │
│ Selected: 2/5         │
│                       │
├───────────────────────┤
│  [ 📋 View Summary ]  │
├───────────────────────┤
│ ███████████████████████│
│ [    Continue →     ] │
└───────────────────────┘
```

---

## Technical Implementation Notes

### Frontend Components Structure

```
src/app/[locale]/custom-trip/
├── page.tsx                    # Main page component
├── components/
│   ├── CustomTripWizard.tsx    # Main wizard container
│   ├── ProgressBar.tsx         # Step progress indicator
│   ├── TripSummary.tsx         # Floating summary panel
│   ├── steps/
│   │   ├── TripBasicsStep.tsx  # Step 1
│   │   ├── InterestsStep.tsx   # Step 2
│   │   ├── BudgetStyleStep.tsx # Step 3
│   │   └── ContactStep.tsx     # Step 4
│   └── SuccessPage.tsx         # Confirmation page
```

### Backend API Endpoint

```
POST /api/v1/custom-trip-requests

Request Body:
{
  "travel_dates": {
    "start": "2025-12-15",
    "end": "2025-12-22",
    "flexible": true
  },
  "travelers": {
    "adults": 2,
    "children": 1
  },
  "duration_days": 7,
  "interests": ["desert", "star-wars", "photography"],
  "budget": {
    "per_person": 3500,
    "currency": "TND"
  },
  "accommodation_style": "mid-range",
  "travel_pace": "moderate",
  "special_occasions": ["anniversary"],
  "contact": {
    "name": "John Smith",
    "email": "john@example.com",
    "phone": "+1555123456",
    "whatsapp": "+1555123456",
    "country": "US",
    "preferred_method": "whatsapp"
  },
  "special_requests": "We're celebrating our anniversary...",
  "newsletter_consent": false,
  "locale": "en"
}

Response (201 Created):
{
  "data": {
    "id": "ctr_abc123",
    "reference": "GA-2025-001234",
    "status": "pending",
    "created_at": "2025-01-12T19:30:00Z"
  },
  "message": "Custom trip request submitted successfully"
}
```

### Database Model

```sql
CREATE TABLE custom_trip_requests (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  reference VARCHAR(20) UNIQUE NOT NULL,
  status VARCHAR(20) DEFAULT 'pending',

  -- Trip Details
  travel_start_date DATE NOT NULL,
  travel_end_date DATE NOT NULL,
  dates_flexible BOOLEAN DEFAULT FALSE,
  adults INTEGER NOT NULL DEFAULT 1,
  children INTEGER DEFAULT 0,
  duration_days INTEGER NOT NULL,

  -- Preferences
  interests JSONB NOT NULL,
  budget_per_person INTEGER NOT NULL,
  budget_currency VARCHAR(3) DEFAULT 'TND',
  accommodation_style VARCHAR(20),
  travel_pace VARCHAR(20),
  special_occasions JSONB,
  special_requests TEXT,

  -- Contact
  contact_name VARCHAR(255) NOT NULL,
  contact_email VARCHAR(255) NOT NULL,
  contact_phone VARCHAR(50) NOT NULL,
  contact_whatsapp VARCHAR(50),
  contact_country VARCHAR(2) NOT NULL,
  preferred_contact_method VARCHAR(20) NOT NULL,
  newsletter_consent BOOLEAN DEFAULT FALSE,

  -- Metadata
  locale VARCHAR(5) DEFAULT 'en',
  ip_address INET,
  user_agent TEXT,
  assigned_agent_id UUID REFERENCES users(id),

  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

---

## Design Tokens

| Token                   | Value                     | Usage                                  |
| ----------------------- | ------------------------- | -------------------------------------- |
| `--color-primary`       | #0D642E                   | Buttons, progress bar, selected states |
| `--color-primary-light` | #8BC34A                   | Hover states, accents                  |
| `--color-cream`         | #f5f0d1                   | Summary panel background               |
| `--color-success`       | #22c55e                   | Success checkmark                      |
| `--color-error`         | #ef4444                   | Error states                           |
| `--border-radius-card`  | 12px                      | Interest cards, form sections          |
| `--shadow-card`         | 0 4px 6px rgba(0,0,0,0.1) | Elevated elements                      |

---

## Accessibility Checklist

- [ ] All form fields have associated labels
- [ ] Error messages linked with aria-describedby
- [ ] Focus management between steps
- [ ] Keyboard navigation for card selection
- [ ] Screen reader announcements for step changes
- [ ] Color contrast ratio ≥ 4.5:1
- [ ] Touch targets ≥ 44x44px on mobile

---

## Approval

| Role          | Name | Date | Status  |
| ------------- | ---- | ---- | ------- |
| Product Owner |      |      | Pending |
| Designer      |      |      | Pending |
| Tech Lead     |      |      | Pending |

---

_Document generated by Claude Opus 4.5_
