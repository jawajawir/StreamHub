# Security Documentation

## Overview

StreamHub follows a **secure-by-default** approach. All protections are server-side. Client-side JavaScript is used only for UX enhancement, never for access control.

---

## Authentication

### Password Hashing
- `password_hash()` with `PASSWORD_DEFAULT` (Argon2id where available, bcrypt fallback)
- `password_verify()` for comparison — no custom hashing

### Login Protection
- **Rate limiting**: Frontend login allows 5 failed attempts per 15-minute window per identifier; admin allows 4
- **Login attempts table**: Every attempt (success/failure) is recorded in `login_attempts` with hashed IP and UA
- **Lockout response**: After threshold, returns generic "too many attempts" (no timing oracle)
- **Account enumeration prevention**: Login errors use generic "Invalid credentials" message

### Token Lifecycle
- Email verification: random 32-byte token, SHA-256 hashed in DB, 24h expiry, single-use
- Password reset: random 32-byte token, SHA-256 hashed in DB, 1h expiry, single-use
- Remember-me: not yet implemented (session-only auth currently)

### Session Security
- `HttpOnly` cookies (JavaScript cannot read session cookie)
- `Secure` flag when HTTPS is enabled
- `SameSite=Lax` (configurable to Strict)
- Session ID regenerated after login
- Configurable session lifetime (default: 2 hours)
- Admin and frontend sessions use the same PHP session but separate `_admin_id` / `_user_id` keys

---

## CSRF Protection

Every state-changing request (POST/PUT/PATCH/DELETE) requires a valid CSRF token.

- Token stored in session, rotated on login
- Submitted via hidden `_csrf` field or `X-CSRF-TOKEN` header
- Validation failure: HTTP 419 response + `security_events.csrf_failed` log
- Token never exposed in error responses

Protected actions include: login, register, forgot/reset password, logout, profile update, like/dislike, favorite, report, contact, membership request, all admin CRUD, settings save, API key update, import, sync, publish, ban, etc.

---

## Authorization

### Admin Panel
- Only `admin_users` with `role = 'superadmin'` and `status = 'active'` can access `/admin/*`
- `AdminAuthMiddleware` redirects unauthorized requests to admin login
- No multi-role system — single superadmin only

### Frontend Access Control
- `AccessRuleService::decideContentAccess()` checks: lifecycle_status, visibility_level, access_level vs user's membership_tier
- Guest users cannot access member features (server-side enforcement, not JS hiding)
- Premium/VIP content returns appropriate "require login" or "require membership" state
- Feature toggles control which actions are available — disabled features return 404 on direct POST

---

## SQL Injection Prevention

- **All queries use PDO prepared statements** with parameter binding
- Sort columns are whitelisted (never from raw user input)
- Pagination has a hard cap (max 60 items per page)
- Search query length capped at 100 characters
- No raw string interpolation in SQL anywhere in the codebase

---

## XSS Prevention

### Output Escaping
- `e()` helper (htmlspecialchars with ENT_QUOTES | ENT_SUBSTITUTE) used in all view templates
- All user-supplied data escaped by default

### HTML Sanitization (Pages Manager)
- Admin-entered page content passes through `PagesManagerController::sanitizeHtml()`
- Strips: `<script>`, `<style>`, `<iframe>`, `<object>`, `<embed>`
- Strips: all `on*` event handler attributes
- Strips: `javascript:` URLs in href/src
- Allows: safe HTML tags (p, br, strong, em, a, ul, ol, li, h1-h6, table, img, etc.)

### Content Security
- Imported metadata (title, description) is always escaped on output
- Comments (when enabled) will use the same sanitizer
- No raw HTML rendering of user-submitted content

---

## Iframe / Embed Security

### Domain Allowlist
- All iframe/embed URLs validated against a configurable allowlist
- Default: 18+ Doodstream domains (dood.li, doodstream.com, d000d.com, etc.)
- Admin can add extra domains via Settings → Security → iframe allowlist
- Rejected domains are logged as `security_events.invalid_embed_domain`

### Blocked Protocols
- `javascript:`, `data:`, `vbscript:`, `file:` — all rejected
- Only `http://` and `https://` URLs accepted
- Relative URLs rejected

### Iframe Attributes
- `referrerpolicy="strict-origin-when-cross-origin"` on Doodstream iframes
- `allow="autoplay; fullscreen; encrypted-media; picture-in-picture"`

---

## Doodstream API Key

- Stored in `api_credentials.encrypted_value` using AES-256-GCM encryption
- Encryption key is the project's `ENCRYPTION_KEY` from `.env`
- Only the masked value (`****...last4`) is rendered in admin UI
- Full key never appears in: HTML source, JavaScript, logs, error messages, audit logs
- Test connection and sync operations happen server-side via cURL
- Only superadmin can view/update/test the key

---

## Direct Media Protection

For Direct MySQL content (Video.js/HLS.js player):

- Playback URLs are **signed and expiring** (default: 1 hour TTL)
- Token contains: content_id, user_id, expiry timestamp, random nonce
- Token encrypted with AES-256-GCM (same ENCRYPTION_KEY)
- Expired tokens return 403
- Invalid tokens logged as `security_events.hotlink_blocked`
- Content access-level check is repeated at playback time (defense in depth)

---

## Rate Limiting

Database-backed sliding-window rate limiter (`rate_limit_hits` table).

| Endpoint | Window | Max Hits |
|----------|--------|----------|
| Frontend login | 15 min | 5 per identifier |
| Admin login | 15 min | 4 per identifier |
| Registration | 1 hour | 5 per IP |
| Forgot password | 1 hour | 5 per IP |
| Search | 1 min | 30 per IP |
| Report video | 1 hour | 5 per content per IP |
| Membership request | 1 hour | 5 per IP |
| Cron/queue endpoints | 1 min | 60 per IP |
| Installer | 1 min | 60 per IP |

Blocked requests: HTTP 429 + `security_events.rate_limited` log.

---

## Audit Logging

All important admin actions write to `audit_logs`:

- Admin login/logout
- User membership change
- User ban/status change
- Content create/update/delete/publish/archive
- Settings save
- Feature toggle change
- API key update/test
- Page update/publish
- Taxonomy CRUD
- Membership request approve/reject

Sensitive fields (password_hash, API keys, tokens) are redacted as `[REDACTED]` in before/after JSON.

---

## Security Events

High-priority security violations write to `security_events`:

| Event Type | Trigger |
|------------|---------|
| `csrf_failed` | Invalid/missing CSRF token on POST |
| `rate_limited` | Rate limit exceeded |
| `login_blocked` | Too many failed logins or banned status |
| `invalid_embed_domain` | Iframe URL not in allowlist |
| `hotlink_blocked` | Invalid/expired playback token |
| `api_auth_failed` | Invalid cron/queue token |
| `suspicious_import` | Malformed import file |

---

## File Protection

### .htaccess Rules
- Root `.htaccess`: rewrites to `/public`, blocks access to `/app`, `/config`, `/database`, `/storage`, `/routes`, `/resources`, `/cron`, `/queue`
- `/public/.htaccess`: front controller rewrite, security headers, disable directory listing
- `/storage/.htaccess`: deny all
- `/config/.htaccess`: deny all
- `/database/.htaccess`: deny all
- `/cron/.htaccess`: deny all (CLI access only)
- `/queue/.htaccess`: deny all (CLI access only)

### Security Headers (set in /public/.htaccess)
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: geolocation=(), microphone=(), camera=()`

### Additional headers (set in App::boot())
- `Strict-Transport-Security` when HTTPS detected

---

## Privacy

- IP addresses stored as HMAC-SHA256 hashes (using APP_KEY), never raw
- User-agent stored as HMAC-SHA256 hash
- Age gate uses hashed IP + UA — no raw PII persisted
- Users can: clear watch history, pause history tracking, set favorites to private, logout all devices
- No third-party tracking built-in (admin can add via External Scripts Manager, scoped by page/membership)
