# Laravel Upgrade Plan — Saurashtra Express (Laravel 8 → 11)

> Target: Laravel 11.x (latest stable) on PHP 8.2.
> Current declared: Laravel `^11.0` in `composer.json` but the code is Laravel 8.
> Companion: `framework-version-audit.md`, `refactoring-plan.md`.

---

## 1. Why upgrade

| Reason | Detail |
|---|---|
| Bug fixes | 8 → 11 closes 3+ years of framework CVEs |
| PHP 8.2 features | readonly properties, enums, fibers, `str()` helper |
| Simpler bootstrap | L11 removes `app/Http/Kernel.php` in favor of `bootstrap/app.php` |
| Maintenance | L8 hit EOL March 2023; L9 EOL February 2024; L10 EOL March 2025; L11 active |
| Performance | L11 ships with improved query performance and OPcache tuning |

---

## 2. Pre-flight checklist

| Item | Status | Notes |
|---|---|---|
| PHP version ≥ 8.2 | ✅ (declared in composer.json) | Verify on server |
| Composer 2.x | ⚠️ | Verify |
| `composer.lock` committed | ❌ | **Generate one before any upgrade** |
| Tests | ❌ | **Add at least a smoke test** before any upgrade |
| Local PHP 8.2 environment | ⚠️ | Verify |

---

## 3. The breaking changes that affect this codebase

| Change | Affected code | Migration |
|---|---|---|
| `app/Http/Kernel.php` is removed; middleware goes in `bootstrap/app.php` | `app/Http/Kernel.php` | Port middleware aliases to `->withMiddleware([...])` in `bootstrap/app.php` |
| `app/Console/Kernel.php` is removed; commands go in `routes/console.php` or auto-discovered | `app/Console/Kernel.php` | Port scheduled commands to `routes/console.php` |
| `app/Exceptions/Handler.php` is removed | `app/Exceptions/Handler.php` | Port exception handling to `->withExceptions(...)` in `bootstrap/app.php` |
| `Route::resource('/x', 'App\…\YController')` (string) emits deprecation | `routes/web.php` (4 sites) | Replace with `[X::class, 'method']` or `X::class` |
| Default `App\Providers\AuthServiceProvider` `$policies = []` is fine | OK | — |
| `app/Providers/EventServiceProvider::$listen = []` is fine | OK | — |
| `app/Providers/RouteServiceProvider` `HOME` constant is deprecated | `RouteServiceProvider` | Replace with `Route::redirect('/dash', ...)` or in `bootstrap/app.php` |
| `Spatie\Permission` 6.x is L11-compatible | OK | — |
| `laravel/breeze` 2.x → must be ≥ 2.1 for L11 | `composer.json` | Bump |
| `fruitcake/laravel-cors` 3.x → built-in CORS in L11 | `composer.json` | Remove the package and config; use `config/cors.php` in L11 form |
| `Carbon` 2.x → 3.x | indirect via `laravel/framework` | Auto-upgraded by Composer |
| `Symfony` 6.x → 7.x | indirect | Auto-upgraded |
| `getKeyName()` and `getIncrementing()` semantics for non-int PKs | `challan` model | Verify `challan_no` is still a string PK after the `id` PK is added in Phase 5 |
| `withCount()` returns `int` (was `int\|null`) | views displaying counts | Cast as needed |
| `casts()` method preferred over `$casts` property | `User` | Migrate |
| `password` mutator → cast | `User::setPasswordAttribute` | Replace with `protected $casts = ['password' => 'hashed']` (L10+) |
| `dateFormat` and `dates` properties deprecated | none in this codebase | OK |
| `Str::random()` etc. unchanged | OK | — |
| Validation messages: `numeric` rule (typo) → `number` | `GrController::store` has `'pm.numberic'` etc. | Fix to `'numeric'` |
| `mimes:` rule uses Laravel default; mimes must include dot | views with file uploads | None observed in this codebase |
| `route:list` command renamed (cosmetic) | CI scripts | None |
| `php artisan make:*` flags unchanged | OK | — |
| `cache:clear` etc. unchanged | OK | — |
| `SESSION_DRIVER=cookie` removed | `.env` says `file` | OK |
| `CACHE_STORE` instead of `CACHE_DRIVER` (cosmetic) | `.env` says `CACHE_DRIVER=file` | Update to `CACHE_STORE=file` |
| `QUEUE_CONNECTION` deprecated in favor of `QUEUE_CONNECTION` (still valid) | OK | — |
| `MAIL_MAILER` (was `MAIL_DRIVER`) | `.env` uses `MAIL_MAILER` | OK |
| `BCRYPT_ROUNDS` (was `BCRYPT_COST`) | `.env` | Update name to `BCRYTH_ROUNDS` if needed |
| `auth()->user()->office` style accessors | `GrController` etc. | OK (auth() helper unchanged) |
| `Auth::routes()` is removed in L11 | `routes/web.php` line 19 | Replace with explicit routes (Breeze-style) |
| `email_verified_at` cast stays | `User` | OK |

---

## 4. Step-by-step upgrade (incremental, safe)

> **Each step is a separate PR. Do not bundle.**

### Step 0 — Freeze
- Tag current code as `v0.8.0-legacy`.
- Add `.gitignore` entry for `vendor/`, `node_modules/`, `.env`, `storage/*.sqlite`, `*.log`.
- **Delete** `Composer-Setup.exe` from project root (it should not be committed).

### Step 1 — Add tests
- Write at least one smoke test that hits `GET /` and asserts the login page renders.
- Add one Feature test per business module that just asserts the route exists (no behavior assertions yet).
- Verify the test suite runs against a temporary SQLite DB.

### Step 2 — Generate the lock file
- `composer update --no-scripts` (with L8 declared) to get a `composer.lock` for the current state.
- Commit `composer.lock`.

### Step 3 — Bump to Laravel 9
- Set `"laravel/framework": "^9.0"`.
- Run `composer update`.
- Fix L9 deprecations (mostly cosmetic — message changes, etc.).
- Run tests.
- Tag `v0.9.0`.

### Step 4 — Bump to Laravel 10
- Set `"laravel/framework": "^10.0"`.
- Run `composer update`.
- Fix L10 changes:
  - Replace `setPasswordAttribute` with `protected $casts = ['password' => 'hashed']` in `User`.
  - Remove `laravelcollective/html` if unused (search for `Form::` — none found).
- Run tests.
- Tag `v0.10.0`.

### Step 5 — Bump to Laravel 11
- Set `"laravel/framework": "^11.0"`.
- Set `"php": "^8.2"`.
- Run `composer update`.
- Remove `fruitcake/laravel-cors` from `composer.json` and `config/cors.php`.
- **Move middleware aliases** from `app/Http/Kernel.php::$middlewareAliases` to `bootstrap/app.php`:
  ```php
  ->withMiddleware(function (Middleware $middleware) {
      $middleware->alias([
          'isAdmin' => \App\Http\Middleware\AdminMiddleware::class,
          'clearance' => \App\Http\Middleware\ClearanceMiddleware::class,
      ]);
  })
  ```
- **Move console schedule** from `app/Console/Kernel.php` to `routes/console.php`:
  ```php
  use Illuminate\Support\Facades\Schedule;
  Schedule::command('inspire')->hourly();  // placeholder
  ```
- **Move exception handler** from `app/Exceptions/Handler.php` to `bootstrap/app.php`:
  ```php
  ->withExceptions(function (Exceptions $exceptions) {
      //
  })
  ```
- **Delete** `app/Http/Kernel.php`, `app/Console/Kernel.php`, `app/Exceptions/Handler.php`.
- **Replace** `Auth::routes()` with explicit routes (Breeze-style) or install Breeze:
  ```php
  // routes/auth.php
  use App\Http\Controllers\Auth\AuthenticatedSessionController;
  Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
  Route::post('login', [AuthenticatedSessionController::class, 'store']);
  Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
  // ... register, password, etc.
  ```
  And include in `bootstrap/app.php`:
  ```php
  ->withRouting(
      web: __DIR__.'/../routes/web.php',
      auth: __DIR__.'/../routes/auth.php',  // NEW
      commands: __DIR__.'/../routes/console.php',
      health: '/up',
  )
  ```
- **Replace** `Route::resource('/x', 'App\…\YController')` with `::class` references:
  ```php
  Route::resource('/users', UserController::class);
  Route::resource('/roles', RoleController::class);
  Route::resource('/permissions', PermissionController::class);
  ```
- **Update** validation message keys: `'pm.numberic'` → `'pm.numeric'`.
- **Update** `.env` keys: `CACHE_DRIVER` → `CACHE_STORE`.
- Run tests.
- Tag `v0.11.0`.

### Step 6 — Modernize the code
- Introduce service layer (see `refactoring-plan.md §3`).
- Fix `PostController` missing (create or remove the route).
- Fix `challan_iteam` typo.
- Fix `gatepasses.gst_amount` ghost.
- Fix `copies_print.blade.php` `packeges` typo + hard-coded Windows path.
- Implement Freight Memo `store/update/destroy`.
- Tag `v1.0.0`.

### Step 7 — Modernize the schema
- Apply the migrations in `database/migrations_new/` (see `database-reconstruction-report.md §14` and Phase 4 plan).
- Tag `v1.1.0`.

---

## 5. Verification at each step

```bash
# After each composer update
composer install --no-interaction
php artisan key:generate
php artisan migrate:fresh --seed
php artisan config:clear
php artisan view:clear
php artisan route:list
php artisan test
```

---

## 6. Risk register

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Auth stack breaks | Medium | High | Pin Breeze at Step 1, smoke test login |
| Middleware aliases lost | Medium | High | Manual port with explicit tests |
| Spatie permission v6 changes | Low | Medium | Read Spatie upgrade guide first |
| Hard-coded Windows path breaks in CI | High | Low | Already broken in prod too — fix in Step 6 |
| `composer.lock` regeneration causes dependency drift | Medium | Medium | Pin Spatie and CORS versions explicitly |
| Hidden PHP 8.2 deprecations | Medium | Medium | Run `php -d zend.assertions=1 -d assert.exception=1 artisan test` |
| Custom Casts/Casts classes need porting | Low | Low | None exist |
| Eloquent `whereKey()` semantics for string PK | Low | Low | Affects `challan` only |

---

## 7. Estimated time

| Step | Effort | Notes |
|---|---|---|
| 0. Freeze | 1h | git tag, gitignore, exe removal |
| 1. Tests | 8h | Smoke + per-module route existence |
| 2. Lock | 15min | composer update |
| 3. L8 → L9 | 4h | mostly cosmetic |
| 4. L9 → L10 | 6h | mutator → cast, html package removal |
| 5. L10 → L11 | 16h | middleware, console, exceptions port, Auth::routes() replacement |
| 6. Modernize code | 40h | see refactoring-plan |
| 7. Modernize schema | 16h | see migrations_new/ |
| **Total** | **~91h** | ~2 weeks of dedicated work |

---

## 8. Compatibility matrix

| Package | L8 | L9 | L10 | L11 |
|---|---|---|---|---|
| `laravel/framework` | ✅ | ✅ | ✅ | ✅ (target) |
| `spatie/laravel-permission` | v5 | v5 | v5/v6 | v6 |
| `laravel/breeze` | 0.x | 1.x | 1.x/2.0 | 2.0+ |
| `laravelcollective/html` | 6.x | 6.x | 6.x | 6.x (unused) |
| `fruitcake/laravel-cors` | 2.x | 2.x/3.x | 3.x | built-in |

---

## 9. Rollback

Each step is a separate git commit. If any step fails:

```bash
git revert <commit-hash>           # revert the step
composer install                   # restore previous composer.lock
php artisan migrate:rollback       # if migrations were touched
```

For schema changes (Step 7), keep the original `database/migrations/` and put new ones in `database/migrations_new/`. If something goes wrong, simply don't run the new ones.

---

## 10. Decision points for the user

1. **Should we keep `composer.lock` in version control?** Recommended: yes.
2. **Should we add Breeze (L11-style auth) or keep the legacy `Auth\LoginController` stack?** Recommended: Breeze for cleanliness, but it's a much larger change.
3. **Should we drop `laravelcollective/html`?** Recommended: yes (not used).
4. **Should we drop `fruitcake/laravel-cors`?** Recommended: yes (built into L11).
5. **Should we drop the `posts` table and the broken `/dash/posts` route?** Recommended: yes.
6. **PHP 8.2 or 8.3?** Recommended: 8.2 (declared, widely available; 8.3 is fine if your host supports it).
