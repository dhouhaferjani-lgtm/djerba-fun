# Agentic Checkout - AI Agent Integration Guide

> **Status**: Planned (Not yet implemented)
> **Purpose**: Enable AI assistants (ChatGPT, Claude, Perplexity) to help end users complete bookings through structured data exposure and delegated authentication.

---

## Table of Contents

1. [Overview](#overview)
2. [llms.txt Specification](#llmstxt-specification)
3. [AI Agent Integration Guide](#ai-agent-integration-guide)
4. [Delegation Flow](#delegation-flow)
5. [Security Considerations](#security-considerations)
6. [Testing with AI Platforms](#testing-with-ai-platforms)
7. [Implementation Checklist](#implementation-checklist)

---

## Overview

The Agentic Checkout system enables AI assistants to act on behalf of users to discover, book, and pay for tourism experiences. Unlike the Partner API (B2B), this is designed for AI-to-API interactions where:

- **Who**: ChatGPT, Claude, Perplexity, other AI assistants
- **Need**: Help end users complete bookings through AI conversation
- **Revenue Model**: Standard booking commission (same as direct bookings)
- **Management**: Structured data exposure (llms.txt, OpenAPI), delegated authentication

### Key Differences from Partner API

| Feature        | Partner API                     | Agentic Checkout             |
| -------------- | ------------------------------- | ---------------------------- |
| User Type      | B2B (hotels, tour operators)    | End consumers via AI         |
| Authentication | API Key + Secret (headers)      | Delegated OAuth tokens       |
| Payment        | Partner collects, settles later | Immediate payment processing |
| Commission     | 10-15% (tiered)                 | Standard platform rate       |
| Use Case       | Reselling, white-label          | AI-assisted booking          |

---

## llms.txt Specification

The `llms.txt` file is a markdown-based standard (2024) that makes your platform discoverable and understandable by AI assistants.

### File Location

- **Primary**: `https://goadventure.tn/llms.txt`
- **Comprehensive**: `https://goadventure.tn/llms-full.txt`

### llms.txt Format

```markdown
# Go Adventure - Tourism Marketplace

> AI-powered discovery and booking for outdoor adventures in Tunisia

## About

Go Adventure is a tourism marketplace connecting travelers with unique outdoor
experiences including hiking, desert tours, cultural events, and adventure activities
across Tunisia.

## Available for AI Agents

This platform supports AI-assisted booking through:

- Natural language search and discovery
- Delegated authentication for secure bookings
- Real-time availability checking
- Secure payment processing on behalf of users

## API Overview

Base URL: https://api.goadventure.tn/api/v1

### Authentication for AI Agents

AI assistants can help users book experiences using delegated authentication:

1. User provides credentials to AI assistant
2. AI creates delegation token via `/api/v1/ai/delegate`
3. Token is time-limited, scoped, and revocable
4. AI uses token for all subsequent requests

### Key Endpoints

- **Search**: `POST /api/v1/ai/search` - Natural language search
- **Listings**: `GET /api/v1/listings` - Browse experiences
- **Availability**: `GET /api/v1/listings/{slug}/availability`
- **Booking**: `POST /api/v1/ai/bookings/quick` - One-step booking
- **Payment**: `POST /api/v1/ai/payments/process` - Process payment

### User Account Management

- **Register**: `POST /api/v1/ai/register` - Create account for user
- **Delegate**: `POST /api/v1/ai/delegate` - Get delegation token

## OpenAPI Specification

Full API documentation: https://api.goadventure.tn/api/v1/openapi.json

## Capabilities

AI assistants can help users:

- Search for experiences by natural language ("hiking near Tunis this weekend")
- Check real-time availability and pricing
- Create user accounts seamlessly
- Complete bookings with payment
- Manage existing bookings

## Safety & Trust

- User credentials are never stored by the platform
- Delegation tokens are short-lived (1 hour default)
- All transactions are auditable
- Users can revoke AI access at any time
- PCI-compliant payment processing

## Support

For AI integration questions: api-support@goadventure.tn
Documentation: https://goadventure.tn/ai-agent-guide
```

### llms-full.txt Format

Extended version with:

- Detailed endpoint descriptions
- Example request/response payloads
- Error handling guidelines
- Rate limiting information
- Webhook documentation

---

## AI Agent Integration Guide

### Step 1: Discovery

AI assistants discover your platform through:

1. **llms.txt indexing** - Platforms like OpenAI, Anthropic, Microsoft index these files
2. **User mention** - "Book me a hiking trip on goadventure.tn"
3. **Search engines** - Enhanced SEO with structured data

### Step 2: Understanding Capabilities

AI reads your OpenAPI specification to understand:

- Available endpoints and operations
- Request/response schemas
- Authentication requirements
- Rate limits and constraints

### Step 3: User Intent Processing

When a user says: _"I want to book a desert tour in Tunisia next month"_

The AI assistant:

1. Parses intent (book, desert tour, Tunisia, next month)
2. Calls `POST /api/v1/ai/search` with natural language query
3. Presents options to user
4. Proceeds with booking if user confirms

### Step 4: Authentication Flow

See [Delegation Flow](#delegation-flow) section below.

### Step 5: Booking Execution

AI calls simplified endpoints designed for conversational UX:

```http
POST /api/v1/ai/bookings/quick
Authorization: Bearer {delegation_token}
Content-Type: application/json

{
  "listing_slug": "desert-safari-douz",
  "slot_id": "uuid-of-slot",
  "quantity": 2,
  "traveler_info": {
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+1234567890"
  },
  "payment_method": {
    "type": "credit_card",
    "token": "tok_visa_4242"
  }
}
```

---

## Delegation Flow

### OAuth 2.0 Extension for AI (IETF Draft)

Based on the emerging standard for AI agent delegation.

### Principles

1. **Just-Enough Access**: Tokens are scoped to specific permissions
2. **Just-In-Time Consent**: User approves each delegation
3. **Full Auditability**: All AI actions are logged and attributable

### Flow Diagram

```
User                    AI Assistant              Platform API
  |                           |                         |
  | "Book a tour"            |                         |
  |------------------------->|                         |
  |                           |                         |
  |                           | "I need your login"    |
  |<--------------------------|                         |
  |                           |                         |
  | email: user@example.com  |                         |
  | password: ••••••••       |                         |
  |------------------------->|                         |
  |                           |                         |
  |                           | POST /api/v1/ai/delegate|
  |                           |------------------------>|
  |                           |                         |
  |                           |    {delegation_token}   |
  |                           |<------------------------|
  |                           |                         |
  |                           | POST /ai/bookings/quick |
  |                           | Bearer: {token}         |
  |                           |------------------------>|
  |                           |                         |
  |                           |    {booking_confirmed}  |
  |                           |<------------------------|
  |                           |                         |
  | "Your booking is confirmed!"|                      |
  |<--------------------------|                         |
```

### Delegation Endpoint

```http
POST /api/v1/ai/delegate
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "user_password",
  "scopes": ["bookings:create", "payments:process"],
  "consent": true,
  "session_id": "ai_session_12345",
  "ai_platform": "chatgpt",
  "expires_in": 3600
}
```

**Response:**

```json
{
  "delegation_token": "dlg_at_abc123...",
  "expires_at": "2025-12-23T15:30:00Z",
  "scopes": ["bookings:create", "payments:process"],
  "user": {
    "id": 123,
    "name": "John Doe",
    "email": "user@example.com"
  }
}
```

### Delegation Token Format (JWT)

```json
{
  "iss": "https://api.goadventure.tn",
  "sub": "user:123",
  "aud": "ai-agents",
  "exp": 1735048200,
  "iat": 1735044600,
  "scopes": ["bookings:create", "payments:process"],
  "act": {
    "sub": "ai_platform:chatgpt",
    "session_id": "ai_session_12345"
  }
}
```

The `act` (actor) claim identifies the AI assistant acting on behalf of the user.

### Token Revocation

Users can revoke delegation tokens at any time:

```http
DELETE /api/v1/ai/delegations/{token_id}
Authorization: Bearer {user_token}
```

Or revoke all AI delegations:

```http
DELETE /api/v1/ai/delegations/all
Authorization: Bearer {user_token}
```

---

## Security Considerations

### 1. Credential Handling

**NEVER store user credentials:**

- Platform receives credentials only during delegation
- Credentials are verified and immediately discarded
- Only delegation token is returned

**Implementation:**

```php
public function delegate(Request $request): JsonResponse
{
    $validated = $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
        'scopes' => 'required|array',
        'consent' => 'required|boolean',
    ]);

    // Verify credentials (DON'T STORE)
    $user = User::where('email', $validated['email'])->first();

    if (!$user || !Hash::check($validated['password'], $user->password)) {
        abort(401, 'Invalid credentials');
    }

    // Create delegation token
    $delegation = AiDelegation::create([
        'user_id' => $user->id,
        'scopes' => $validated['scopes'],
        'expires_at' => now()->addHours(1),
        // ... other fields
    ]);

    return response()->json([
        'delegation_token' => $delegation->generateJWT(),
        // ...
    ]);
}
```

### 2. Scope Limitations

Define granular scopes:

- `listings:read` - Search and view listings
- `bookings:create` - Create bookings
- `bookings:read` - View user's bookings
- `bookings:cancel` - Cancel bookings
- `payments:process` - Process payments
- `profile:read` - Read user profile
- `profile:update` - Update user profile

**Never grant more than necessary.** AI should request minimum scopes.

### 3. Payment Security

**Tokenization:**

- Never pass raw credit card numbers
- Use payment gateway tokenization (Stripe, PayPal)
- AI provides payment token, not card details

**Example:**

```javascript
// AI SHOULD NOT receive this:
{
  "card_number": "4242424242424242",
  "exp_month": 12,
  "exp_year": 2025,
  "cvv": "123"
}

// AI SHOULD receive this:
{
  "payment_token": "tok_visa_4242",
  "type": "credit_card"
}
```

**Implementation:**

```php
public function processPayment(Request $request)
{
    $validated = $request->validate([
        'payment_token' => 'required|string', // NOT raw card data
        'amount' => 'required|numeric',
    ]);

    // Process via payment gateway
    $charge = $this->paymentGateway->charge([
        'token' => $validated['payment_token'],
        'amount' => $validated['amount'],
    ]);
}
```

### 4. Rate Limiting

Protect against abuse:

```php
Route::middleware(['throttle:ai-agent'])
    ->prefix('ai')
    ->group(function () {
        // AI endpoints
    });
```

**Rate limits:**

- Delegation: 5 per hour per IP
- Search: 100 per hour per delegation
- Bookings: 10 per hour per delegation

### 5. Fraud Detection

Monitor for suspicious patterns:

```php
class AiFraudDetectionService
{
    public function checkBooking(Booking $booking, AiDelegation $delegation): bool
    {
        // Check for rapid successive bookings
        $recentBookings = Booking::where('delegation_id', $delegation->id)
            ->where('created_at', '>', now()->subMinutes(10))
            ->count();

        if ($recentBookings > 3) {
            return false; // Suspicious
        }

        // Check for unusual amounts
        if ($booking->total > 5000) {
            // Flag for manual review
            $booking->update(['requires_review' => true]);
        }

        return true;
    }
}
```

### 6. Audit Logging

Log all AI actions:

```php
AiDelegationLog::create([
    'delegation_id' => $delegation->id,
    'action' => 'booking.created',
    'resource_id' => $booking->id,
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent(),
    'metadata' => [
        'ai_platform' => $delegation->ai_platform,
        'amount' => $booking->total,
    ],
]);
```

---

## Testing with AI Platforms

### Testing with ChatGPT

1. **Enable ChatGPT Plugins** (ChatGPT Plus required)
2. **Provide llms.txt URL**: "Check out https://goadventure.tn/llms.txt"
3. **Test delegation**: "Help me book a tour, I'll provide my credentials"
4. **Monitor logs**: Check `ai_delegations` and `ai_delegation_logs` tables

### Testing with Claude

1. **Use Claude API** with tools/function calling
2. **Provide OpenAPI spec** in system prompt
3. **Test delegation flow** with mock credentials
4. **Verify token scoping** works correctly

### Testing with Perplexity

1. **Wait for official Perplexity API integration** (coming soon)
2. **Ensure llms.txt is indexed**
3. **Test discovery**: "Find outdoor activities in Tunisia"

### Test Checklist

- [ ] AI can discover platform via llms.txt
- [ ] AI can parse OpenAPI specification
- [ ] Delegation flow creates valid JWT tokens
- [ ] Scopes are correctly enforced
- [ ] Payment tokenization works
- [ ] Bookings are created successfully
- [ ] Audit logs capture all actions
- [ ] Token revocation works
- [ ] Rate limits are enforced
- [ ] Fraud detection triggers on suspicious activity

---

## Implementation Checklist

### Phase 1: Discovery & Documentation

- [ ] Create `/llms.txt` file
- [ ] Create `/llms-full.txt` file
- [ ] Enhance OpenAPI spec with AI annotations
- [ ] Build AI agent guide page at `/ai-agent-guide`
- [ ] Update sitemap to include AI resources

### Phase 2: Delegation Flow

- [ ] Create `ai_delegations` table migration
- [ ] Create `AiDelegation` model
- [ ] Implement delegation endpoint `POST /api/v1/ai/delegate`
- [ ] Build `AiDelegationMiddleware` for JWT validation
- [ ] Add consent tracking in `consents` table
- [ ] Create delegation revocation endpoints

### Phase 3: Context Management

- [ ] Create `ai_conversation_sessions` table migration
- [ ] Create `AiConversationSession` model
- [ ] Build `AiContextService` (Redis + PostgreSQL)
- [ ] Implement session management endpoints
- [ ] Create context cleanup jobs

### Phase 4: AI-Optimized Endpoints

- [ ] Build `POST /api/v1/ai/search` (natural language search)
- [ ] Build `POST /api/v1/ai/register` (simplified registration)
- [ ] Build `POST /api/v1/ai/bookings/quick` (one-step booking)
- [ ] Build `POST /api/v1/ai/payments/process` (secure payment)

### Phase 5: Security & Compliance

- [ ] Implement rate limiting for AI endpoints
- [ ] Build `AiFraudDetectionService`
- [ ] Enhance audit logging for AI actions
- [ ] Create user transparency dashboard
- [ ] Add GDPR compliance checks

### Phase 6: Frontend Integration

- [ ] Create AI referral landing page
- [ ] Add analytics tracking for AI bookings
- [ ] Build booking confirmation enhancements
- [ ] Create user dashboard for AI activity

### Phase 7: Testing & Launch

- [ ] Test with ChatGPT
- [ ] Test with Claude
- [ ] Test with Perplexity
- [ ] Launch beta program
- [ ] Monitor and optimize

---

## Cost Analysis

### Expected Costs per AI-Initiated Booking

- **Infrastructure**: ~$0.01
- **API processing**: ~$0.02
- **Total**: ~$0.03 per booking

### Expected ROI

- **AI discovery increases reach**: No upfront marketing cost
- **Pay only for successful bookings**: Standard commission applies
- **First-mover advantage**: Early adoption of AI-native commerce

---

## Support & Resources

- **Email**: api-support@goadventure.tn
- **Documentation**: https://goadventure.tn/ai-agent-guide
- **OpenAPI Spec**: https://api.goadventure.tn/api/v1/openapi.json
- **Status Page**: https://status.goadventure.tn

---

**Last Updated**: 2025-12-23
**Version**: 1.0 (Planning)
**Status**: Not yet implemented - See implementation plan for timeline
