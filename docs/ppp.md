# Purchasing Power Parity (PPP) Pricing – Product & UX Specification

## Purpose

This document defines how Purchasing Power Parity (PPP) pricing is implemented on the platform, including:

- Pricing logic and decision rules
- UI/UX hints and disclosures
- Checkout behavior
- Edge cases and scenarios
- Legal-safe disclosure practices

The goal is to ensure **fair regional pricing** while maintaining **clarity, trust, and compliance** with consumer protection standards.

---

## Core Principles

1. **Single Visible Price**
   - At any given moment, the user sees only one price.
   - No comparison with other regions.
   - No crossed-out or “discount” messaging.

2. **No Surprise at Payment**
   - The final price must be clear **before** payment confirmation.
   - Any price change must be explicitly explained.

3. **Final Authority: Billing Address**
   - The billing address provided at checkout is the **final source of truth**.
   - All other signals (IP, locale, currency) are provisional.

---

## Pricing Decision Hierarchy

Pricing is determined using the following priority order:

1. **Billing Address (Checkout – Final)**
2. Payment country inferred from payment provider (fallback)
3. User-selected country (if available)
4. IP-based geolocation (initial browsing only)

> ⚠️ The price charged is ALWAYS based on the billing address country.

---

## User Journey & UX Behavior

### 1. Browsing & Discovery Phase

**Inputs used:**

- IP geolocation
- Browser locale
- Optional country selector (if implemented)

**Behavior:**

- Display price adapted to the detected or selected country.
- Display price in the corresponding currency.
- No disclosure about other prices.

**UI Hint (Optional but Recommended):**
Small, non-intrusive text near the price:

> _Price shown in TND (based on your location)_

or:

> _Pricing adapted to your region_

This hint:

- Is informational, not promotional
- Does not mention discounts or differences
- Is not required on every page

---

### 2. Pre-Checkout (Cart / Summary)

**Behavior:**

- Price remains unchanged from browsing phase.
- Currency remains consistent.
- No recalculation at this stage.

**If a country selector exists:**

- Changing the country updates prices immediately.
- The updated price is shown clearly.
- No retroactive “price change” messaging.

---

### 3. Checkout Phase (Critical)

#### Data Collected:

- Billing address (mandatory)
- Payment method
- Payment country (via provider, if available)

#### Price Validation:

- The system recalculates the price **based on billing address country**.
- This price becomes the **final authoritative price**.

---

## Price Change Scenarios at Checkout

### Case A – No Price Change (Ideal)

**Condition:**

- Browsing country = Billing address country

**Behavior:**

- Price remains identical throughout the journey.
- No disclosure needed.

---

### Case B – Price Changes at Checkout (Mandatory Disclosure)

**Condition:**

- Billing address country ≠ browsing / IP country
- Example: VPN usage, traveler booking for another country, expat card

**Behavior:**

- Price updates immediately after billing address is entered.
- A clear notice is displayed **before payment confirmation**.

**Required Notice (Example):**

> _Your final price has been adjusted based on your billing country and local purchasing power._

**Rules:**

- Notice must be visible.
- Plain language only.
- Shown before “Pay now” / payment confirmation.
- No implication of fault or misuse.

---

## What Is NOT Allowed

The following patterns are explicitly prohibited:

- Showing multiple regional prices simultaneously
- Showing crossed-out prices or “you saved X” messaging
- Referring to “cheaper countries” or “local discounts”
- Silent price changes after payment confirmation
- Post-payment disclosure of price adjustments

---

## Edge Cases & Defined Behavior

### VPN Usage

- VPN affects browsing price only.
- Billing address overrides VPN pricing at checkout.
- Disclosure shown only if price changes.

### Expats / Travelers

- Billing address determines price.
- No special handling needed.
- System behavior is consistent and neutral.

### Multi-Currency Cards

- Currency conversion is handled by the payment provider.
- PPP price is still based on billing country.
- Exchange rate differences are not disclosed as price changes.

### Manual Country Selection (If Enabled)

- User-selected country affects browsing price.
- Billing address still overrides at checkout.
- Disclosure applies if mismatch occurs.

---

## Legal & Policy Disclosure (Footer / Help Page)

A single global disclosure is sufficient.

**Recommended wording:**

> _Prices may vary depending on country, currency, and billing address. We adapt pricing to ensure fair access across regions. The final price is confirmed at checkout._

This disclosure:

- Protects against legal complaints
- Avoids UX clutter
- Is standard across SaaS and booking platforms

---

## Summary (TL;DR)

- Show one price at a time.
- No disclosure during browsing unless price changes later.
- Billing address is the final authority.
- Any checkout price change must be explained before payment.
- Use neutral, fairness-based language.
- Avoid “discount” framing entirely.

---

## Status

This specification defines the **approved and final behavior** for PPP pricing implementation.
