# Manual QA Checklist

Every item must pass before a release is considered production-ready.

---

## Installer

- [ ] Visit `/install` on a fresh database — wizard loads with premium UI
- [ ] Requirements step shows real PHP version and extension status
- [ ] Database step rejects invalid credentials with error message
- [ ] Database step accepts valid credentials and proceeds
- [ ] App step saves site name, URL, timezone to `.env`
- [ ] Migrate step creates all tables (check `schema_migrations` count)
- [ ] Migrate step seeds default data (membership_plans, feature_toggles, pages, etc.)
- [ ] Admin step rejects password < 10 characters
- [ ] Admin step creates superadmin in `admin_users` table
- [ ] Cron step displays correct URL and CLI command with token
- [ ] Verify step confirms all checks pass
- [ ] `storage/install.lock` is created after completion
- [ ] Revisiting `/install` after lock shows "Installer locked" page
- [ ] CSRF is validated on every POST step of the installer

---

## Admin Authentication

- [ ] `/admin/login` renders login form
- [ ] Invalid credentials show generic error (no account enumeration)
- [ ] Valid credentials redirect to dashboard
- [ ] Session regenerated after login (check session ID change)
- [ ] After 4 failed attempts (same identifier), subsequent attempts are blocked
- [ ] Blocked attempt logged in `security_events` as `login_blocked`
- [ ] Logout (POST with CSRF) destroys admin session
- [ ] Accessing `/admin` without session redirects to login
- [ ] Audit log records `admin.login` and `admin.logout`

---

## Frontend Authentication

- [ ] `/login` renders sign-in form
- [ ] `/register` renders registration form (when `registration` toggle is ON)
- [ ] `/register` returns "Registration closed" page when toggle is OFF
- [ ] Registration validates: username format, email, password min 10, confirmation match
- [ ] Registration rejects duplicate username/email
- [ ] Registration creates user with `status = 'pending_email'`
- [ ] Email verification token generated and queued in `mail_queue`
- [ ] Visiting `/verify-email/{token}` activates user (`status = 'active'`)
- [ ] Expired/used token shows "invalid" page
- [ ] Login works for active users
- [ ] Login rejects suspended/banned users with generic message
- [ ] After 5 failed login attempts, blocked for 15 minutes
- [ ] `/forgot-password` always shows "if email exists, link sent" (no enumeration)
- [ ] Password reset token created in `auth_tokens` with 1h expiry
- [ ] `/reset-password/{token}` renders form for valid token
- [ ] Password reset updates hash, marks token used, revokes all sessions
- [ ] Logout (POST) destroys user session

---

## Age Gate

- [ ] First visit as guest triggers redirect to `/age-gate`
- [ ] Age gate page renders with Tailwind premium UI
- [ ] Accepting age gate sets cookie + session + DB record (hashed IP)
- [ ] Subsequent visits skip age gate (cookie/DB check passes)
- [ ] Age gate expiry configurable from Settings (default 30 days)
- [ ] Logged-in users who already passed are not re-prompted
- [ ] Bypass paths work: `/login`, `/register`, `/page/*`, `/sitemap.xml`, `/robots.txt`

---

## Guest Restrictions

- [ ] Guest cannot POST to `/watch/{id}/like` — returns 401 or redirects to login
- [ ] Guest cannot POST to `/watch/{id}/favorite` — same
- [ ] Guest cannot access `/account` — redirects to login
- [ ] Guest cannot access `/account/favorites` — redirects to login
- [ ] Guest countdown (5s default) displays before player activates
- [ ] Logged-in user does NOT see countdown

---

## Membership

- [ ] `/membership` page shows Free/Premium/VIP comparison
- [ ] Membership request form requires name + email + plan selection
- [ ] Submitting request creates row in `membership_requests` with status `new`
- [ ] Rate limited: max 5 requests per hour per IP
- [ ] Admin panel → Membership Requests shows the request
- [ ] Admin can approve → user's `membership_tier` updated + `user_memberships` row created
- [ ] Admin can reject → status set to `rejected`
- [ ] Approved user sees updated tier in account dashboard
- [ ] Expired membership (past `expires_at`) → cron downgrades to `free`

---

## Video Manager (Admin)

- [ ] `/admin/videos` lists content from database with search/filter/pagination
- [ ] Empty state shown when no content exists
- [ ] Create form renders with source selection (Direct MySQL / Doodstream)
- [ ] Direct MySQL: entering a .mp4 URL creates `media_assets` row + `content_sources` link
- [ ] Direct MySQL: entering a .m3u8 URL creates HLS asset type
- [ ] Doodstream: entering file_code builds embed URL from config base
- [ ] Doodstream: entering full iframe URL validates against allowlist
- [ ] Invalid iframe domain shows error + logs `security_events.invalid_embed_domain`
- [ ] Slug auto-generated from title; conflicts resolved with suffix
- [ ] Taxonomy (categories/tags/performers) saved to pivot tables
- [ ] Lifecycle status change from draft → published sets `published_at`
- [ ] Slug change records old slug in `slug_history` for 301 redirect
- [ ] Archive/delete action works (soft delete sets `deleted_at`)
- [ ] Audit log written for create/update/status-change/delete

---

## Watch Page

- [ ] `/watch/{slug}` renders for published content
- [ ] Non-existent slug returns 404
- [ ] Old slug (from `slug_history`) triggers 301 redirect to new slug
- [ ] Doodstream source renders iframe with validated embed URL
- [ ] Direct MySQL source renders Video.js player
- [ ] HLS source renders Video.js + HLS.js
- [ ] `access_level = 'registered'` + guest → shows "Sign in to watch" state
- [ ] `access_level = 'premium'` + free user → shows "Upgrade membership" state
- [ ] `access_level = 'vip'` + premium user → shows "VIP only" state
- [ ] View count incremented on successful access
- [ ] `content_views` row created
- [ ] Like/dislike buttons work for logged-in users (toggle behavior)
- [ ] Favorite button works (toggle on/off)
- [ ] Report form submits to `reports` table
- [ ] Related videos section populated from same tags/studio
- [ ] Tags and categories displayed as links
- [ ] Performers displayed with links to performer page

---

## CSRF Protection

- [ ] All POST forms include `_csrf` hidden field
- [ ] Submitting POST without token returns 419
- [ ] CSRF failure logged in `security_events` as `csrf_failed`
- [ ] Token rotates on login (old token from pre-login session is invalid)
- [ ] AJAX requests can use `X-CSRF-TOKEN` header as alternative

---

## Doodstream API Manager

- [ ] API key input field is type="password" (masked entry)
- [ ] Saving key encrypts it (check `api_credentials.encrypted_value` is not plaintext)
- [ ] Displayed key is masked (e.g., `abcd****wxyz`)
- [ ] Test connection button sends real HTTP request to Doodstream API
- [ ] Successful test updates `last_test_status = 'success'`
- [ ] Failed test updates `last_test_status = 'failed'` with message
- [ ] API key never appears in page source, JavaScript, or network requests
- [ ] Audit log written for key save and test

---

## Settings Manager

- [ ] Settings form loads current values from `settings` table
- [ ] Saving settings updates DB (verify with SELECT)
- [ ] Feature toggle changes affect runtime (e.g., disable `registration` → `/register` returns disabled page)
- [ ] Age gate enabled/disabled setting controls middleware behavior
- [ ] Guest countdown setting changes the seconds shown on watch page
- [ ] Iframe allowlist extra domains accepted and used in validation

---

## Pages Manager

- [ ] Page list shows all pages from `pages` table
- [ ] Edit form loads body content
- [ ] Saving strips `<script>`, `on*` handlers, `javascript:` URLs
- [ ] Page revision created on save
- [ ] Publish toggle changes status (published ↔ draft)
- [ ] Published pages accessible at `/page/{slug}`
- [ ] Draft pages return 404 on frontend

---

## Sitemap & Robots

- [ ] `/sitemap.xml` returns valid XML with published content URLs
- [ ] `/robots.txt` returns rules from `robots_rules` table
- [ ] Disallow /admin and /account present in robots output

---

## Cron & Queue

- [ ] `/cron/run?token=WRONG` returns 401 + security event logged
- [ ] `/cron/run?token=CORRECT` returns JSON with job results
- [ ] CLI `php cron/run.php --token=CORRECT` outputs JSON
- [ ] Membership expiry job downgrades expired users
- [ ] Queue drain processes pending jobs
- [ ] Health check row written to `system_health_checks`
- [ ] File lock prevents concurrent cron runs
- [ ] `cron_schedules.last_run_at` updated after successful run

---

## Admin Dashboard

- [ ] KPI cards show real counts from database (not hardcoded)
- [ ] Views chart renders with Chart.js using real `content_views` data
- [ ] Membership distribution doughnut uses real user counts
- [ ] Top content list from actual `view_count` ordering
- [ ] Doodstream sync card shows real credential status
- [ ] Queue card shows real pending/failed counts
- [ ] Cron schedules show real `last_status` and `last_run_at`
- [ ] Audit activity shows real entries (or empty state if none)
- [ ] Security events shows real entries (or empty state if none)
- [ ] All empty states are honest "no data" messages, not fake numbers

---

## Security

- [ ] `.env` not accessible via browser (`/storage/.env`, `/.env` → 403)
- [ ] `/storage/logs/` not accessible via browser
- [ ] `/config/` not accessible via browser
- [ ] `/database/` not accessible via browser
- [ ] `/app/` not accessible via browser
- [ ] Directory listing disabled (no file index shown)
- [ ] SQL injection test: `' OR 1=1 --` in search/filter does not break queries
- [ ] XSS test: `<script>alert(1)</script>` in content title is escaped in output
- [ ] Admin panel cannot be accessed by frontend user session
- [ ] Frontend user cannot upgrade own membership via direct POST
- [ ] Production errors show generic "Something went wrong" (no stack trace)
- [ ] `error_logs` table captures the full exception detail

---

## Mobile / Responsive

- [ ] Frontend renders correctly on 375px width (iPhone SE)
- [ ] Mobile bottom navigation present and functional
- [ ] Search drawer opens on mobile
- [ ] Content grid switches to 1-2 columns on small screens
- [ ] Admin sidebar collapses on mobile (hamburger menu)
- [ ] Admin tables scroll horizontally on narrow screens
- [ ] Player is full-width on mobile
- [ ] Age gate is responsive
- [ ] Installer is responsive
