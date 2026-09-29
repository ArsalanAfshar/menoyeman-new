# Decisions — MenoyeMan

Architecture decisions and their rationale. Newest phases are appended at the bottom;
each entry states the decision, why, and any consequence to remember.

## Phase 1 — Foundation

### D1. Laravel monolith with Blade + Tailwind + Alpine (no Livewire / Inertia)

**Decision:** Server-rendered Blade views, Tailwind CSS 4, Alpine.js for interactivity,
Chart.js for graphs. No Livewire, no Inertia, no SPA.
**Why:** Must run on Iranian shared hosting (cPanel) with limited resources and no Node at
runtime; prebuilt assets are committed to `public/build`. Alpine keeps the JS payload tiny and
works with the "no external CDN" constraint.
**Consequence:** Interactivity is small and localized; heavy UI updates use full page loads or
smart polling.

### D2. Single database, shared schema, `menu_id` + Global Scope tenancy

**Decision:** One database; tenant-owned tables carry `menu_id`; `App\Scopes\MenuOwnedScope`
filters every query automatically; `App\Support\TenantContext` holds the active menu.
**Why:** Cheapest and simplest on shared hosting (no multi-DB provisioning, no per-tenant
migrations). Forgetting a `where` clause cannot leak data because the scope is always applied.
**Consequence:** Requires membership checks in middleware/policies (done) and *isolation tests* —
`tests/Feature/Tenancy/TenantIsolationTest.php` asserts cross-tenant reads/writes fail.

### D3. `TenantContext` uses static state — reset it in long-running processes

**Decision:** `TenantContext` stores the current user/menu in static properties.
**Why:** Zero-config access from models and scopes without passing a tenant object through every
call stack; safe for classic PHP-FPM/HTTP where each request is a fresh process state.
**Consequence (must not be forgotten):** statics persist inside `queue:work` workers and any
long-lived process. When Phase 5/6 introduces queued jobs, **each job must reset/set the tenant
context in its middleware/handle start**. Tests already reset it in `TestCase::setUp()`.

### D4. Phone-first authentication with OTP, password as an alternative

**Decision:** Registration/login via 11-digit Iranian mobile number (`09xxxxxxxxx`,
normalized with Persian/Arabic digit support) and a 6-digit SMS OTP; password login remains
available for owners who prefer it.
**Why:** Standard, lowest-friction flow for Iranian users; SMS is the only reliable identifier.
**Security:** OTP codes are stored **hashed**, expire after a few minutes, have a per-code attempt
limit, and resend throttling. OTP rows are keyed by phone + purpose (`login`, `password_reset`).

### D5. Multi-step session values must be `put()` (persistent), never flashed

**Decision:** The OTP handshake stores `phone` / `otp_purpose` in the session with
`$request->session()->put()`, and forgets them on success.
**Why:** Laravel's `redirect()->with()` **flashes** — alive for exactly one subsequent request.
The flow spans `POST /login` → `GET /login/verify` → `POST /login/verify` (and reset continues to
`POST /panel/password/reset`), so flashed values vanish mid-flow. This was a real bug; the
regression test is `test_forgot_password_flow_sets_new_password`.

### D6. SMS behind a driver contract (log/fake locally, sms.ir in production)

**Decision:** `SmsManager` resolves a driver from `config/sms.php`. Local/dev uses a log driver and
may display the OTP on the verify page (`SMS_FAKE_DISPLAY=true`); production uses the sms.ir
template API.
**Why:** Developers and the owner must test the full flow without spending SMS credit or
depending on a production key; production gets the real provider without code changes.

### D7. Menu ID (slug) rules and the slug-check endpoint

**Decision:** Menu IDs are `a-z0-9-`, 3–30 chars, no leading/trailing hyphen, globally unique, and
must not collide with a reserved word list. `SlugRules` is the single source of truth and returns
Persian error messages. The live check endpoint (`/panel/onboarding/slug-check`) ignores the
caller's **own** menu id, so re-checking your current slug reports "available".
**Why:** The ID appears in printed QR codes and public URLs; it must be predictable and safe.

### D8. Changing a menu ID is a dangerous operation → explicit warning

**Decision:** Phase 1 keeps the rule only; Phase 2 adds a confirmation dialog explaining that
changing the menu ID **breaks every previously printed QR code and shared link**, and offers
"keep the old address working for a limited time" (redirect) as the safe path.
**Why:** Owners print QR codes on tables and stickers; silent renames break physical media.

### D9. Weights (min/step) and rounding

**Decision:** Weight-based items will define a minimum quantity and a step (e.g. min 250 g,
step 250 g). Order quantities are validated server-side and **rounded to the nearest step,
half-up**; the UI shows the rounding before submit.
**Why:** Iranian menus sell by weight; unrounded values cause pricing disputes.
**Consequence:** Revisit in Phase 4 when the cart is implemented; the rule is centralized in a
single helper so the UI and server always agree.

### D10. Money handling

**Decision:** Prices are stored as integer IRR (Rial) in the database; all display is in **Toman**
with Persian digits and thousand separators; prices are **always recomputed on the server** —
client-submitted amounts are ignored.
**Why:** No floating-point errors, no tampering with totals, single currency formatting source.

### D11. Dates and locale

**Decision:** Timestamps stored in UTC; displayed as Jalali dates in `Asia/Tehran` via
`App\Support\JalaliDate` (morilog/jalali). `APP_LOCALE=fa`; all UI strings live in `lang/fa/`.
**Why:** Persian users expect Jalali dates and Persian digits; storing UTC keeps calculations and
future scheduling correct.

### D12. No external runtime dependencies

**Decision:** Vazirmatn (variable WOFF2) self-hosted in `resources/fonts`, Tailwind compiled and
committed to `public/build`, Chart.js bundled locally, brand assets generated by
`scripts/build-brand-assets.php`. No Google Fonts, no CDNs, no analytics, no reCAPTCHA.
**Why:** Those services are blocked or unreliable in Iran and would break the site for customers.

### D13. Background work via database queue + Cron

**Decision:** `QUEUE_CONNECTION=database`, driven by `php artisan schedule:run` in Cron.
**Why:** No Redis/supervisor provisioning on shared hosting. See D3 for the tenant-context
consequence when jobs are introduced.

### D14. Test database migrations under the php-wasm toolchain

**Decision:** `tests/Concerns/RefreshesDatabase.php` wraps Laravel's `RefreshDatabase` and runs
migrations through `Artisan::call('migrate')` instead of the `$this->artisan()` helper.
**Why:** The helper crashes the php-wasm runtime used during development; behavior on real PHP
is identical.
**Consequence:** Use this trait in tests instead of `RefreshDatabase` directly.

### D15. Logo raster inside SVG is a performance and Iran-CDN bug

**Decision:** The owner's `resources/brand/logo.svg` embeds a 3000px PNG as base64 (≈291KB).
The UI must never serve this SVG directly. `scripts/build-brand-assets.php` now extracts the
raster and generates small transparent PNGs (`public/brand/logo-32/48/64/96/128.png`). The
layouts use `logo-96.png` (≈3.7KB, 7KB budget) with explicit width/height and eager loading.
**Why:** 297KB SVG blocks LCP on mobile and violates the \"no CDN, everything local but tiny\"
rule. PNG is faster, cacheable, and works everywhere.
**Consequence:** Any future brand asset change must re-run `php scripts/build-brand-assets.php`
and `npm run build`, then commit `public/brand` and `public/build`.

### D16. CSRF must be disabled in automated tests, but only CSRF

**Decision:** `tests/TestCase.php::setUp()` calls `withoutMiddleware([PreventRequestForgery,
VerifyCsrfToken])`. In Laravel 13 the web group uses `PreventRequestForgery`, not the legacy
`VerifyCsrfToken`; both are disabled to avoid 419 on POST. All other web middleware
(SecurityHeaders, tenant, session, etc.) stays enabled.
**Why:** The simplest and safest for tests; CSRF is irrelevant in the test harness and would
require manually passing tokens. Disabling all middleware (`withoutMiddleware()`) breaks
`$errors` sharing and auth, so we disable only the two CSRF classes.
**Consequence:** SecurityHeaders and tenant isolation are still tested via dedicated Feature
tests.

### D17. Autoloader escaping and classmap generation under php-wasm

**Decision:** Vendor is restored via a Node script (`/tmp/clone-vendor.mjs`) because no PHP
binary exists in the sandbox. The script must escape PSR-4 prefixes as `'App\\'` (two slashes
in file) and use `__DIR__ . '/../../' . 'app'` for root paths, `__DIR__ . '/../' . 'pkg/src'`
for vendor. Classmap must be generated via `token_get_all` (not regex) to avoid matching
docblocks like `class is not covered`, and must include `T_ENUM` for `SortDirection`.
**Why:** The original regex produced entries like `'PHPUnit\TextUI\is'` and missed real classes.
**Consequence:** When composer.lock changes, re-run the classmap generator and verify
`findFile('App\Support\Persian')` resolves.

### D18. SortDirection polyfill for PHP 8.3 vs Laravel 13

**Decision:** Laravel 13 uses the PHP 8.4+ `SortDirection` enum. On PHP 8.3 we rely on
`symfony/polyfill-php86` which provides the enum via classmap `Resources/stubs/SortDirection.php`.
Our custom classmap generator must scan that stub and the PSR-4 `PHPUnit\` must be added to
`autoload_psr4.php` to cover any classmap misses.
**Why:** Without the polyfill, `Collection::sortBy` throws `Class SortDirection not found`.
**Consequence:** Keep `symfony/polyfill-php86` in composer.json and ensure its stub is in the
classmap; add `PHPUnit\` PSR-4 mapping when vendor is cloned manually.

### D19. APP_KEY in phpunit.xml and opcache disabled in wasm

**Decision:** `phpunit.xml` includes a testing `APP_KEY` (base64) so encryption works without
a `.env`. The wasm php.ini disables opcache (`opcache.enable=0`, `enable_cli=0`) to avoid
`is_path_to_shared_fs mount undefined` flock errors on NODEFS.
**Why:** php-wasm mounts host FS via NODEFS which does not support flock; opcache file_cache
uses flock and crashes. Disabling opcache is safe for tests.
**Consequence:** Any new phpunit run via wasm must write `/internal/shared/php.ini` with opcache
off and define STDERR/STDOUT/STDIN + set `$_SERVER['argv']` and `$GLOBALS['_composer_autoload_path']`.
