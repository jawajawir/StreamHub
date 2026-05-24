# Cron & Queue Setup

StreamHub uses a single cron entrypoint that handles scheduled tasks and drains pending queue jobs. No daemon or supervisor is required.

---

## Cron Configuration

### Option A — URL Cron (cPanel / shared hosting)

In cPanel → Cron Jobs, add a new job running **every minute**:

```
*/1 * * * * curl -fsS "https://your-domain.com/cron/run?token=YOUR_CRON_TOKEN" >/dev/null 2>&1
```

Or using `wget`:
```
*/1 * * * * wget -qO /dev/null "https://your-domain.com/cron/run?token=YOUR_CRON_TOKEN"
```

### Option B — PHP CLI (SSH / VPS)

```
*/1 * * * * php /home/user/public_html/cron/run.php --token=YOUR_CRON_TOKEN >> /dev/null 2>&1
```

### Finding your CRON_TOKEN

The token is in your `.env` file:
```
CRON_TOKEN=abc123def456...
```

It was auto-generated during installation. You can rotate it by changing the value in `.env`.

---

## What the cron runner does

Each run (every minute):

| Job | Description |
|-----|-------------|
| `membership_expiry` | Checks `user_memberships` for expired records, downgrades users to `free` tier |
| `queue_drain` | Processes up to 50 pending `queue_jobs` with exponential backoff on failure |
| `health_check` | Writes a health-check row to `system_health_checks` for monitoring |

The runner uses a **file lock** (`storage/tmp/cron-runner.lock`) to prevent overlapping executions.

---

## Queue Worker (dedicated mode)

For VPS/dedicated servers with SSH access, you can run a dedicated queue worker:

```bash
php /path/to/queue/worker.php --token=YOUR_CRON_TOKEN --max=100 --timeout=55
```

Parameters:
- `--token` (required) — same CRON_TOKEN from .env
- `--max` — maximum jobs per run (default: 100)
- `--timeout` — max seconds before stopping (default: 55)

For continuous processing via supervisor:

```ini
[program:streamhub-queue]
command=php /home/user/streamhub/queue/worker.php --token=YOUR_TOKEN --max=200 --timeout=55
autostart=true
autorestart=true
startsecs=5
startretries=3
stderr_logfile=/home/user/streamhub/storage/logs/queue-worker.err.log
stdout_logfile=/home/user/streamhub/storage/logs/queue-worker.out.log
```

---

## Queue Job Types

| Job Type | Module | Description |
|----------|--------|-------------|
| `membership_expiry` | Core | Expire due memberships |
| `mail_send` | Mail Manager | Send queued emails via SMTP |
| `doodstream_sync` | API Manager | Sync files from Doodstream API |
| `sitemap_generate` | SEO Manager | Regenerate sitemap XML |
| `cache_cleanup` | System | Remove stale cache files (>24h) |

Jobs are stored in the `queue_jobs` table with status tracking (`pending` → `running` → `completed` / `failed`).

Failed jobs are retried with exponential backoff (30s, 60s, 150s, 300s) up to `max_attempts` (default: 3).

---

## Monitoring

Check cron health in the admin panel:
- **Dashboard** → Queue & Cron card shows pending/failed counts and last-run status
- **System → Audit Logs** → cron-related entries
- `system_health_checks` table → look for `check_key = 'cron_runner'`
- `cron_schedules` table → `last_run_at`, `last_status`, `next_run_at`

---

## Security

- The cron URL requires a valid `CRON_TOKEN` — requests without it return 401
- Failed auth attempts are logged to `security_events` as `api_auth_failed`
- The `cron/` and `queue/` directories are protected by `.htaccess` (deny all direct access)
- CLI mode validates the token via `--token=` argument
- File lock prevents double-execution

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Cron returns 401 | Verify CRON_TOKEN in .env matches the URL/argument |
| "Another cron run is in progress" | Delete `storage/tmp/cron-runner.lock` if stale |
| Jobs stuck in "running" | Check for crashed workers; manually reset status to "pending" in `queue_jobs` |
| Membership not expiring | Verify cron is actually running — check `cron_schedules.last_run_at` |
