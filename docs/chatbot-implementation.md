# On-Site Booking Assistant Chatbot - Implementation Guide

> **Status**: Planned (Not yet implemented)
> **Purpose**: Embedded AI chatbot using LLM + RAG for conversational booking assistance on the Go Adventure website.

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [RAG Pipeline](#rag-pipeline-retrieval-augmented-generation)
4. [Function/Tool Definitions](#functiontool-definitions)
5. [Admin Management](#admin-management)
6. [Cost Optimization](#cost-optimization-strategies)
7. [Implementation Guide](#implementation-guide)

---

## Overview

The On-Site Booking Assistant is an embedded chatbot that helps website visitors:

- **Discover** listings through natural language ("Show me hiking trips near Tunis")
- **Answer questions** about activities, locations, pricing, availability
- **Complete bookings** with conversational guidance
- **Get recommendations** based on preferences and constraints

### Key Characteristics

- **Who**: Website visitors (both authenticated and guests)
- **Technology**: Anthropic Claude 3.5 Sonnet + pgvector RAG
- **Revenue Model**: Direct bookings (standard commission)
- **Integration**: Embedded widget on all pages

### Business Value

- **Increased conversion**: 12-15% chat-to-booking rate (industry benchmark)
- **Reduced support load**: AI handles common questions
- **Better discovery**: Natural language search improves listing visibility
- **User insights**: Chat transcripts reveal user intent and pain points

---

## Architecture

### System Overview

```
┌─────────────────────────────────────────────────────────┐
│                Frontend (Next.js)                        │
│  ┌─────────────────────────────────────────────────┐   │
│  │  ChatWidget Component                            │   │
│  │  - Floating button (bottom-right)               │   │
│  │  - Expandable chat panel                        │   │
│  │  - Message history with bubbles                 │   │
│  │  - Typing indicators                            │   │
│  │  - Rich components (listing cards, calendars)   │   │
│  │  - Quick action buttons                         │   │
│  └─────────────────────────────────────────────────┘   │
└────────────────────┬────────────────────────────────────┘
                     │ HTTP/SSE
                     ▼
┌─────────────────────────────────────────────────────────┐
│              Backend API (Laravel)                       │
│  ┌──────────────────────────────────────────────────┐  │
│  │  ChatController                                   │  │
│  │  POST /api/v1/chat/message                      │  │
│  │  GET  /api/v1/chat/history                      │  │
│  └──────────────┬───────────────────────────────────┘  │
│                 │                                        │
│  ┌──────────────▼───────────────────────────────────┐  │
│  │  ChatService (Orchestration)                     │  │
│  │  - Session management                            │  │
│  │  - Context building                              │  │
│  │  - Function routing                              │  │
│  └──────────────┬───────────────────────────────────┘  │
│                 │                                        │
│  ┌──────────────▼───────────────────────────────────┐  │
│  │  LLMService (Claude API)                         │  │
│  │  - Anthropic SDK integration                     │  │
│  │  - Prompt engineering                            │  │
│  │  - Tool/function definitions                     │  │
│  └──────────────┬───────────────────────────────────┘  │
│                 │                                        │
│       ┌─────────┴──────────┐                            │
│       │                    │                            │
│  ┌────▼─────────┐   ┌─────▼─────────────────────────┐ │
│  │  RAGService  │   │  FunctionHandler              │ │
│  │  - pgvector  │   │  - search_listings()          │ │
│  │  - Semantic  │   │  - get_availability()         │ │
│  │    search    │   │  - add_to_cart()              │ │
│  └──────────────┘   └───────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Technology Stack

**LLM Provider**: Anthropic Claude 3.5 Sonnet

- **Why**: Best-in-class conversation, strong function calling, 200K context
- **Cost**: $3/1M input tokens, $15/1M output tokens
- **Features**: Prompt caching (90% discount on repeated context)

**Vector Database**: pgvector (PostgreSQL extension)

- **Why**: Zero infrastructure overhead, sub-50ms queries
- **Features**: HNSW indexing, cosine similarity, hybrid search

**Embeddings**: OpenAI text-embedding-3-small

- **Why**: Best price/performance ratio
- **Cost**: $0.02/1M tokens
- **Dimensions**: 1536

---

## RAG Pipeline (Retrieval-Augmented Generation)

### What is RAG?

RAG enhances LLM responses by retrieving relevant information from a knowledge base before generating answers. This prevents hallucinations and ensures accuracy.

### Pipeline Flow

```
User Query: "Show me hiking trips near Tunis under €50"
    │
    ▼
1. Embed Query
   OpenAI Embedding API → [0.123, -0.456, 0.789, ...]
    │
    ▼
2. Vector Search (pgvector)
   SELECT * FROM listing_embeddings
   ORDER BY embedding <-> query_embedding
   LIMIT 5
    │
    ▼
3. Retrieved Listings
   - [Hiking] Cap Bon Peninsula Trail - €35
   - [Hiking] Zaghouan Mountain Trek - €45
   - [Hiking] Bou Kornine National Park - €30
    │
    ▼
4. Build Context
   System Prompt + Retrieved Listings + Conversation History
    │
    ▼
5. LLM Generation (Claude)
   "I found 3 hiking trips near Tunis under €50:
    1. Cap Bon Peninsula Trail (€35) - ..."
```

### Database Schema

```sql
-- Enable pgvector extension
CREATE EXTENSION IF NOT EXISTS vector;

-- Listing embeddings
CREATE TABLE listing_embeddings (
    id BIGSERIAL PRIMARY KEY,
    listing_id BIGINT UNSIGNED NOT NULL,
    locale VARCHAR(5) NOT NULL, -- 'en' or 'fr'
    content_type VARCHAR(50), -- 'full', 'title_summary'
    content_hash VARCHAR(64), -- For change detection
    embedding vector(1536), -- OpenAI embeddings
    metadata JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE (listing_id, locale, content_type)
);

-- HNSW index for fast vector search
CREATE INDEX listing_embeddings_embedding_idx
ON listing_embeddings
USING hnsw (embedding vector_cosine_ops);
```

### Embedding Generation

```php
class EmbeddingService
{
    public function generateForListing(Listing $listing, string $locale): void
    {
        // Build content for embedding
        $content = $this->buildContent($listing, $locale);

        // Generate embedding via OpenAI API
        $response = Http::withToken(config('openai.api_key'))
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => 'text-embedding-3-small',
                'input' => $content,
            ]);

        $embedding = $response->json('data.0.embedding');

        // Store in database
        ListingEmbedding::updateOrCreate([
            'listing_id' => $listing->id,
            'locale' => $locale,
            'content_type' => 'full',
        ], [
            'embedding' => json_encode($embedding),
            'content_hash' => hash('sha256', $content),
            'metadata' => [
                'title' => $listing->title,
                'category' => $listing->service_type,
                'price' => $listing->pricing['base'] ?? null,
            ],
        ]);
    }

    private function buildContent(Listing $listing, string $locale): string
    {
        return implode(' ', [
            $listing->title,
            $listing->description,
            $listing->location?->name,
            $listing->service_type,
            "Price: {$listing->pricing['base']} {$listing->pricing['currency']}",
            // Include other searchable fields
        ]);
    }
}
```

### RAG Retrieval

```php
class RAGService
{
    public function searchListings(string $query, string $locale, int $limit = 5): Collection
    {
        // Generate embedding for query
        $queryEmbedding = $this->embedQuery($query);

        // Vector similarity search
        return DB::select("
            SELECT
                l.id,
                l.title,
                l.slug,
                l.description,
                l.pricing,
                le.embedding <-> ? AS distance
            FROM listings l
            JOIN listing_embeddings le ON l.id = le.listing_id
            WHERE le.locale = ?
            ORDER BY le.embedding <-> ?
            LIMIT ?
        ", [$queryEmbedding, $locale, $queryEmbedding, $limit]);
    }

    private function embedQuery(string $query): string
    {
        $response = Http::withToken(config('openai.api_key'))
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => 'text-embedding-3-small',
                'input' => $query,
            ]);

        return json_encode($response->json('data.0.embedding'));
    }
}
```

---

## Function/Tool Definitions

Claude can call functions to perform actions. Define tools using this schema:

### Tool Schema

```php
$tools = [
    [
        'name' => 'search_listings',
        'description' => 'Search for tourism listings/experiences based on natural language criteria',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Natural language search query from the user',
                ],
                'location' => [
                    'type' => 'string',
                    'description' => 'Location/city/region (optional)',
                ],
                'max_price' => [
                    'type' => 'number',
                    'description' => 'Maximum price in EUR (optional)',
                ],
                'category' => [
                    'type' => 'string',
                    'enum' => ['tour', 'event', 'all'],
                    'description' => 'Category filter (optional)',
                ],
            ],
            'required' => ['query'],
        ],
    ],
    [
        'name' => 'get_availability',
        'description' => 'Check availability for a specific listing on given dates',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'listing_slug' => [
                    'type' => 'string',
                    'description' => 'The slug of the listing',
                ],
                'start_date' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format',
                ],
                'end_date' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format (optional)',
                ],
            ],
            'required' => ['listing_slug', 'start_date'],
        ],
    ],
    [
        'name' => 'add_to_cart',
        'description' => 'Add a listing to the user\'s cart for booking',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'listing_slug' => ['type' => 'string'],
                'slot_id' => ['type' => 'string', 'description' => 'Availability slot UUID'],
                'quantity' => ['type' => 'integer', 'minimum' => 1],
            ],
            'required' => ['listing_slug', 'slot_id', 'quantity'],
        ],
    ],
    [
        'name' => 'proceed_to_checkout',
        'description' => 'Generate a checkout URL for the user to complete their booking',
        'input_schema' => [
            'type' => 'object',
            'properties' => [],
        ],
    ],
];
```

### Function Handler

```php
class FunctionHandlerService
{
    public function handle(string $functionName, array $arguments, ChatSession $session): mixed
    {
        return match($functionName) {
            'search_listings' => $this->searchListings($arguments),
            'get_availability' => $this->getAvailability($arguments),
            'add_to_cart' => $this->addToCart($arguments, $session),
            'proceed_to_checkout' => $this->proceedToCheckout($session),
            default => throw new \InvalidArgumentException("Unknown function: $functionName"),
        };
    }

    private function searchListings(array $args): array
    {
        // Use RAG service for semantic search
        $listings = app(RAGService::class)->searchListings(
            query: $args['query'],
            locale: $args['locale'] ?? 'en',
            limit: 5
        );

        // Apply additional filters
        if (isset($args['max_price'])) {
            $listings = $listings->filter(fn($l) =>
                ($l->pricing['base'] ?? 0) <= $args['max_price']
            );
        }

        return [
            'results' => $listings->map(fn($listing) => [
                'slug' => $listing->slug,
                'title' => $listing->title,
                'description' => Str::limit($listing->description, 200),
                'price' => $listing->pricing['base'] ?? null,
                'currency' => $listing->pricing['currency'] ?? 'EUR',
                'location' => $listing->location?->name,
            ])->toArray(),
        ];
    }

    private function getAvailability(array $args): array
    {
        $listing = Listing::where('slug', $args['listing_slug'])->firstOrFail();

        $slots = AvailabilitySlot::where('listing_id', $listing->id)
            ->where('start_time', '>=', $args['start_date'])
            ->where('start_time', '<=', $args['end_date'] ?? $args['start_date'])
            ->where('available_quantity', '>', 0)
            ->get();

        return [
            'available_slots' => $slots->map(fn($slot) => [
                'id' => $slot->id,
                'start_time' => $slot->start_time->toISOString(),
                'end_time' => $slot->end_time?->toISOString(),
                'available_quantity' => $slot->available_quantity,
                'price' => $slot->price ?? $listing->pricing['base'],
            ])->toArray(),
        ];
    }

    private function addToCart(array $args, ChatSession $session): array
    {
        // Add item to cart (linked to session_id)
        $cartItem = app(CartService::class)->addItem(
            sessionId: $session->session_id,
            userId: $session->user_id,
            listingSlug: $args['listing_slug'],
            slotId: $args['slot_id'],
            quantity: $args['quantity']
        );

        return [
            'success' => true,
            'cart_item_id' => $cartItem->id,
            'message' => 'Added to cart successfully',
        ];
    }

    private function proceedToCheckout(ChatSession $session): array
    {
        // Generate checkout URL with session context
        $checkoutUrl = route('checkout', [
            'session_id' => $session->session_id,
            'from_chat' => true,
        ]);

        return [
            'checkout_url' => $checkoutUrl,
            'message' => 'Click the link above to complete your booking',
        ];
    }
}
```

---

## Admin Management

### Chat Analytics Dashboard

Filament resource for monitoring chatbot performance.

```php
// app/Filament/Admin/Pages/ChatAnalyticsDashboard.php

class ChatAnalyticsDashboard extends Page
{
    protected static string $view = 'filament.admin.pages.chat-analytics-dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            ChatStatsWidget::class,
            ConversionRateWidget::class,
            CostTrackingWidget::class,
        ];
    }
}
```

### Key Metrics

```php
// app/Filament/Admin/Widgets/ChatStatsWidget.php

class ChatStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $sessions = ChatSession::where('started_at', '>=', $today)->count();
        $messages = ChatMessage::where('created_at', '>=', $today)->count();
        $bookings = Booking::whereHas('chatSession')->where('created_at', '>=', $today)->count();

        return [
            Stat::make('Chat Sessions Today', $sessions)
                ->icon('heroicon-o-chat-bubble-left-right'),

            Stat::make('Messages Sent', $messages)
                ->icon('heroicon-o-chat-bubble-bottom-center-text'),

            Stat::make('Bookings from Chat', $bookings)
                ->description($sessions > 0 ? round(($bookings / $sessions) * 100, 1) . '% conversion' : 'N/A')
                ->icon('heroicon-o-check-circle'),
        ];
    }
}
```

### Chat Session Resource

```php
// app/Filament/Admin/Resources/ChatSessionResource.php

class ChatSessionResource extends Resource
{
    protected static ?string $model = ChatSession::class;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session_id')->searchable(),
                TextColumn::make('user.name')->label('User'),
                TextColumn::make('status')->badge(),
                TextColumn::make('started_at')->dateTime(),
                TextColumn::make('ended_at')->dateTime(),
                TextColumn::make('messages_count')->counts('messages'),
            ])
            ->actions([
                Action::make('view_transcript')
                    ->icon('heroicon-o-document-text')
                    ->modalContent(fn (ChatSession $record) =>
                        view('filament.admin.chat-transcript', ['session' => $record])
                    ),
            ]);
    }
}
```

### Cost Tracking

```php
// Track LLM costs per session
class ChatMessage extends Model
{
    protected $casts = [
        'tokens_input' => 'integer',
        'tokens_output' => 'integer',
        'cost_usd' => 'decimal:6',
    ];

    protected static function booted()
    {
        static::creating(function (ChatMessage $message) {
            // Calculate cost based on Claude pricing
            $inputCost = ($message->tokens_input / 1_000_000) * 3; // $3 per 1M tokens
            $outputCost = ($message->tokens_output / 1_000_000) * 15; // $15 per 1M tokens
            $message->cost_usd = $inputCost + $outputCost;
        });
    }
}

// Dashboard query
$totalCostToday = ChatMessage::whereDate('created_at', today())->sum('cost_usd');
```

---

## Cost Optimization Strategies

### 1. Prompt Caching (90% Discount)

Claude offers prompt caching - repeated context is cached for 5 minutes with 90% discount.

```php
// Cache system prompt and context
$systemPrompt = <<<PROMPT
You are a helpful tourism assistant for Go Adventure, a marketplace for
outdoor experiences in Tunisia. Your role is to help users discover and
book activities.

Available listings:
{$cachedListingsContext}

Use the search_listings tool for semantic search.
PROMPT;

// Send with cache control
$response = $this->anthropic->messages()->create([
    'model' => 'claude-3-5-sonnet-20241022',
    'system' => [
        [
            'type' => 'text',
            'text' => $systemPrompt,
            'cache_control' => ['type' => 'ephemeral'], // Cache this!
        ],
    ],
    'messages' => $conversationHistory,
]);
```

**Savings**: If 80% of requests hit cache, cost drops from $15/1M to $1.5/1M output tokens.

### 2. Response Caching

Cache common responses in Redis:

```php
// Cache FAQ responses
$cacheKey = "chat:faq:" . hash('sha256', $userQuestion);
$cachedResponse = Cache::remember($cacheKey, 3600, function () use ($userQuestion) {
    return $this->llm->generateResponse($userQuestion);
});
```

### 3. Hybrid Search (Pre-filter)

Don't use LLM for simple keyword searches:

```php
public function handleMessage(string $message, ChatSession $session): string
{
    // Detect simple keyword queries
    if ($this->isSimpleSearch($message)) {
        // Use basic search, no LLM needed
        return $this->basicSearch($message);
    }

    // Use LLM for complex queries
    return $this->llmSearch($message, $session);
}

private function isSimpleSearch(string $message): bool
{
    return preg_match('/^(hiking|diving|desert|tour|event)\s+(in|near)\s+\w+$/i', $message);
}
```

### 4. Limit Context Window

Don't send entire conversation history:

```php
// Only send last 10 messages
$recentMessages = $session->messages()
    ->latest()
    ->limit(10)
    ->get()
    ->reverse()
    ->map(fn($msg) => [
        'role' => $msg->role,
        'content' => $msg->content,
    ])
    ->toArray();
```

### 5. Smaller Model for Simple Tasks

Use Claude Haiku for simple queries:

```php
public function selectModel(string $query): string
{
    // Use Haiku for simple questions (10x cheaper)
    if (Str::length($query) < 100 && !str_contains($query, 'book')) {
        return 'claude-3-haiku-20240307'; // $0.25/$1.25 per 1M tokens
    }

    // Use Sonnet for complex queries
    return 'claude-3-5-sonnet-20241022'; // $3/$15 per 1M tokens
}
```

### Cost Projection (10,000 sessions/month)

| Component                  | Usage                          | Cost          |
| -------------------------- | ------------------------------ | ------------- |
| LLM (Claude Sonnet)        | 8 msgs/session, 500 tokens/msg | $220          |
| LLM (with 80% cache hit)   | Same as above                  | $50           |
| Embeddings (OpenAI)        | 1000 new listings/month        | $5            |
| Infrastructure (Redis, DB) | Standard usage                 | $20           |
| **Total (optimized)**      |                                | **$75/month** |

**Cost per booking** (15% conversion): $0.05

---

## Implementation Guide

### Phase 1: Foundation (Week 1)

```bash
# Database
php artisan make:migration create_chat_sessions_table
php artisan make:migration create_chat_messages_table
php artisan make:migration create_listing_embeddings_table
php artisan migrate

# Enable pgvector
DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

# Models
php artisan make:model ChatSession
php artisan make:model ChatMessage
php artisan make:model ListingEmbedding

# Controller
php artisan make:controller Api/V1/ChatController
```

### Phase 2: LLM Integration (Week 2)

```bash
# Install Anthropic SDK
composer require anthropic-ai/client

# Services
php artisan make:service LLM/LLMService
php artisan make:service Chat/ChatService

# Configure
# Add to .env:
ANTHROPIC_API_KEY=sk-ant-...
```

### Phase 3: RAG Implementation (Week 3)

```bash
# Services
php artisan make:service RAG/RAGService
php artisan make:service RAG/EmbeddingService

# Generate embeddings for existing listings
php artisan app:generate-embeddings

# Configure OpenAI
# Add to .env:
OPENAI_API_KEY=sk-...
```

### Phase 4: Function Calling (Week 4)

```bash
# Service
php artisan make:service Chat/FunctionHandlerService

# Test function calling
php artisan tinker
>>> app(ChatService::class)->sendMessage('Find hiking near Tunis');
```

### Phase 5: Frontend (Week 5)

```bash
# Next.js components
mkdir -p apps/web/src/components/chat
touch apps/web/src/components/chat/ChatWidget.tsx
touch apps/web/src/components/chat/ChatPanel.tsx
touch apps/web/src/components/chat/ChatMessage.tsx
```

### Phase 6: Admin Dashboard (Week 6)

```bash
# Filament resources
php artisan make:filament-resource ChatSession --view
php artisan make:filament-page ChatAnalyticsDashboard
php artisan make:filament-widget ChatStatsWidget
```

### Phase 7: Optimization (Week 7)

```bash
# Implement caching
# Tune prompts
# Test cost optimizations
# Mobile responsive UI
```

---

## Success Metrics (3-month targets)

| Metric          | Target                 | Actual | Status  |
| --------------- | ---------------------- | ------ | ------- |
| Adoption Rate   | 30% visitors open chat | -      | Pending |
| Engagement      | 8+ messages/session    | -      | Pending |
| Conversion      | 12% chat → booking     | -      | Pending |
| Satisfaction    | 80% positive feedback  | -      | Pending |
| Response Time   | <2s average            | -      | Pending |
| Cost Efficiency | <$0.20/booking         | -      | Pending |

---

## Security & Privacy

### PII Handling

```php
// Detect and redact PII before logging
class PIIRedactionService
{
    public function redact(string $content): string
    {
        // Credit cards
        $content = preg_replace('/\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}/', '[REDACTED CARD]', $content);

        // Email addresses
        $content = preg_replace('/[\w\.-]+@[\w\.-]+\.\w+/', '[REDACTED EMAIL]', $content);

        // Phone numbers
        $content = preg_replace('/\+?\d{10,}/', '[REDACTED PHONE]', $content);

        return $content;
    }
}
```

### Data Retention

- **Guest sessions**: 30 days
- **Authenticated sessions**: 1 year
- **GDPR-compliant deletion**: User can delete chat history

---

**Last Updated**: 2025-12-23
**Version**: 1.0 (Planning)
**Status**: Not yet implemented - See implementation plan for timeline
