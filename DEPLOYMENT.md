# Deployment Guide (Laravel Forge)

This documents how the live deployment (`kagglecapstone.on-forge.com`) is actually set up, so it can be reproduced from scratch. It reflects the real, verified-working configuration — including gotchas that were hit and fixed during production hardening — rather than a generic Laravel deploy checklist.

> For local development setup, see [`README.md`](README.md#getting-started) instead. This document is specifically about reproducing the **production** deployment on Forge.

## 1. Provision the server

1. Create a server in [Laravel Forge](https://forge.laravel.com) (Ubuntu, PHP 8.4, MySQL, Nginx).
2. Create a new **Site** pointing at your domain (or use a Forge-provided `*.on-forge.com` subdomain for quick evaluation).
3. Connect the site to this Git repository, branch `main`.
4. Set the site's web directory to `/public` (standard Laravel document root).

## 2. Environment variables (Forge → Site → Environment)

At minimum:

```
APP_NAME="HomeLogic360"
APP_ENV=production
APP_KEY=                      # generate with: php artisan key:generate --show
APP_DEBUG=false
APP_URL=https://your-domain

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

QUEUE_CONNECTION=sync         # see "Known gotchas" below before changing this
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=your-domain

# Required for the AI Assistant to run in real (non-heuristic) mode
ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_MAX_TOKENS=2048      # optional; code defaults to 2048 if unset — must be >=2048 or responses truncate (see gotcha #5)

# Real-time notifications (recommended: Pusher, not self-hosted Reverb)
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

See [`.env.example`](.env.example) for the complete list (mail, AWS/S3, web push VAPID keys, fax module, etc.).

**Important**: `ANTHROPIC_API_KEY` must be set here even though the app also reads it via `config('services.anthropic.api_key')` — that config key is backed by `env('ANTHROPIC_API_KEY')` in [`config/services.php`](config/services.php), and Laravel's `config:cache` step (below) bakes `.env` values into a compiled cache at deploy time. If you add or change this key, you **must** redeploy (or run `php artisan config:cache` again) for it to take effect — a bare `env()` read after `config:cache` has run returns `null`.

## 3. Deploy script

Forge's **Deploy Script** box should run the equivalent of [`deploy.sh`](deploy.sh) in this repo:

```bash
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
npm ci
npm run build

php artisan migrate --force
php artisan storage:link || true

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart PHP-FPM so OPcache picks up the new code (see gotcha #6)
sudo service php8.4-fpm reload

php artisan queue:restart
```

Seeding is **not** run automatically on every deploy (it's meant for initial setup / demo data refresh, not every push) — run it manually via Forge's **Commands** tab when needed:

```bash
php artisan db:seed --force
```

This runs `DatabaseSeeder`, which chains: `CompleteDatabaseSeeder` (roles, permissions, facility, residents, medications, vitals, appointments, assessments, sleep, behaviors, incidents, staff/leave/clock-in records) → `FacilitySettingsSeeder` → `FaxModuleSeeder` → `DemoDataBackfillSeeder` (fills in anything the above skips — see gotcha #7).

## 4. Known gotchas (hit and fixed in this deployment)

These are documented here because they're easy to reintroduce if this deployment is ever rebuilt from scratch, and none of the older docs in this repo cover them.

1. **`chart_messages` migration re-run failure** — a migration that had partially applied in a prior failed deploy would error with "table already exists" on retry. Fixed by guarding `up()` with `if (Schema::hasTable(...)) return;` — apply this pattern to any migration you add that might run against a partially-migrated database.

2. **MySQL 64-character identifier limit** — auto-generated composite index names on `pharmacy_stock_transactions` exceeded MySQL's identifier length limit. Always pass an explicit short name as the second argument to `$table->index([...], 'short_name')` for multi-column indexes.

3. **`fakerphp/faker` must be in `require`, not `require-dev`** — Forge's deploy runs `composer install --no-dev`, which strips `require-dev` packages. Laravel's `fake()` helper is only defined `if (class_exists(\Faker\Factory::class))`, so any seeder using `fake()` (several do) fails with `Call to undefined function` unless Faker is a production dependency.

4. **Pusher misconfiguration must not crash requests** — several model Observers (`NotificationObserver`, `VitalSignObserver`, `MedicationAdministrationObserver`) broadcast real-time events synchronously (`QUEUE_CONNECTION=sync`). If Pusher credentials are missing/invalid, an unguarded `event(new ...)` call throws and takes down the whole request — including seeders creating hundreds of records. Every broadcast call is wrapped in `try { event(...) } catch (\Throwable $e) { Log::warning(...) }` so a broadcasting failure degrades to "no live notification" instead of a 500 or a failed seed.

5. **Chart Assistant silently falling back to heuristic mode** — this took the longest to fully diagnose and had three separate causes, all now fixed in [`app/Services/ChartAssistantService.php`](app/Services/ChartAssistantService.php):
   - `ANTHROPIC_API_KEY` unreachable after `config:cache` (see the note in step 2)
   - the model ID was hardcoded to a since-retired Claude model — always verify the model string against current Anthropic docs
   - **`max_tokens` too low (800)** — Claude's JSON reply was getting truncated mid-object before it could close its braces, so `json_decode` silently failed. This is the subtlest one: the failure looks identical to "API not configured" from the UI, but the root cause is completely different. All three failure paths (missing key, HTTP error, invalid JSON) now log a `Log::warning('Chart assistant Anthropic fallback triggered', ...)` line — if the assistant is stuck in heuristic mode, `grep -i "anthropic" storage/logs/laravel.log` should always show *why*, never silence.

6. **Forge's "Commands" panel shows "No output available" for `php artisan` commands** — this is a Forge platform quirk, not a broken command. Plain shell commands (`echo`, `cat`, `ls`) display output fine; `php artisan ...` output is frequently swallowed by the panel even when the command succeeds. Work around it by redirecting to a file and reading that file with `cat`/`grep`:
   ```bash
   php artisan your:command >> storage/logs/command-output.log 2>&1
   cat storage/logs/command-output.log
   ```

7. **Seeders that exist in the codebase but were never wired into the seed chain** — `FireDrillSeeder`, `GroceryStatusUpdateSeeder`, `ExpenseCategorySeeder`, `PharmacySupplierSeeder`, `PharmacyInventorySeeder`, `MedicationDeliverySeeder`, `HousekeepingSeeder`, and `BehaviorDefinitionSeeder` all existed but were never called by `DatabaseSeeder`/`CompleteDatabaseSeeder`, leaving those tables empty in production. `DemoDataBackfillSeeder` now wires them in, guarded by a count check (`backfillIfEmpty()`) since most use plain `create()` instead of `firstOrCreate()` and would duplicate data if run twice.

8. **`VitalSignSeeder` producing zero records** — its date range was hardcoded to `"September 1st of the current year"` through "today," which is a *future* date (and therefore a no-op loop) for any run happening January–August. Fixed to a rolling 4-month lookback so the range is always valid regardless of when the seeder runs.

## 5. Post-deploy verification checklist

- [ ] `php artisan migrate:status` — confirm no pending migrations
- [ ] Log in (or use the demo auto-login) and confirm the sidebar renders with the correct facility branding, not the generic "HomeLogic360 / Care Home" super-admin fallback
- [ ] Open the AI Assistant page, select a resident with chart data, and confirm the response shows `Mode: anthropic (claude-opus-4-8)` — if it shows `Mode: heuristic`, check `storage/logs/laravel.log` for the `Chart assistant Anthropic fallback triggered` warning to see why
- [ ] Confirm real-time notifications work (or fail *silently*, per gotcha #4, rather than crashing pages) if Pusher isn't configured yet
- [ ] Run `php artisan db:seed --force` once to populate demo data, and re-run it any time new demo residents/facilities are added (it's safe to re-run — see gotcha #7)

## Optional: self-hosted Reverb instead of Pusher

The app defaults to Pusher (`BROADCAST_CONNECTION=pusher`) because it needs no extra process or Nginx config on the server. If you'd rather self-host WebSockets with Laravel Reverb:

1. Set the commented-out `REVERB_*` / `VITE_REVERB_*` variables in `.env.example` and set `BROADCAST_CONNECTION=reverb`.
2. Run `php artisan reverb:start` as a persistent Forge daemon.
3. Add the Nginx config in [`deployment/nginx-reverb-forge.conf`](deployment/nginx-reverb-forge.conf) to the site's Nginx config — it proxies only WebSocket upgrade requests on `/app` to Reverb, leaving normal SPA traffic on that same path alone (a naive proxy config here will break the React app).
