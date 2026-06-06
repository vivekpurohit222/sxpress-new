# Codebase Inventory — Saurashtra Express

> Generated 2026-06-05 by static scan of `app/`, `routes/`, `resources/views/`, `database/`, `config/`, `public/`.
> Companion: `project-analysis.md`.

---

## 1. Models (`app/Models/`)

### 1.1 `User.php`
- **Table:** `users`
- **Namespace:** `App\Models\User extends Authenticatable`
- **Traits:** `HasFactory, Notifiable, HasRoles` (Spatie)
- **Implements:** `MustVerifyEmail`
- **Fillable:** `name, email, password, office`
- **Hidden:** `password, remember_token`
- **Casts:** `email_verified_at => datetime`
- **Mutator:** `setPasswordAttribute($password)` → `bcrypt()`
- **Methods:** `officeall()` (returns `pluck('office')` for current user — but it returns a Collection, not a scalar; bug — see `ghost-field-audit.md §3.4`)
- **Relationships:** none defined; Spatie provides `roles()`, `permissions()`
- **Primary Key:** `id` (default)

### 1.2 `Gr.php`
- **Table:** `grs`
- **Fillable (25 fields):** `gr_no, from_dest, to_dest, copy_date, consignor, nor_adress, nor_gst_no, consignee, nee_adress, nee_gst_no, nugs, meth, eway_bill_number, bill_amount, paid, to_pay, other, description, pm, weight, frieght_amount, sur_ch, c_r, bc_amount, total_amount`
- **Methods:** `latestGrNumber()` (static helper)
- **Relationships:** none
- **Casts / hidden / soft-deletes:** none

### 1.3 `gatepass.php` (lowercase class — non-PSR-1)
- **Table:** `gatepasses`
- **Fillable (15 fields):** `gp_no, gp_date, m_s, from_dest, to_dest, gr_no, weight, nugs, pm, frieght_amount, labour_amount, other, dc_amount, total_amount, note`
- **Relationships:** none

### 1.4 `Freight.php`
- **Table:** `frieghts` (preserved misspelling)
- **Fillable (21 fields):** `fm_no, fm_date, from_dest, to_dest, truck_no, entry_1, entry_1_amount, entry_2, entry_2_amount, entry_3, entry_3_amount, entry_4, entry_4_amount, total_amount, truck_freight, commission, other_charges, extra, balance_to_sn, note`
- **Relationships:** none

### 1.5 `challan.php` (lowercase class)
- **Table:** `challans`
- **Fillable (10 fields):** `challan_no, from_dest, challan_date, to_dest, truck_no, driver_name, license, owner_name, note, challan_total`
- **PK is `challan_no` (string)** — no `id` column
- **Relationships:** none

### 1.6 `ChallanItem.php`
- **Table:** `challan_iteams` (preserved misspelling)
- **Fillable (11 fields):** `challan_no, gr_no, nugs, meth, description, weight, paid, to_pay, sur_ch, c_r, other`
- **Relationships:** none
- Note: the controller imports `App\Models\challan_iteam` (typo class) — see `ghost-field-audit.md §3.1`

### 1.7 `truckdriver.php` (lowercase class)
- **Table:** `truckdrivers`
- **Fillable (6 fields):** `driver_name, truck_no, license, mobile_no1, mobile_no2, driver_address`
- **Relationships:** none

### 1.8 `Post.php`
- **Table:** `posts`
- **Fillable:** `title, body`
- **Used by:** `ClearanceMiddleware` (sample/legacy). No controller class for it exists in `app/Http/Controllers/`.

---

## 2. Controllers (`app/Http/Controllers/`)

### 2.1 Root
| File | Class | Methods |
|---|---|---|
| `Controller.php` | `App\Http\Controllers\Controller` | (base) |
| `DashboardController.php` | `App\Http\Controllers\DashboardController` | empty |
| `HomeController.php` | `App\Http\Controllers\HomeController` | `index()` → view `home` |
| `TruckdriverController.php` | `App\Http\Controllers\TruckdriverController` | `index, create, store, show, edit, update, destroy` |
| `UserController.php` | `App\Http\Controllers\UserController` | `index, create, store, show, edit, update, destroy` + role assignment |
| `RoleController.php` | `App\Http\Controllers\RoleController` | `index, create, store, show, edit, update, destroy` + permission sync |
| `PermissionController.php` | `App\Http\Controllers\PermissionController` | `index, create, store, show, edit, update, destroy` |

### 2.2 `dash/` namespace
| File | Class | Methods |
|---|---|---|
| `dash/DashboardController.php` | `App\Http\Controllers\dash\DashboardController` | `index()` |
| `dash/GrController.php` | `App\Http\Controllers\dash\GrController` | `index, create, store, show, edit, update, destroy` |
| `dash/GatepassController.php` | `App\Http\Controllers\dash\GatepassController` | `index, create, store, show, edit, update, destroy` |
| `dash/ChallanController.php` | `App\Http\Controllers\dash\ChallanController` | `index, create, getData, challanfetchdata, challanIteamStore, store, show, edit, challandelete, update, destroy` |
| `dash/ChallanItemController.php` | `App\Http\Controllers\dash\ChallanItamsController` | empty (stub) |
| `dash/FreightController.php` | `App\Http\Controllers\dash\FreightController` | `index, create` (others stubs) |

### 2.3 `Auth/` namespace
7 default Laravel 8 controllers: `LoginController, RegisterController, ForgotPasswordController, ResetPasswordController, ConfirmPasswordController, VerificationController, HomeController` (Auth\HomeController — **note: not the same as root `HomeController`**).

---

## 3. Routes (`routes/`)

### 3.1 `routes/web.php` (full)
See `project-analysis.md §3` for the full table (47 business routes + 4 resource routes).

### 3.2 `routes/api.php`
Default scaffolding (`/api/user` behind `auth:api`). Not used by the business app.

### 3.3 `routes/channels.php`, `routes/console.php`
Default scaffolding. Empty (no broadcast channels, no scheduled commands).

### 3.4 Routes by HTTP verb
| Verb | Count | % |
|---|---|---|
| GET | 30 | 60% |
| POST | 6 | 12% |
| PATCH | 6 | 12% |
| DELETE | 5 | 10% |
| Resource (multi-verb) | 3 | 6% |

---

## 4. Views (`resources/views/`)

### 4.1 Forms
| File | Module | Form fields (count) |
|---|---|---|
| `auth/login.blade.php` | Auth | 3 (email, password, remember) |
| `auth/register.blade.php` | Auth | 4 (name, email, password, password_confirmation) |
| `auth/passwords/email.blade.php` | Auth | 1 (email) |
| `auth/passwords/reset.blade.php` | Auth | 2 (email, password) |
| `auth/passwords/confirm.blade.php` | Auth | 1 (password) |
| `auth/verify.blade.php` | Auth | 0 |
| `admin/category/copies.blade.php` | GR | 25+ (full GR form, see `form-field-map.md`) |
| `admin/category/copies_edit.blade.php` | GR | 25+ |
| `admin/category/Gatepass/gate_pass.blade.php` | Gatepass | 16+ |
| `admin/category/Gatepass/gate_pass_edit.blade.php` | Gatepass | 16+ (includes ghost `gst_amount` input) |
| `admin/category/FrieghtMemo/Frieght_memo.blade.php` | Freight Memo | ~20 — but **inputs lack `name` attributes** (form posts nothing) |
| `admin/category/FrieghtMemo/Frieght_memo_edit.blade.php` | Freight Memo | same as above |
| `admin/category/challan/challan.blade.php` | Challan | AJAX-driven; no traditional form |
| `admin/category/challan/challan_edit.blade.php` | Challan | AJAX-driven |
| `admin/category/TruckDriver/truck_driver.blade.php` | Truck | 6 |
| `admin/category/TruckDriver/truck_driver_edit.blade.php` | Truck | 6 |
| `admin/category/register.blade.php` | (legacy — duplicated by `users/create.blade.php`) | — |
| `admin/category/register_edit.blade.php` | (legacy) | — |
| `admin/category/freightmemo.blade.php` | (legacy — duplicated by FrieghtMemo/) | — |
| `users/create.blade.php` | Users | 4 |
| `users/edit.blade.php` | Users | 4 |
| `roles/create.blade.php` | Roles | 1 + multi-select permissions |
| `roles/edit.blade.php` | Roles | 1 + multi-select |
| `permissions/create.blade.php` | Permissions | 1 |
| `permissions/edit.blade.php` | Permissions | 1 |
| `posts/create.blade.php` | Posts (legacy) | 2 |
| `posts/edit.blade.php` | Posts (legacy) | 2 |

### 4.2 Tables (list pages)
| File | Module | Columns |
|---|---|---|
| `copies_list.blade.php` | GR | gr_no, date, from, to, consignor, consignee, weight, freight, total + action buttons |
| `Gatepass/gate_pass_list.blade.php` | Gatepass | gp_no, date, gr_no, m_s, from, to, freight, total + actions |
| `FrieghtMemo/Frieght_memo_list.blade.php` | Freight Memo | fm_no, date, truck_no, total + actions |
| `challan/challan_list.blade.php` | Challan | challan_no, date, truck, driver, from, to, total + actions |
| `challan/challan_all.blade.php` | Challan | (combined view) |
| `TruckDriver/truck_driver_list.blade.php` | Truck | truck_no, driver, license, mobiles + actions |
| `register_list.blade.php` | (legacy) | — |
| `users/index.blade.php` | Users | name, email, office, roles + actions |
| `roles/index.blade.php` | Roles | name, permissions + actions |
| `permissions/index.blade.php` | Permissions | name + actions |
| `posts/index.blade.php` | Posts (legacy) | title + actions |

### 4.3 Reports
- **None.** The application has no dedicated report views. All "reports" are table listings. The print template `copies_print.blade.php` is the only formatted output.

### 4.4 Print layouts
| File | Used by |
|---|---|
| `admin/category/copies_print.blade.php` | GR print (`/dash/gr/{id}/print`) **and** Gatepass print (`/dash/gatepass/{id}/print`) |

### 4.5 Layouts
| File | Used by |
|---|---|
| `admin/layout/master.blade.php` | All `/dash/*` pages |
| `admin/layout/top.blade.php` | Master partial — head |
| `admin/layout/navigation.blade.php` | Master partial — sidebar |
| `admin/layout/header.blade.php` | Master partial — top bar |
| `admin/layout/bottom.blade.php` | Master partial — scripts |
| `layouts/app.blade.php` | Default (unused) |

---

## 5. Middleware (`app/Http/Middleware/`)

| File | Alias | Notes |
|---|---|---|
| `Authenticate.php` | `auth` | standard |
| `AdminMiddleware.php` | `isAdmin` | counts users on every request — bug |
| `ClearanceMiddleware.php` | `clearance` | dead code (gates `posts.create` only) |
| `EncryptCookies.php` | default | OK |
| `PreventRequestsDuringMaintenance.php` | default | OK |
| `RedirectIfAuthenticated.php` | default | OK |
| `TrimStrings.php` | default | OK |
| `TrustHosts.php` | default | registered but disabled in Kernel |
| `TrustProxies.php` | default | OK |
| `VerifyCsrfToken.php` | default | OK |

See `security-audit.md §3` for the per-middleware risk assessment.

---

## 6. Services / Repositories / Jobs / Events / Helpers

| Layer | Files | Notes |
|---|---|---|
| Services | **0** | none — all logic in controllers |
| Repositories | **0** | none — Eloquent used directly in controllers |
| Jobs | **0** | none — `QUEUE_CONNECTION=sync` anyway |
| Events / Listeners | **0** | none — `EventServiceProvider` has empty `$listen` array |
| Mailables | **0** | none |
| Notifications | **0** | none (User has `Notifiable` trait but no usage) |
| Form Requests | **0** | none — all validation is inline `$request->validate(...)` |
| Helpers | **0** | no `app/Helpers/` directory; no `helpers.php` autoloaded |
| Casts | **0** | no `app/Casts/` |
| Observers | **0** | no `app/Observers/` |
| Policies | **0** | `AuthServiceProvider::$policies = []` |
| Console Commands | **0** | `app/Console/Kernel.php` `$commands = []` |

---

## 7. Config files (`config/`)

| File | Notable settings |
|---|---|
| `app.php` | `timezone = UTC`, `locale = en`, providers listed |
| `auth.php` | `App\Models\User` as provider; `web` + `api` guards |
| `broadcasting.php` | `log` driver (no real broadcasting) |
| `cache.php` | `file` driver (default) |
| `cors.php` | fruitcake/laravel-cors paths (will be superseded by L11 built-in) |
| `database.php` | mysql primary, sqlite memory for tests |
| `filesystems.php` | local + public disks |
| `hashing.php` | bcrypt default |
| `logging.php` | `stack` channel (default) |
| `mail.php` | smtp; `MAIL_HOST=mailhog` from `.env` |
| `permission.php` | spatie/laravel-permission default |
| `queue.php` | `sync` connection (background jobs run inline) |
| `services.php` | default empty |
| `session.php` | `file` driver, `lifetime = 120` |
| `view.php` | default paths |

---

## 8. Public assets (`public/`)

- `public/css/copies_print.css` — print stylesheet
- `public/images/` — (need to verify what is here; some views reference `G:\revan\img1.jpg` which is a hard-coded Windows path and will not work)
- `public/index.php` — front controller
- `public/.htaccess` — Apache rewrite rules
- `public/js/`, `public/fonts/`, `public/webfonts/` — admin theme assets (assumed standard adminLTE-derivative)

## 9. Storage (`storage/`)

- `storage/app/public/` — public uploads (POD, scan images — none yet)
- `storage/framework/cache/data/` — file cache
- `storage/framework/sessions/` — file sessions
- `storage/framework/testing/` — test artifacts
- `storage/framework/views/` — compiled Blade
- `storage/logs/laravel.log` — runtime log
