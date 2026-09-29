# MenoyeMan (منوی من)

A multi-tenant SaaS for restaurants, cafés, sweet shops and juice bars in Iran: digital QR menus, table ordering, online payments (ZarinPal), SMS (sms.ir), and an owner panel — fully in **Persian (Farsi), RTL, Persian digits, and Jalali dates**.

- **Site:** MenoyeMan.ir
- **Stack:** PHP 8.2+ · Laravel · Blade · Tailwind CSS 4 · Alpine.js · Chart.js (local) · morilog/jalali · Intervention Image · PHPUnit
- **Hosts:** Iranian shared hosting (cPanel) **and** VPS — no external CDNs, no Google Fonts, no reCAPTCHA. Everything is bundled locally.

> Status: Phase 1 complete — foundation (multi-tenancy, auth, design system, helpers, tests).
> See `docs/DECISIONS.md` for architectural decisions and `docs/DEPLOY.md` for deployment.

## Highlights

- **Multi-tenant by construction.** Every menu-scoped model carries `menu_id` and is filtered by a global scope (`App\Scopes\MenuOwnedScope`) fed from `App\Support\TenantContext`. Forgetting a `where` clause cannot leak data — proven by `tests/Feature/Tenancy/TenantIsolationTest.php`.
- **Persian-first UI.** All user-facing strings live in `lang/fa/`; digits render as ۰–۹; dates are Jalali in `Asia/Tehran`. Code, comments, and docs are English.
- **SMS login (OTP) + password login.** Fake/local SMS mode displays the code on screen for development; production uses sms.ir with template verification.
- **Self-hosted everything.** Vazirmatn variable font, compiled Tailwind, Chart.js, and brand assets ship in the repository (`public/build`, `public/brand`, `resources/fonts`).

## Local setup

### Requirements

- PHP 8.2+ with `pdo_sqlite`/`pdo_mysql`, `gd`, `mbstring`, `openssl`, `fileinfo`, `sodium`
- Composer 2
- Node.js 20+ (only when changing frontend assets)

### Quick start (any platform)

```bash
git clone <repo> menoyeman-new && cd menoyeman-new
composer install
npm ci && npm run build          # committed build lives in public/build
cp .env.example .env
php artisan key:generate
php artisan migrate              # SQLite file DB by default
php artisan serve                # http://127.0.0.1:8000
```

Set in `.env` for local fake SMS (the code is shown on the verify page):

```env
SMS_DRIVER=log
SMS_FAKE_DISPLAY=true
```

### Laragon (Windows)

1. Install [Laragon](https://laragon.org) (PHP 8.2+, Composer included).
2. Clone the repo into `C:\laragon\www\menoyeman-new`.
3. In Laragon terminal: `composer install`, `npm ci && npm run build`, `cp .env.example .env`, `php artisan key:generate`, `php artisan migrate`.
4. Add a site host (e.g. `menoyeman.test`) in Laragon → **Menu → www → menoyeman-new**, or just use `php artisan serve`.
5. SQLite needs no DB server. For MySQL, create a database and set `DB_CONNECTION=mysql` etc. in `.env`.

### Docker (optional)

```bash
docker compose up -d            # php-fpm + nginx + mysql
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

A minimal `compose.yaml` (php:8.3-fpm, nginx:alpine, mysql:8) is the recommended VPS-style
local mirror; production specifics are in `docs/DEPLOY.md`.

## Testing

```bash
php vendor/bin/phpunit            # full suite
php vendor/bin/phpunit tests/Unit # fast unit tests
```

The suite covers Persian digits/words, phone normalization, Jalali conversion, OTP security rules,
slug rules, authentication flows, and **cross-tenant isolation** (global scope, IDOR probes, policy,
middleware).

> This repository was developed under a php-wasm toolchain; `tests/Concerns/RefreshesDatabase.php`
> runs migrations via `Artisan::call()` because the `$this->artisan()` test helper crashes that
> runtime. On real PHP the behavior is identical to Laravel's `RefreshDatabase` trait.

## Project layout

```
app/
  Http/Controllers/     Auth, Onboarding, Dashboard
  Http/Middleware/      SetTenantContext, SecurityHeaders
  Models/               User, Menu, Category, OtpCode, Setting, MenuStaff
  Policies/             MenuPolicy (owner/super-admin authorization)
  Scopes/               MenuOwnedScope (tenant isolation)
  Services/Auth/        OtpService (hashed codes, throttling, attempts)
  Services/Sms/         SmsManager + drivers (log/fake, sms.ir)
  Services/Menus/       SlugRules (menu-ID validation)
  Support/              Persian, Phone, JalaliDate, TenantContext
bootstrap/app.php       middleware registration
config/menoyeman.php    trial days, grace period, retention, URLs
database/migrations/    users (phone auth), menus, categories, otp_codes, settings
lang/fa/                all Persian UI strings
resources/brand/        original + working logo, generated PWA/OG assets
scripts/                brand asset generation + WCAG contrast checker
tests/                  Unit + Feature (Tenancy, Auth, Menus)
```

## Key conventions

- **Never commit `.env`** — `.env.example` is the template.
- **Compiled frontend is committed** (`public/build`) so shared hosting needs no Node.
- **Commits** are small Conventional Commits in English; each phase ends with a `phase-N-complete` tag.
- **Menu IDs (slugs):** lowercase `a-z0-9-`, 3–30 chars, no leading/trailing hyphen, globally unique, reserved words blocked. Changing one breaks printed QR codes → confirmation dialog (Phase 2).
- **Money** is stored in IRR; displayed in تومان with Persian digits; server recomputes all prices.

## Docs

| File | Contents |
|---|---|
| `docs/DEPLOY.md` | cPanel shared hosting + VPS deployment |
| `docs/DECISIONS.md` | Architecture decisions and rationale |
| `docs/BRAND.md` | Brand tokens, contrast proof, asset pipeline |
| `docs/ADMIN_GUIDE.fa.md` | راهنمای فارسی مدیریت |
| `docs/THEME_GUIDE.md` | Theme package format (Phase 3) |
| `docs/THEME_AI_PROMPT.md` | Prompt for generating new themes (Phase 3) |
