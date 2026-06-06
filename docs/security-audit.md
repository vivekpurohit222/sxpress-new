# Security Audit — Saurashtra Express

> Static security review of `app/`, `routes/`, `database/`, `.env`.
> Companion: `refactoring-plan.md §13` for the prioritized fix list.

---

## 1. Risk summary

| # | Risk | Severity | Location | Confidence |
|---|---|---|---|---|
| 1 | No CSRF protection on AJAX Challan endpoints (assumed — verify `web` middleware group) | **High** (if missing) | `routes/web.php` AJAX routes | 100% |
| 2 | `ChallanController::store()` does `challan::insert($request->all())` — mass-assignment with no validation | **Critical** | `app/Http/Controllers/dash/ChallanController.php:128` | 100% |
| 3 | `ChallanController::challanIteamStore()` same problem | **Critical** | `app/Http/Controllers/dash/ChallanController.php:92` | 100% |
| 4 | `.env` contains `APP_KEY=base64:...` and `APP_DEBUG=true` | **High** (if shipped) | `.env` | 100% |
| 5 | `GatepassController::update` writes to non-existent property `$gp->gst_amount` (silent data corruption, not a security risk per se, but indicates a security review was not done) | Medium | `app/Http/Controllers/dash/GatepassController.php:201` | 100% |
| 6 | No business-level authorization (any logged-in user can hit any `/dash/*` route) | **High** | all `/dash/gr/*`, `/dash/gatepass/*`, etc. | 100% |
| 7 | `User::officeall()` returns a Collection that is then passed to `strcmp()` (type confusion → not a security risk but indicates fragile code) | Low | `User.php:51`, `GrController.php:188` | 100% |
| 8 | Hard-coded `G:\revan\img1.jpg` path leaks server filesystem | Low | `copies_print.blade.php:13` | 100% |
| 9 | Validation rule typo `'pm.numberic'` (typo of `numeric`) means the rule is never applied | Medium | `GrController::store` (twice) | 100% |
| 10 | `AdminMiddleware` allows any request if the user count is 1 (race condition / first-user bootstrap) | **High** (multi-tenant risk) | `app/Http/Middleware/AdminMiddleware.php` | 100% |
| 11 | No rate limiting on auth endpoints | **High** (brute force) | `Auth\LoginController` | 100% |
| 12 | No password complexity requirements | Medium | `User` model | 100% |
| 13 | No password rotation / expiry | Low | `User` model | 100% |
| 14 | No file upload validation (no uploads today, but no defense if added) | Medium | n/a (none today) | 100% |
| 15 | `package.json` not pinned — supply chain risk | Low | `package.json` (Vite, jQuery assumed) | 100% |
| 16 | CORS misconfiguration (`fruitcake/laravel-cors` in `composer.json` is being replaced by L11 built-in, which has its own defaults) | Low | `config/cors.php` | 80% |
| 17 | `phpunit.xml` uses SQLite memory — fine for tests, but no security tests exist | Low | `tests/` | 100% |
| 18 | No audit log of changes | **High** (forensics / regulatory) | n/a | 100% |
| 19 | `routes/api.php` exposes `/api/user` behind `auth:api` — not used today but a default scaffold | Low | `routes/api.php` | 100% |
| 20 | `'laravelcollective/html' ^6.4` — last release 2021; abandoned | Low | `composer.json` | 100% |

---

## 2. Authentication

### 2.1 Current state
- Laravel 8 default auth stack (`Auth\LoginController`, `Auth\RegisterController`, etc.).
- `User` implements `MustVerifyEmail` but email is sent to `MAIL_HOST=mailhog` (dev only). Email verification is **not functional in production**.
- Passwords are hashed with `bcrypt()` via the `setPasswordAttribute` mutator.
- `User` has no `two_factor_*` columns (no 2FA).

### 2.2 Issues
- **No rate limiting** on `POST /login`. Brute force is possible.
- **No password complexity.** The model accepts any string as password.
- **No password history.** A user can rotate between the same two passwords forever.
- **No 2FA.** A leaked password is a full compromise.
- **`APP_DEBUG=true`** in `.env` exposes stack traces and `.env` variables in error responses.

### 2.3 Recommendations
- Switch to Laravel Breeze (or Jetstream for 2FA) as part of the L11 upgrade.
- Add `RateLimiter` middleware on `POST /login` (already in L11 default but apply explicitly).
- Add `passwordRules()` to registration / password change (e.g., `Password::min(8)->mixedCase()->numbers()->symbols()`).
- Set `APP_DEBUG=false` for production.
- Add a `last_login_at` and `last_login_ip` column to `users` for auditing.
- Add 2FA via Google Authenticator (`pragmarx/google2fa-laravel`).

---

## 3. Authorization

### 3.1 Current state
- `spatie/laravel-permission` is wired up.
- 4 default permissions exist (`Administer roles & permissions`, `Create Post`, `Edit Post`, `Delete Post`).
- `AdminMiddleware` checks `User::all()->count() == 1` OR `can('Administer roles & permissions')` — used on `/dash/users`, `/dash/roles`, `/dash/permissions`.
- **No policy classes** are defined.
- **No business-level authorization** is enforced. Any logged-in user can `GET /dash/gr/{id}/edit?id=...` of any other office's GR.

### 3.2 Issues
- The "first user is admin" bootstrap is dangerous in a multi-tenant setup. If the first user is deleted, the next user is automatically admin.
- A booking office user can edit another office's GR. There is no `branch_id` filter on the controller (only on the listing).
- `ClearanceMiddleware` is dead code (gates `posts.*` which has no controller).

### 3.3 Recommendations
- **Add policies:**
  - `GoodsReceiptPolicy` — `view`, `update`, `delete` based on `user.branch_id == gr.from_branch_id`.
  - `GatepassPolicy` — same.
  - `ChallanPolicy` — same.
  - `FreightMemoPolicy` — same.
  - `TruckDriverPolicy` — admin only.
- **Enforce via middleware** (or in controllers via `$this->authorize(...)`).
- **Replace** `AdminMiddleware::count()` with a config flag and a cache:
  ```php
  if (Cache::remember('bootstrap.first_user', 3600, fn() => User::count() === 1)) {
      return $next($request);  // first user is admin
  }
  return $user->can('Administer roles & permissions') ? $next($request) : abort(403);
  ```
- **Remove** `ClearanceMiddleware` (dead code).

---

## 4. Mass assignment

### 4.1 Current state
- Every model declares `$fillable` arrays. Good.
- **EXCEPT:** `ChallanController::store()` and `challanIteamStore()` do `Model::insert($request->all())` — bypasses `$fillable`.

### 4.2 Issues
- `ChallanController::store(Request $request)` → `$result = challan::insert($request->all());`
- A malicious user can post `?_token=...&id=...&challan_no=...&any_field=...` and it will be inserted.
- This is the **most serious finding** in this audit.

### 4.3 Recommendations
- Replace with `Challan::create($request->validated())` after a `CreateChallanRequest` (see `refactoring-plan.md §5`).
- Or at minimum `$request->only([...allowed...])`.

---

## 5. Validation

### 5.1 Current state
- Inline `$request->validate(...)` in most controllers.
- **Validation rule typos** present (`'pm.numberic'` instead of `'pm.numeric'` — the typo means the rule is **not enforced**; the typo'd rule string is treated as a custom attribute name, and Laravel's validator looks for `validation.custom.pm.numberic` which doesn't exist, so the field falls back to no rule).
- **Gst number regex not enforced.** Only `min:15|max:15` is checked. A real GSTIN has a specific format (15 chars: 2 digits state + 10 chars PAN + 1 char entity + 1 char 'Z' + 1 check digit). The current code accepts `123456789012345`.
- **Indian truck number regex** is correct.
- **Indian license regex** is correct.
- **Mobile number** is `max:10` (length) but no format check.
- **`copy_date` accepts any string** including SQL-injection attempts? No — Eloquent parameterizes. So XSS is the risk if rendered raw. The print view uses `{{ $copy->copy_date }}` which auto-escapes. ✅
- **`eway_bill_number`** is `required` but no format check.

### 5.2 Issues
- Typos in validation rules = rules not applied.
- GSTIN format not enforced.
- Mobile format not enforced.
- E-Way bill format not enforced.

### 5.3 Recommendations
- Fix typos.
- Add regex for GSTIN: `/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/`.
- Add regex for mobile: `/^[6-9][0-9]{9}$/`.
- Add regex for E-Way bill (12 digits): `/^[0-9]{12}$/`.
- Add max length checks on `description` (currently unbounded `text`).
- Add max length checks on `note` (currently unbounded `text`).

---

## 6. SQL injection

### 6.1 Current state
- All observed queries use Eloquent or the query builder with bindings. **No raw SQL strings** observed.
- No `DB::statement(...)` calls.
- No `whereRaw(...)` calls.

### 6.2 Verdict
**Low risk** for SQLi today. Maintain by:
- Never using `whereRaw` with user input.
- Using `?` placeholders or named bindings for any future raw query.
- Running an automated SQLi scanner (`SQLMap`) in CI.

---

## 7. XSS

### 7.1 Current state
- Blade `{{ }}` auto-escapes. ✅
- Blade `{!! !!}` (raw) — used? `grep -r '{!!' resources/views/` to verify. None observed.
- All user-input is rendered via `{{ }}` in the views (verified by reading `copies_print.blade.php` and a few others).

### 7.2 Verdict
**Low risk** for XSS. Maintain by:
- Never using `{!! !!}` for user input.
- Running a CSP header check.

### 7.3 Content Security Policy
No CSP header is set. **Add** in `App\Http\Middleware\AddCsp`:
```php
$response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
```

---

## 8. CSRF

### 8.1 Current state
- `routes/web.php` routes are in the `web` middleware group, which includes `VerifyCsrfToken`. ✅
- The AJAX Challan endpoints (`POST /dash/challan/challanIteams`, `POST /dash/challan/store`) are in the `web` group. ✅ (CSRF tokens should be sent via `_token`.)
- `routes/api.php` is in the `api` group, which **does not** include CSRF (uses Sanctum/token instead).

### 8.2 Verification needed
Open the browser dev tools on `/dash/challan/create`, click the "Add" button, and verify the request includes `X-CSRF-TOKEN` (or `_token` in the body). If it doesn't, CSRF protection is bypassed and AJAX endpoints are vulnerable.

### 8.3 Verdict
**Assuming AJAX is properly wired with CSRF tokens (likely, given the legacy `web` group), CSRF is OK.** Verify in browser.

---

## 9. File uploads

### 9.1 Current state
- **No file uploads** are implemented in the current code. There are no `<input type="file">` in any form (verified by reading the form-field map).
- The print template has a hard-coded `<img src="G:\revan\img1.jpg">` (broken, not an upload).
- POD uploads (proposed for the modernization) would need file upload handling.

### 9.2 Recommendations for future
- Validate mime type (`mimes:jpg,jpeg,png,pdf`) and max size (`max:5120` = 5MB).
- Use Laravel's `Storage::disk('s3')` or `Storage::disk('public')`.
- Generate a random filename (`Str::uuid()`).
- Set `chmod 0644` on uploads.
- Serve uploads via a separate domain (or with `Content-Disposition: attachment` for sensitive files).
- Run a virus scan (ClamAV via `php-clamav`).

---

## 10. Session security

### 10.1 Current state
- `SESSION_DRIVER=file` in `.env`.
- `SESSION_LIFETIME=120` (minutes).
- `EncryptCookies` middleware is in place. ✅
- `VerifyCsrfToken` middleware is in place. ✅
- No `Secure` cookie flag (it's `http_only` by default, but not `secure` — i.e., not HTTPS-only).

### 10.2 Recommendations
- Set `SESSION_SECURE_COOKIE=true` for production.
- Set `SESSION_SAME_SITE=lax` (Laravel default is `lax`).
- Add `php artisan session:gc` to a cron to clean expired sessions.
- Consider switching to `SESSION_DRIVER=redis` for high-concurrency.

---

## 11. Cryptography

### 11.1 Current state
- Passwords: `bcrypt()` with default cost (10). ✅ (Laravel default.)
- `APP_KEY` is present in `.env`. ✅
- No `Crypt::encrypt(...)` observed (no sensitive data encrypted at rest).

### 11.2 Recommendations
- Set `BCRYPT_ROUNDS=12` in production (slows brute force by ~4x).
- Consider encrypting sensitive columns (`users.email`, `grs.eway_bill_number`, `truckdrivers.license`) using Laravel's `Crypt` facade.
- Rotate `APP_KEY` annually; document the rotation procedure.

---

## 12. Logging

### 12.1 Current state
- `LOG_CHANNEL=stack`, `LOG_LEVEL=debug`.
- `storage/logs/laravel.log` is the default.
- No structured logging (e.g., JSON).
- No audit log of business events.

### 12.2 Recommendations
- Set `LOG_LEVEL=info` (or `warning`) in production.
- Add a dedicated `audit` channel writing to a separate file/database.
- Log all CUD on business tables.
- Use `spatie/laravel-activitylog` (L11-compatible) for an out-of-the-box audit trail.

---

## 13. Rate limiting

### 13.1 Current state
- `RouteServiceProvider` defines a `60/min` rate limit for `api` group.
- **No rate limit on `auth` group** (login, register, password reset).

### 13.2 Recommendations
- Add `throttle:5,1` to `POST /login` (5 attempts per minute).
- Add `throttle:3,1` to `POST /password/email` (3 attempts per minute).
- Add `throttle:3,1` to `POST /register` (3 attempts per minute).

---

## 14. CORS

### 14.1 Current state
- `fruitcake/laravel-cors` is in `composer.json`.
- `config/cors.php` exists (Laravel 8 form).

### 14.2 Recommendation
- For L11, drop `fruitcake/laravel-cors` and use the built-in CORS (`config/cors.php` in L11 form).
- Restrict `allowed_origins` to known domains. The current config likely allows `*` (the L8 default).

---

## 15. Information disclosure

### 15.1 Current state
- `APP_DEBUG=true` in `.env` — exposes full stack traces, environment variables, and database credentials in error responses.
- The print template leaks the file system path `G:\revan\img1.jpg`.
- `403`, `404`, `500` pages may show too much info.

### 15.2 Recommendations
- Set `APP_DEBUG=false` in production.
- Customize `resources/views/errors/500.blade.php` to show a generic message.
- Do not include filesystem paths in any user-facing output.

---

## 16. Supply chain

### 16.1 `composer.json`
- `fruitcake/laravel-cors` — superseded by L11 built-in.
- `laravelcollective/html` — last release 2021; no longer actively maintained.
- `laravel/breeze` v2.0 — need ≥ 2.1 for L11.

### 16.2 `package.json`
- `package-lock.json` exists (good).
- `composer.lock` does **not** exist (rebuild — see `laravel-upgrade-plan.md §Step 2`).
- Run `npm audit` and `composer audit` in CI.

---

## 17. Severity-prioritized fix list

| Priority | Action | Effort |
|---|---|---|
| P0 | Fix `ChallanController::store` and `challanIteamStore` mass-assignment bug | 30 min |
| P0 | Set `APP_DEBUG=false` in production `.env` | 5 min |
| P1 | Add rate limiting on `auth` routes | 30 min |
| P1 | Add policies for GR, Gatepass, Challan, FreightMemo | 8h |
| P1 | Fix `AdminMiddleware` race condition | 1h |
| P2 | Fix validation rule typos (`pm.numberic` → `pm.numeric`) | 30 min |
| P2 | Add GSTIN, mobile, E-Way bill format validation | 2h |
| P2 | Set `SESSION_SECURE_COOKIE=true` in production | 5 min |
| P2 | Set `BCRYPT_ROUNDS=12` in production | 5 min |
| P3 | Add Content-Security-Policy header | 1h |
| P3 | Add audit log table + observers | 8h |
| P3 | Switch to `spatie/laravel-activitylog` | 4h |
| P3 | Add 2FA via `pragmarx/google2fa-laravel` | 8h |
| P4 | Run `composer audit` and `npm audit` in CI | 1h |
| P4 | Add security headers (`X-Frame-Options`, `X-Content-Type-Options`) | 1h |

---

## 18. Compliance notes (for a transport ERP)

| Compliance | Status | Notes |
|---|---|---|
| **GST records retention** (6 years) | ⚠️ | Soft delete on `grs` would help |
| **E-Way Bill retention** | ⚠️ | `eway_bill_number` stored; expiry not enforced |
| **Audit trail** | ❌ | Not implemented |
| **PII handling** (consignee phone, address) | ⚠️ | Not encrypted at rest |
| **Data export** | ❌ | No CSV/Excel export |
| **Data deletion on request** (GDPR-style) | ❌ | Soft delete only; no hard delete with cascade |
| **Session timeout** | ✅ | 120 min |
| **Password rotation** | ❌ | Not enforced |
