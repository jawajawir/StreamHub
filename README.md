# StreamHub

Modern PHP + MySQL adult/tube streaming CMS. Two strictly-separated areas:

- **Public frontend** at `/`
- **Admin panel** at `/admin` (single role: superadmin)

Stack:

- PHP 8.0+
- MySQL 8+ (InnoDB / utf8mb4)
- PDO prepared statements only
- Tailwind CSS, Alpine.js, Lucide Icons (via CDN by default)
- Chart.js, Swiper.js, Video.js, HLS.js for the frontend / admin
- Doodstream native iframe for Doodstream-sourced content

Built for shared hosting (Apache/LiteSpeed + cPanel/DirectAdmin) without requiring SSH, Composer, Node.js, Redis, or supervisor on production.

---

## What's in this build

This is the **foundation release**. Every module shipped here is fully real per the spec's "no partial delivery" rule (route + controller + service + repository + DB + validation + CSRF + auth + audit log + UI state + QA).

**Shipped (production-complete):**

- Premium multi-step installer (`/install`)
- Auth: superadmin login, frontend register/login/forgot/reset/verify-email with real token lifecycle
- Server-side age gate (cookie + session + hashed-IP record)
- Settings Manager + Feature Toggle Manager (real save/read)
- User Manager (search/filter, ban, status update, manual membership update)
- Membership manual request flow (frontend form → admin inbox → approve/reject)
- Account dashboard, watch history (clear/pause), favorites, privacy preferences, logout-all-devices
- Video Manager with Direct MySQL & Doodstream sources, lifecycle/access/visibility, taxonomy, slug history
- Categories, Tags, Performers, Studios, Series CRUD with content-count caching
- Watch page with real player routing: Doodstream iframe (allowlist-validated) or Video.js+HLS.js (signed playback URL)
- Doodstream API Manager (encrypted API key, masked display, real test connection)
- Pages Manager (HTML sanitizer, page revisions, publish toggle)
- Admin dashboard with **real** Chart.js series (no fake stats)
- Sitemap.xml + robots.txt generated from DB
- CSRF on every state-changing route, sliding-window rate limiter, audit logs, security events
- Token-protected cron runner (`/cron/run`) with file lock, queue drain, membership expiry sweep, health-check write
- 12 SQL migrations covering the full SQL Schema v1 + counter columns + `doodstream_sync_logs`

**Hidden behind feature toggles (default OFF) until shipped:**

`comments`, `playlists`, `performer_follow`, `rss_feed`, `newsletter`, `mail_broadcast`, `ads_vast_vpaid`, `import_csv`, `import_json`, `search_autocomplete`. The matching admin sidebar entries are not rendered, and direct POSTs return 404, per spec §27.

---

## Install

1. Upload the project to your host (or set the document root to `/public`).
2. Create an empty MySQL database.
3. Visit `https://your-domain.com/install` and follow the wizard:
   - Welcome → Requirements → Database → App → Migrate → Admin → Cron → Verify → Done.
4. The installer writes `.env`, generates `APP_KEY`, `ENCRYPTION_KEY`, and `CRON_TOKEN`, runs all migrations + seeds, creates your superadmin, then drops `storage/install.lock` to lock itself.
5. Sign in at `https://your-domain.com/admin/login`.

Detailed deployment notes: see [`docs/installation.md`](docs/installation.md).

## Cron

Run **every minute** on shared hosting:

```
*/1 * * * * curl -fsS "https://your-domain.com/cron/run?token=YOUR_CRON_TOKEN" >/dev/null
```

Or via PHP CLI when available:

```
*/1 * * * * php /home/USER/public_html/cron/run.php --token=YOUR_CRON_TOKEN
```

The runner expires due memberships, drains pending queue jobs, and writes a health-check row per pass. See [`docs/cron.md`](docs/cron.md).

## Security

Highlights — full notes in [`docs/security.md`](docs/security.md):

- Argon2id (bcrypt fallback) password hashing
- CSRF on every POST + 419 page on mismatch + `security_events.csrf_failed` log
- Iframe domain allowlist for Doodstream embeds + admin-extendable extra domains
- Doodstream API key stored AES-256-GCM-encrypted; only masked value rendered
- Direct MySQL playback via signed, expiring tokens (no permanent public URLs for restricted content)
- IP/UA hashed (`hash_hmac` with APP_KEY) — raw IPs never persisted
- Per-route + per-identifier rate limiting (frontend login, admin login, register, forgot-password, search, reports, embed, contact, membership requests, cron)
- File protection via `.htaccess` in project root, `/public`, `/storage`, `/app`, `/config`, `/database`, `/routes`, `/resources`
- HTML sanitizer for admin-edited static pages strips `<script>`, `<style>`, `<iframe>`, `<object>`, `<embed>`, `on*` handlers and `javascript:` URLs
- Production exception handler logs to `storage/logs/app.log` and `error_logs` — stack traces never leak

## Manual QA

[`docs/qa-checklist.md`](docs/qa-checklist.md) — every item must pass before declaring release.

---

## Repository layout

```
/app
  /Core              # MVC primitives (Router, Request, Response, Database, ...)
  /Middleware        # AdminAuth, UserAuth, Csrf, RateLimit, AgeGate, Maintenance, FeatureToggle
  /Services          # SettingService, FeatureToggleService, AuditService, SecurityService,
                     # RateLimitService, AgeGateService, AccessRuleService, MembershipService,
                     # PlayerService, DoodstreamService
  /Repositories      # ContentRepository
  /Controllers
    /Frontend
    /Admin
  /Installer         # Self-contained multi-step installer
/config              # app/database/security/doodstream/mail
/database
  /migrations        # 0001..0012 SQL files
  /seeds             # Default plans, ad placements, feature toggles, etc.
/public              # Front controller (index.php), assets, .htaccess
/resources/views
  /frontend
  /admin
  /installer
/routes              # web.php / admin.php / api.php
/storage             # cache, logs, uploads, imports, exports, tmp - all denied via .htaccess
/cron                # cron entrypoint (calls public/index.php /cron/run)
/queue               # placeholder for future dedicated worker
/docs                # installation / cron / security / qa-checklist / architecture
```

---

## License

This codebase is provided as-is under your project's license. The operator is responsible for legal compliance in their jurisdiction (DMCA, age verification, 2257 records, etc.).
