# Installation Guide

## Requirements

| Requirement | Minimum | Recommended |
|-------------|---------|-------------|
| PHP | 8.0 | 8.2+ |
| MySQL / MariaDB | 8.0 / 10.6 | 8.0+ |
| Web server | Apache 2.4 / LiteSpeed | with mod_rewrite |
| PHP Extensions | pdo, pdo_mysql, openssl, mbstring, json, fileinfo, curl, session | + intl |
| Disk space | 50 MB (code) | + storage for uploads |

## Hosting Compatibility

StreamHub is built for **shared hosting** (cPanel, DirectAdmin, Plesk). No SSH, Composer, Node.js, Redis, or supervisor required on production.

---

## Step-by-step

### 1. Upload files

Upload the entire project to your hosting. Two layout options:

**Option A — Document root points to `/public`** (preferred):
```
/home/user/streamhub/        ← project root (NOT web-accessible)
/home/user/streamhub/public/ ← document root
```

**Option B — Everything in `public_html`** (shared hosting default):
```
/home/user/public_html/      ← all files here
```

The root `.htaccess` rewrites all requests into `/public/index.php` and blocks access to sensitive directories (`/app`, `/config`, `/database`, `/storage`, `/routes`, `/resources`, `/cron`, `/queue`).

### 2. Create a MySQL database

Via cPanel → MySQL Databases (or equivalent):
- Create database, e.g. `streamhub_db`
- Create user, e.g. `streamhub_user`
- Grant ALL PRIVILEGES on the database to the user
- Note: charset must be `utf8mb4`

### 3. Run the installer

Visit: `https://your-domain.com/install`

The wizard has 9 steps:

1. **Welcome** — overview and adult content compliance warning
2. **Requirements** — PHP version, extensions, writable folders
3. **Database** — enter credentials, test connection
4. **App setup** — site name, URL, timezone, admin path, HTTPS toggle
5. **Migrate** — creates all 60+ tables and seeds default data
6. **Superadmin** — create your admin account (min 10-char password)
7. **Cron** — copy the cron command/URL for your hosting
8. **Verify** — final sanity checks
9. **Done** — installer locks itself via `storage/install.lock`

### 4. Configure cron

See [cron.md](cron.md) for full details.

Quick setup (cPanel → Cron Jobs → every minute):
```
*/1 * * * * curl -fsS "https://your-domain.com/cron/run?token=YOUR_CRON_TOKEN" >/dev/null 2>&1
```

### 5. Post-install

1. Sign in at `/admin/login`
2. Go to **Settings** → verify site name, age gate, player countdown
3. Go to **API Manager** → paste your Doodstream API key (stored encrypted)
4. Go to **Pages** → edit and publish DMCA, Privacy, Terms pages
5. Go to **Video Manager** → add your first content (Direct MySQL URL or Doodstream file_code)
6. Go to **Categories / Tags** → create taxonomy for your content
7. Visit the public site and verify age gate, watch page, membership page

---

## Folder Permissions

| Path | Permission | Purpose |
|------|-----------|---------|
| `storage/cache/` | 775 | Template/query cache |
| `storage/logs/` | 775 | App + error logs |
| `storage/uploads/` | 775 | User/admin uploads |
| `storage/imports/` | 775 | JSON/CSV import files |
| `storage/exports/` | 775 | Generated exports |
| `storage/tmp/` | 775 | Lock files, temp processing |
| `.env` | 640 | Environment config (not world-readable) |

All `storage/` subdirectories are protected by `.htaccess` (deny all).

---

## Environment Variables

Generated automatically by the installer. Key variables in `.env`:

```
APP_KEY=...          # Base64-encoded 32-byte key for HMAC operations
ENCRYPTION_KEY=...   # Base64-encoded 32-byte key for AES-256-GCM encryption
CRON_TOKEN=...       # Random token for authenticating cron/queue requests
DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASS
ADMIN_PATH=admin     # URL segment for admin panel (change for obscurity)
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| 500 error after install | Check `storage/logs/app.log` for details |
| Installer shows after install | Verify `storage/install.lock` exists |
| Can't write .env | Create `.env` manually from `.env.example`, set permissions to 640 |
| mod_rewrite not working | Ensure `AllowOverride All` in Apache vhost config |
| Session not persisting | Check `session.save_path` is writable |
| Age gate loops | Clear browser cookies, verify `age_gate_records` table exists |

---

## Upgrading

1. Back up your database and `.env` file
2. Upload new files (overwrite all except `.env` and `storage/`)
3. Visit `/install` — if new migrations are needed, the installer will detect and offer to run them
4. Or run migrations manually: access the migrate step in the installer after removing `storage/install.lock` temporarily

---

## Uninstalling

1. Drop the database
2. Remove all project files
3. No external services to disconnect (Doodstream API key is only stored locally)
