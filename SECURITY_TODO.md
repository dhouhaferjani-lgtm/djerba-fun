# Security TODO - Go Adventure

> Last updated: 2026-01-15
> Status: Phase 1 Complete, Phase 2-3 Pending

---

## Completed (Phase 1)

- [x] XSS Prevention - DOMPurify sanitization
- [x] Rate limiting on auth endpoints
- [x] Security headers (X-Frame-Options, etc.)
- [x] Sanctum token expiration (7 days)
- [x] Payment secrets not revealable
- [x] Delete confirmations on critical resources
- [x] PII section collapsed in BookingResource

---

## Pending - MEDIUM RISK (Phase 2)

### 1. Filament Policy Authorization

**Files:** All 16 resources in `app/Filament/Admin/Resources/`

Add to each resource:

```php
public static function canAccess(): bool
{
    return auth()->user()?->hasRole('admin') ?? false;
}
```

**Risk:** Could lock out admins if role check wrong - test carefully

---

### 2. IP Whitelist Enforcement

**File:** `app/Http/Middleware/PartnerAuthMiddleware.php`

Add after authentication:

```php
if (!$partner->isIpWhitelisted($request->ip())) {
    return response()->json(['error' => 'IP not whitelisted'], 403);
}
```

**Risk:** Could block legitimate partners - ensure IPs configured first

---

### 3. CORS Environment Variables

**File:** `config/cors.php`

Change to:

```php
'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000')),
```

Then set in `.env`:

```
CORS_ALLOWED_ORIGINS=https://dev.go-adventure.net,https://go-adventure.net
```

**Risk:** If misconfigured, blocks frontend requests

---

## Pending - HIGH RISK (Phase 3 - Separate PR)

### 4. Auth Token Migration (localStorage → httpOnly Cookies)

**Files:**

- `apps/web/src/lib/api/client.ts` (7 locations)
- `app/Http/Controllers/Api/AuthController.php`

**Changes needed:**

1. Backend: Set httpOnly cookie on login response
2. Frontend: Remove all `localStorage.setItem('auth_token', ...)`
3. Frontend: Use `credentials: 'include'` in fetch calls
4. Backend: Read token from cookie instead of Authorization header

**Risk:** HIGH

- All existing sessions invalidated
- Coordinated frontend + backend changes
- Thorough testing of login/logout/refresh flows required

**Recommendation:** Dedicated PR with full QA testing

---

## Infrastructure Items

### 5. Session Encryption

Add to server `.env`:

```
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
```

### 6. Git History Cleanup (Optional)

Remove committed `.env` from history:

```bash
git filter-branch --force --index-filter \
  'git rm --cached --ignore-unmatch apps/laravel-api/.env' \
  --prune-empty --tag-name-filter cat -- --all
git push origin lastshot --force
```

---

## Verification Tests

After implementing Phase 2:

- [ ] Run https://securityheaders.com on dev.go-adventure.net
- [ ] Test Partner API from non-whitelisted IP → should return 403
- [ ] Non-admin user cannot access admin panel
- [ ] Attempt 6+ failed logins → should be rate limited

After implementing Phase 3:

- [ ] Check DevTools → Application → Local Storage → no auth tokens
- [ ] Check DevTools → Application → Cookies → httpOnly cookie present
- [ ] Login/logout flow works correctly
- [ ] Token refresh works correctly
