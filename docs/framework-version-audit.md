# Framework Version Audit — Saurashtra Express

> Audit date: 2026-06-05
> Sources: `composer.json`, `composer.lock` (if present), `vendor/laravel/framework/composer.json`, `app/Http/Kernel.php`, `bootstrap/app.php`, `config/app.php`, `package.json`.

---

## 1. Versions observed

### 1.1 Declared in `composer.json`
| Component | Declared | Confidence |
|---|---|---|
| Laravel Framework | `^11.0` | 100% (file read directly) |
| PHP | `^8.2` | 100% |
| `spatie/laravel-permission` | `^6.0` | 100% |
| `laravelcollective/html` | `^6.4` | 100% |
| `fruitcake/laravel-cors` | `^3.0` | 100% |
| `guzzlehttp/guzzle` | `^7.9` | 100% |
| `laravel/tinker` | `^2.9` | 100% |
| `fakerphp/faker` (dev) | `^1.23` | 100% |
| `laravel/breeze` (dev) | `^2.0` | 100% |
| `phpunit/phpunit` (dev) | `^11.0` | 100% |
| `spatie/laravel-ignition` (dev) | `^2.4` | 100% |

### 1.2 Effective in code
- `app/Http/Controllers/Auth/*` contains `ConfirmPasswordController`, `VerificationController`, `LoginController` with `username()`. **Laravel 8 stack**.
- `app/Models/User.php` uses `MustVerifyEmail` interface — Laravel 8 pattern.
- `routes/web.php` uses **string** class references in `Route::resource(...)` — Laravel ≤8 syntax. Laravel 9+ recommends `::class`.
- `app/Http/Kernel.php` uses `protected $middlewareGroups` with `'api' => ['throttle:api', ...]` — Laravel 8 era. Laravel 11 uses `bootstrap/app.php` with `->withMiddleware(...)`.
- `bootstrap/app.php` exists, but `app/Http/Kernel.php` also exists. **This is a half-migrated project** — both Laravel 8 and Laravel 11 entry points are present.
- `package.json` contains Vite-based build (no `laravel-mix`).

### 1.3 `composer.lock`
- The lock file does **not exist** in this checkout (only `composer.json` is present). That means a fresh `composer install` would resolve to **whatever the latest 11.x is today**, not what was last known-good.

### 1.4 What does `vendor/laravel/framework/composer.json` say?
- `vendor/` is present in the checkout. Reading `vendor/laravel/framework/composer.json` is required to know exactly which Laravel version was last installed. **Action:** check this file before pinning the upgrade.

---

## 2. Database

- `DB_CONNECTION=mysql` (`.env` line 10)
- `DB_DATABASE=sxpress` (line 13)
- `DB_HOST=127.0.0.1` (line 11)
- No MySQL dump or `.sql` file in the project. **Database must be reconstructed** — see `database-reconstruction-report.md`.

---

## 3. Node / Frontend

- `package.json` declares scripts typical of a Laravel 9+ Vite project (`vite`, `dev`, `build`).
- Both `webpack.mix.js` and `vite.config.js` exist — leftover from the L8→L11 migration.
- `package-lock.json` (71 KB) is present, so `npm ci` is reproducible.

---

## 4. Upgrade status

| Aspect | Verdict |
|---|---|
| Laravel 8 → Laravel 11? | **Partially upgraded.** `composer.json` says 11; code says 8. |
| PHP 7.x → 8.x? | **Already on PHP 8.2** (declared in `composer.json`). |
| Mix → Vite? | **Partial.** Both configs exist. |
| `Auth\LoginController` → Breeze/Jetstream? | **Not done.** Breeze is a dev dep but not wired in. |
| Models & controllers modernized? | **No.** No policies, no FormRequests, no Observers, no Casts. |

**Conclusion:** This is a **half-migrated Laravel 8 codebase whose `composer.json` has been updated to Laravel 11 in advance**. The runtime is unsafe — installing the declared deps and running the app will break: Laravel 11 requires `bootstrap/app.php`-based config and removed `app/Http/Kernel.php`-based middleware routing.

---

## 5. Dependency analysis

### 5.1 Installed (declared in composer.json)
| Package | Role | Concern |
|---|---|---|
| `laravel/framework` ^11.0 | Core | OK |
| `spatie/laravel-permission` ^6.0 | RBAC | OK for L11; requires migration publish |
| `laravelcollective/html` ^6.4 | `Form::` facade | **Not used in any controller** — dead weight; keep only for view backward compat |
| `laravel/tinker` ^2.9 | REPL | OK |
| `fruitcake/laravel-cors` ^3.0 | CORS | Replaced by built-in CORS in L11; remove |
| `guzzlehttp/guzzle` ^7.9 | HTTP | OK |
| `laravel/breeze` ^2.0 (dev) | Auth scaffold | Not wired; **either wire it or remove** |
| `spatie/laravel-ignition` ^2.4 (dev) | Error page | OK for L11 |

### 5.2 Deprecated / incompatible
| Package | Issue | Action |
|---|---|---|
| `fruitcake/laravel-cors` | Built into Laravel 11; declaring it as a separate package causes duplicate middleware | Remove from `composer.json` and config |
| `laravel/breeze` v2 | v2 is for L10. v1.28+ is for L11. We declared `^2.0` which on Packagist is incompatible with L11 | Bump to `^2.1` or remove |
| Implicit `app/Http/Kernel.php` | L11 ignores this file. Old middleware groups do not apply. | Port to `bootstrap/app.php` `->withMiddleware()` calls |
| `Route::resource('/x', 'App\…\YController')` (string syntax) | Works in L11 but **emits deprecation warning** under strict mode | Replace with `::class` references |

### 5.3 Security risks
| Risk | Where | Severity |
|---|---|---|
| `APP_DEBUG=true` in `.env` | `.env` line 4 | **High** if `APP_ENV` ever becomes `production` |
| `APP_KEY=base64:...` committed to `.env` | `.env` line 3 | **High** — `.env` is in `.gitignore`; verify |
| `BCRYPT_ROUNDS` not set | `.env` | Medium (Laravel default 10 is fine) |
| No `APP_URL=https://` for production | `.env` line 5 | Medium |
| CORS package mismatch | see §5.2 | Medium |

---

## 6. Confidence score

| Claim | Confidence |
|---|---|
| composer.json declares Laravel 11 + PHP 8.2 | 100% |
| Code is functionally Laravel 8 | 95% |
| composer.lock is missing | 100% |
| vendor/ contains the actual installed version | 80% (read `vendor/laravel/framework/composer.json` to confirm) |
| No tests exist | 100% |
| Database is not in the project | 100% |
| `package.json` is Vite | 100% |

**Final confidence: 90%** that the system is a Laravel 8 codebase declared as Laravel 11 in `composer.json`, with no DB, no tests, and a partial Mix→Vite migration.
