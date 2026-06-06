# Project Analysis — Saurashtra Express Transport ERP

> Application: **Saurashtra Express** — Transport / Logistics ERP
> Framework declared in prompt: Laravel 8
> Framework declared in `composer.json`: Laravel `^11.0` (PHP `^8.2`) — see §1.1
> Status: Working legacy system. No database available. Reconstructive modernization in scope.

---

## 1. Tech Stack (observed)

### 1.1 Version discrepancy — flagged for resolution
| Source | Laravel | PHP |
|---|---|---|
| User brief | "Laravel 8" | unspecified |
| `composer.json` | `^11.0` | `^8.2` |
| `app/Http/Kernel.php` middleware groups (`api` uses `throttle:api` named limiter, `AddQueuedCookiesToResponse` fully-qualified, `ShareErrorsFromSession`) | **Laravel 7+ shape** |
| `app/Http/Controllers/Auth/*` (presence of `ConfirmPasswordController`, `VerificationController`, `Auth\LoginController` with `username()`) | **Laravel 8 skeleton** |
| `app/Models/User.php` — uses `MustVerifyEmail`, `Notifiable`, `setPasswordAttribute` mutator pattern | Laravel 8 era |
| `routes/web.php` uses string controller classes (`'App\Http\Controllers\UserController'`) in `Route::resource` | **Laravel 8** syntax |
| `database/migrations` timestamp prefix `2014_10_12_000000` through `2020_12_09_090031` | matches original creation dates |

**Conclusion:** The code is **Laravel 8-era** (built late 2020). The `composer.json` has been partially upgraded to Laravel 11. This is a half-migrated project. Action required: either pin `composer.json` to Laravel 8 to match the code, or perform the Laravel 11 upgrade outlined in `laravel-upgrade-plan.md`. **Recommend the upgrade path** since the rest of the stack (PHP 8.2, Spatie 6) is already there.

### 1.2 Other packages
| Package | Version | Role |
|---|---|---|
| `spatie/laravel-permission` | `^6.0` | Roles + Permissions (SpP v6) |
| `laravelcollective/html` | `^6.4` | `Form`/`Html` facades — **imported by neither controller** but package is loaded; we keep it for migration compatibility |
| `laravel/breeze` (dev) | `^2.0` | Scaffolding (not actively used — auth uses the legacy `Auth\LoginController` stack) |
| `fruitcake/laravel-cors` | `^3.0` | CORS middleware |
| `guzzlehttp/guzzle` | `^7.9` | HTTP client |
| PHPUnit | `^11.0` | Tests (no test files exist outside defaults) |

### 1.3 Database
- `DB_CONNECTION=mysql`, `DB_DATABASE=sxpress`, `DB_HOST=127.0.0.1`
- **No MySQL dump available.** Database is reconstructed in `database-reconstruction-report.md`.

### 1.4 Frontend
- Server-rendered Blade only.
- Assets bundled with `webpack.mix.js` and `vite.config.js` (both present — `package.json` is Vite-only).
- CSS: `admin/css/*` (admin theme), `public/css/copies_print.css` (print).
- JS: jQuery + AJAX (Challan GR search).
- **No SPA**, no Inertia, no Livewire.

---

## 2. Module Inventory

| # | Module | Routes | Controllers | Models | Views | Status |
|---|---|---|---|---|---|---|
| 1 | Authentication | `Auth::routes()` | `Auth/*` (7) | `User` | `auth/*` | Working (Laravel 8 stack) |
| 2 | Authorization (Roles/Permissions) | `dash.users`, `dash.roles`, `dash.permissions` | `UserController`, `RoleController`, `PermissionController` | `User`, `Spatie\Permission\Models\Role`, `Spatie\Permission\Models\Permission` | `users/*`, `roles/*`, `permissions/*` | Working |
| 3 | Dashboard | `dash/` | `dash\DashboardController` | — | `admin/dashboard.blade.php` | Empty stub — only renders layout |
| 4 | **GR (Goods Receipt)** | `dash.gr.*` | `dash\GrController` | `Gr` | `admin/category/copies*` | **Core module — full CRUD + print** |
| 5 | **Gate Pass** | `dash.gatepass.*` | `dash\GatepassController` | `gatepass` | `admin/category/Gatepass/*` | **Core module — full CRUD + print** |
| 6 | **Freight Memo** | `dash.frieghtmemo.*` | `dash\FrieghtController` | `Freight` | `admin/category/FrieghtMemo/*` | **Module is read-only** — `store/update/destroy` are stubs |
| 7 | **Challan** | `dash.challan.*` | `dash\ChallanController`, `dash\ChallanItemController` | `challan`, `ChallanItem` | `admin/category/challan/*` | **Module is read-only + AJAX-driven creation** |
| 8 | **Truck / Driver** | `dash.truckdriver.*` | `TruckdriverController` | `truckdriver` | `admin/category/TruckDriver/*` | **Full CRUD** |
| 9 | **Posts (sample)** | `dash.posts` (resource) | `PostController` | `Post` | `posts/*` | Sample/legacy — has no business use |
| 10 | Print Formats | — | — | — | `admin/category/copies_print.blade.php` | GR print; also reused for Gatepass print |

---

## 3. Route Inventory (web.php)

### 3.1 Public / Auth
| Method | URI | Action | Name |
|---|---|---|---|
| GET | `/` | `view('auth.login')` | — |
| * | `login`, `register`, `password/reset`, `password/confirm`, `password/email`, `verification.notice`, `verification.verify`, `verification.resend`, `logout` | `Auth::routes()` | standard |

### 3.2 Dashboard group (`/dash`)
| Method | URI | Action | Name |
|---|---|---|---|
| GET | `/dash` | `DashboardController@index` | `dash` |
| GET | `/dash/gr` | `GrController@index` | — |
| POST | `/dash/gr/store` | `GrController@store` | — |
| GET | `/dash/gr/create` | `GrController@create` | — |
| GET | `/dash/gr/{id}/edit` | `GrController@edit` | `copies_edit` |
| PATCH | `/dash/gr/{id}/update` | `GrController@update` | `copies_update` |
| DELETE | `/dash/gr/{id}/delete` | `GrController@destroy` | `copies_destroy` |
| GET | `/dash/gr/{id}/print` | `GrController@show` | `copies_priny` *(typo preserved)* |
| GET | `/dash/gatepass` | `GatepassController@index` | — |
| GET | `/dash/gatepass/create` | `GatepassController@create` | — |
| POST | `/dash/gatepass/store` | `GatepassController@store` | — |
| GET | `/dash/gatepass/{id}/edit` | `GatepassController@edit` | `gatepass_edit` |
| PATCH | `/dash/gatepass/{id}/update` | `GatepassController@update` | `gatepass_update` |
| DELETE | `/dash/gatepass/{id}/delete` | `GatepassController@destroy` | `gatepass_destroy` |
| GET | `/dash/gatepass/{id}/print` | `GatepassController@show` | `gatepass_priny` |
| GET | `/dash/truckdriver` | `TruckdriverController@index` | — |
| GET | `/dash/truckdriver/create` | `TruckdriverController@create` | — |
| POST | `/dash/truckdriver/store` | `TruckdriverController@store` | — |
| GET | `/dash/truckdriver/{id}/edit` | `TruckdriverController@edit` | `truckdriver_edit` |
| PATCH | `/dash/truckdriver/{id}/update` | `TruckdriverController@update` | `truckdriver_update` |
| DELETE | `/dash/truckdriver/{id}/delete` | `TruckdriverController@destroy` | `truckdriver_destroy` |
| GET | `/dash/truckdriver/{id}/view` | `TruckdriverController@show` | `truckdriver_view` |
| GET | `/dash/frieghtmemo` | `FreightController@index` | — |
| GET | `/dash/frieghtmemo/create` | `FreightController@create` | — |
| POST | `/dash/frieghtmemo/store` | `FreightController@store` *(stub)* | — |
| GET | `/dash/frieghtmemo/{id}/edit` | `FreightController@edit` *(stub)* | `frieght_edit` |
| PATCH | `/dash/frieghtmemo/{id}/update` | `FreightController@update` *(stub)* | `frieght_update` |
| DELETE | `/dash/frieghtmemo/{id}/delete` | `FreightController@destroy` *(stub)* | `frieght_destroy` |
| GET | `/dash/frieghtmemo/{id}/view` | `FreightController@show` *(stub)* | `frieght_view` |
| GET | `/dash/challan` | `ChallanController@index` | — |
| GET | `/dash/challan/create` | `ChallanController@create` | — |
| GET | `/dash/challan/{id}/getData` | `ChallanController@getData` | — |
| GET | `/dash/challan/{id}/challanfetchdata` | `ChallanController@challanfetchdata` | — |
| POST | `/dash/challan/challanIteams` | `ChallanController@challanIteamStore` | — |
| POST | `/dash/challan/store` | `ChallanController@store` | — |
| GET | `/dash/challan/{id}/edit` | `ChallanController@edit` | `challan_edit` |
| GET | `/dash/challan/edit/{id}` | `ChallanController@challandelete` *(named for delete)* | — |
| PATCH | `/dash/challan/{id}/update` | `ChallanController@update` *(stub)* | `challan_update` |
| DELETE | `/dash/challan/{id}/delete` | `ChallanController@destroy` *(stub)* | `challan_destroy` |
| GET | `/dash/challan/{id}/view` | `ChallanController@show` *(stub)* | `challan_view` |
| * | `/dash/users` | `UserController` resource | `users.*` |
| * | `/dash/roles` | `RoleController` resource | `roles.*` |
| * | `/dash/permissions` | `PermissionController` resource | `permissions.*` |
| * | `/dash/posts` | `PostController` resource | `posts.*` |

### 3.3 API
- `GET /api/user` (auth:api) — default scaffolding; unused.

---

## 4. Controller Inventory

| Controller | File | LOC | Auth | Validation | Authorization | Notes |
|---|---|---|---|---|---|---|
| `dash\DashboardController` | `app/Http/Controllers/dash/DashboardController.php` | 93 | `auth` (constructor) | none | none | Stub — only `index` returns the layout |
| `dash\GrController` | `dash/GrController.php` | 580 | none on ctor; relies on global | Inline `$request->validate()` with custom messages | none | **GR logic** + number-generation per office |
| `dash\GatepassController` | `dash/GatepassController.php` | 224 | none | Inline | none | **Gatepass logic**; query-builder join with `grs` |
| `dash\FrieghtController` | `dash/FreightController.php` | 92 | none | none | none | **Read-only** — `store/update/destroy` empty stubs |
| `dash\ChallanController` | `dash/ChallanController.php` | 192 | none | none | none | AJAX-driven; uses `challan_iteam` *(typo class — see §11)* |
| `dash\ChallanItemController` | `dash/ChallanItemController.php` | 86 | none | none | none | Empty stub class, no methods implemented |
| `TruckdriverController` | `Http/Controllers/TruckdriverController.php` | 170 | none | Inline + regex | none | **Full CRUD** with truck-no + license regex |
| `UserController` | `Http/Controllers/UserController.php` | 147 | `auth` + `isAdmin` | Inline | Spatie `assignRole`/`roles()->sync()` | **Full CRUD** + role assignment |
| `RoleController` | `Http/Controllers/RoleController.php` | 151 | `auth` + `isAdmin` | Inline | Spatie `givePermissionTo`/`revokePermissionTo` | **Full CRUD** |
| `PermissionController` | `Http/Controllers/PermissionController.php` | 143 | `auth` + `isAdmin` | Inline | Spatie | **Full CRUD**; hard-blocks deletion of `Administer roles & permissions` |
| `DashboardController` (root) | `Http/Controllers/DashboardController.php` | 11 | none | none | none | Empty stub |
| `HomeController` | `Http/Controllers/HomeController.php` | 29 | `auth` | none | none | Returns `view('home')` (legacy default template) |
| `PostController` | (no file present) | 0 | — | — | — | Referenced in routes but **the controller file does not exist** — this is a broken route. See §11. |
| `Auth\*` (7) | `Http/Controllers/Auth/*` | — | — | — | — | Default Laravel 8 auth scaffolding, untouched |

---

## 5. Model Inventory

| Model | Table (actual) | PK | `$fillable` size | SoftDeletes | Relationships | Notes |
|---|---|---|---|---|---|---|
| `User` | `users` | id | 4 | no | `HasRoles` (Spatie), `officeall()` static helper | Defines `office` field; `setPasswordAttribute` mutator |
| `Post` | `posts` | id (incr) | 2 | no | none | Sample/legacy; no real business role |
| `Gr` | `grs` | id | 25 | no | none | `latestGrNumber()` static helper |
| `challan` | `challans` | challan_no (string) | 10 | no | none | `challan_no` is the PK (no `id` column!) |
| `ChallanItem` | `challan_iteams` | id | 11 | no | none | Filename `ChallanItem.php` vs class `ChallanItem` (PSR-4 mismatch with `challan_iteam` used in `ChallanController` — see §11) |
| `gatepass` | `gatepasses` | id | 15 | no | none | Class name lowercase; loaded via `gatepass::all()` etc. |
| `truckdriver` | `truckdrivers` | id | 6 | no | none | Class name lowercase |
| `Freight` | `frieghts` | id | 21 | no | none | Class name `Freight`, table `frieghts` (preserved misspelling) |

**Important conventions observed:**
- Table names: plural snake_case (`grs`, `frieghts`, `gatepasses`, `truckdrivers`, `challan_iteams`).
- Class names: case-mixed, often lowercase (`gr`, `challan`, `gatepass`, `truckdriver`) — non-PSR-1.
- Files: most match class but `app/Models/challan.php` contains class `challan` (correct case-insensitive on Windows — fragile on Linux).
- No `App\Observers`, no `App\Scopes`, no `App\Casts\` custom casts.

---

## 6. View Inventory (Blade)

### 6.1 Layouts
| File | Purpose |
|---|---|
| `admin/layout/master.blade.php` | Root layout for `/dash/*` pages |
| `admin/layout/top.blade.php` | `<head>` partial |
| `admin/layout/navigation.blade.php` | Left sidebar menu (admin theme) |
| `admin/layout/header.blade.php` | Top header bar |
| `admin/layout/bottom.blade.php` | Closing scripts (jQuery, Bootstrap, etc.) |
| `layouts/app.blade.php` | Default Laravel layout (unused) |

### 6.2 Auth
- `auth/login.blade.php`, `auth/register.blade.php`, `auth/passwords/{email,reset,confirm}.blade.php`, `auth/verify.blade.php`

### 6.3 Business modules
| Module | Views |
|---|---|
| **GR (Copies)** | `admin/category/copies_list.blade.php`, `copies.blade.php` (create), `copies_edit.blade.php`, `copies_print.blade.php` |
| **Gatepass** | `admin/category/Gatepass/gate_pass_list.blade.php`, `gate_pass.blade.php`, `gate_pass_edit.blade.php` (print reuses `copies_print.blade.php`) |
| **Freight Memo** | `admin/category/FrieghtMemo/Frieght_memo_list.blade.php`, `Frieght_memo.blade.php`, `Frieght_memo_edit.blade.php` |
| **Challan** | `admin/category/challan/challan_list.blade.php`, `challan.blade.php`, `challan_edit.blade.php`, `challan_all.blade.php` |
| **Truck/Driver** | `admin/category/TruckDriver/truck_driver_list.blade.php`, `truck_driver.blade.php`, `truck_driver_edit.blade.php`, `truck_driver_view.blade.php` |

### 6.4 Admin (Users / Roles / Permissions)
- `users/{index,create,edit}.blade.php`
- `roles/{index,create,edit}.blade.php`
- `permissions/{index,create,edit}.blade.php`
- `posts/{index,create,edit,show}.blade.php` (legacy)

### 6.5 Errors
- `errors/401.blade.php`, `errors/list.blade.php`

---

## 7. Middleware Inventory

| Middleware | File | Purpose | Issues |
|---|---|---|---|
| `auth` | `Authenticate.php` | Standard Laravel | OK |
| `isAdmin` | `AdminMiddleware.php` | Allows access if `User::all()->count() == 1` (first user) **OR** user has permission `Administer roles & permissions` | **Anti-pattern**: counts all users on every request. The "first user" bootstrap check is dangerous on multi-tenant. |
| `clearance` | `ClearanceMiddleware.php` | Permission-based gate for `posts.create`, `posts.edit`, `posts.destroy` only | **Dead code** — no business module uses it |
| `TrustProxies` | default | OK | — |
| `EncryptCookies` | default | OK | — |
| `VerifyCsrfToken` | default | OK | — |
| `TrimStrings` | default | OK | — |
| `TrustHosts` | exists but `Kernel::$middleware` doesn't list it (commented out) | Unused | OK |
| `PreventRequestsDuringMaintenance` | default | OK | — |
| `RedirectIfAuthenticated` | default | OK | — |

---

## 8. Service / Repository / Job / Event / Helper / Provider Inventory

| Layer | Count | Files |
|---|---|---|
| **Services** | 0 | none |
| **Repositories** | 0 | none |
| **Jobs** | 0 | none |
| **Events/Listeners** | 0 | none |
| **Mailables** | 0 | none |
| **Notifications** | 0 | none |
| **Form Requests** | 0 | none (validation done inline) |
| **Custom Helpers** | 0 | none |
| **Service Providers** | 6 (all default): `AppServiceProvider`, `AuthServiceProvider`, `BroadcastServiceProvider`, `EventServiceProvider`, `RouteServiceProvider` |
| **Console/Kernel** | 1 | Default, empty `commands` array |
| **Exceptions/Handler** | 1 | Default |

**Architectural verdict:** The application is a **flat MVC layout** with all logic in controllers and a thin Eloquent model layer. There is no service layer, no DTOs, no Action classes, no domain events, no background processing.

---

## 9. Provider / Configuration Inventory

| Config file | Notes |
|---|---|
| `app.php` | Standard Laravel 8 (timezone `UTC`, providers listed, no `package:discover` extra) |
| `auth.php` | Standard `web` + `api` guards; `App\Models\User` as provider |
| `database.php` | MySQL primary, sqlite memory for testing |
| `filesystems.php` | Default `local` + `public` disks |
| `app/Providers/AuthServiceProvider.php` | `$policies = []` (no policies defined) |
| `app/Providers/RouteServiceProvider.php` | HOME = `/dash`; rate-limit 60/min for api |

---

## 10. Database Migration Inventory (existing)

| File | Created | Table | Notes |
|---|---|---|---|
| `2014_10_12_000000_create_users_table.php` | 2014-10-12 | `users` | Original scaffold (no `office` column) |
| `2014_10_12_100000_create_password_resets_table.php` | 2014-10-12 | `password_resets` | OK |
| `2019_08_19_000000_create_failed_jobs_table.php` | 2019-08-19 | `failed_jobs` | OK |
| `2020_10_22_120538_create_permission_tables.php` | 2020-10-22 | Spatie 5-tables | OK |
| `2020_10_22_130826_create_posts_table.php` | 2020-10-22 | `posts` | OK |
| `2020_10_29_082717_create_gatepasses_table.php` | 2020-10-29 | `gatepasses` | `gr_no` UNIQUE — would block one-to-many |
| `2020_11_08_120739_create_frieghts_table.php` | 2020-11-08 | `frieghts` | No unique on `fm_no` |
| `2020_11_09_095555_create_truckdrivers_table.php` | 2020-11-09 | `truckdrivers` | truck_no + license UNIQUE — could be problematic for shared trucks |
| `2020_11_11_082619_create_users_table.php` | 2020-11-11 | `users` | **DUPLICATE NAME** — second `create_users_table` migration; only the second runs in practice because the first would fail. **The `office` column was added via this second migration.** |
| `2020_11_22_053810_cretae_grs_table.php` | 2020-11-22 | `grs` | `down()` is empty (data loss risk) |
| `2020_12_06_102452_create_challan_iteams_table.php` | 2020-12-06 | `challan_iteams` | `gr_no` UNIQUE — prevents one-to-many |
| `2020_12_09_090031_create_challans_table.php` | 2020-12-09 | `challans` | PK is `challan_no` (no `id`) |

---

## 11. Critical Issues (BLOCKING — must be addressed before any modernization)

These were found by static analysis of the source. **Do not deploy, do not migrate, until these are reconciled.**

### 11.1 Duplicate `create_users_table` migration
Two files have the same class name `CreateUsersTable`. On a fresh install Laravel will fail or skip. The second one (with `office` column) is the one that must run. **Action:** in any reconstructed migration set, the `users` table must include `office` from the start; the legacy second migration must be removed or renamed (e.g., `2020_11_11_082619_add_office_to_users_table.php`).

### 11.2 `PostController` is referenced in routes but does not exist
`routes/web.php` line 101: `Route::resource('/posts', 'App\Http\Controllers\PostController');` — no such class in `app/Http/Controllers/`. Loading `/dash/posts/*` will 500. **Action:** either create the controller, or remove the route, or treat as a feature to be rebuilt.

### 11.3 `ChallanController` uses a non-existent class
`use App\Models\challan_iteam;` — actual class is `App\Models\ChallanItem` (and even if case-folded, the file is `app/Models/ChallanItem.php` containing class `ChallanItem`). `challanIteamStore()` and `challanfetchdata()` therefore throw on first call. **Action:** correct the `use` statements to `App\Models\ChallanItem`.

### 11.4 `GatepassController::update()` references non-existent field
Line 201: `$gp->gst_amount = $request->get('other');` — `gst_amount` is not in `$fillable` of the `gatepass` model and not in the `gatepasses` table. The view (`gate_pass_edit.blade.php`) posts a `gst_amount` input, so the value is silently dropped. **Action:** decide whether `gst_amount` is a separate field (and add it) or remove the form input. The most natural fit is to keep `other` (as in the create form) and remove the `gst_amount` from the update form.

### 11.5 `AdminMiddleware` does a `count` on every request
`User::all()->count()` loads the entire table. On a system with thousands of users this is unacceptable. **Action:** replace with `User::doesntHave('roles')->count() == 0` semantics, or use a config flag, or use the spatie `roles()->doesntExist()` builder.

### 11.6 Two `select` blocks for `from_dest` in every form
The `copies.blade.php` create form has the user-office `<select>` rendered twice (one disabled, one hidden) to work around the disabled field not posting. This is brittle. **Action:** keep the disabled visible select for UX and use a hidden input for the actual value.

### 11.7 GR `down()` is empty
`2020_11_22_053810_cretae_grs_table.php` has `public function down() { // }`. A `migrate:rollback` will not drop the table. **Action:** always provide `Schema::dropIfExists('grs')`.

### 11.8 `gatepasses.gr_no` UNIQUE + `challan_iteams.gr_no` UNIQUE
Both tables have `unique('gr_no')`. A single GR can therefore only ever produce one Gatepass and one ChallanItem. This is almost certainly **not** intended. **Action:** drop the unique, replace with an index.

### 11.9 Naming inconsistencies
- `frieght` / `frieghts` (typo, preserved everywhere)
- `challan_iteam` / `challan_iteams` (typo, preserved everywhere)
- Model class `challan`, `gatepass`, `truckdriver` — lowercase (works on Windows, fragile on Linux)
- `freightmemo` URL segment vs `frieghtmemo` route vs `Freight` model — three different spellings

Modernization must lock in a single canonical spelling and add aliases (route `redirect()`, view renames) for backward compatibility.

### 11.10 `Composer-Setup.exe` is in the project root
`Composer-Setup.exe` (1.8 MB) is in the project root. Should not be committed; should be deleted.

---

## 12. Hard-coded Business Constants

| Constant | Where | Value | Note |
|---|---|---|---|
| Company name | All `*_list*.blade.php` and print files | "SAURASHTRA EXPRESS" | Branding preserved |
| Head Office address | All `*.blade.php` create forms | "2- Patel Nagar, Bhoja Bhagat Street, 50ft Ring Road, Rajkot" | Preserved |
| GST No. | All `*.blade.php` | `24AFSPJ7382P1ZI` | Preserved |
| Contact Nos. | All `*.blade.php` | `93750 88088, 97279 00008` | Preserved |
| Office list | All select dropdowns | `Kashmore Gate, Rajkot, Dayabasti, Swarup Nagar, Navagam, Shapar (1), Shapar (2)` | 7 offices, 2 cities (Delhi / Rajkot) |
| GR prefix per office | `GrController::create()` | `AA-` (Rajkot), `AA-` (Navagam, Kashmore), `AA-` (default) | Hard-coded per-office number series |
| Challan prefix | `ChallanController::create()` | `AA-` | No per-office differentiation |
| Gatepass counter | `GatepassController::create()` | numeric, starting 0, resets at 1000 | Fragile — needs a dedicated sequence table |
| T&C text | All create/edit/print forms | "We are not responsible of goods after 6 months of booking date..." | Preserved verbatim |

---

## 13. Authorization Model

- `spatie/laravel-permission` is wired up.
- `User::office` is the de-facto tenancy key (each office sees its own GRs — `GrController::index()` filters by `Auth::user()->office`).
- Three permission gates observed in code:
  - `Administer roles & permissions` (in `AdminMiddleware`, `ClearanceMiddleware`, `PermissionController`).
  - `Create Post` (in `ClearanceMiddleware`).
  - `Edit Post` (in `ClearanceMiddleware`).
  - `Delete Post` (in `ClearanceMiddleware`).
- **No policy classes** defined (`AuthServiceProvider::$policies = []`).
- **No middleware-based gate** for `dash.gr.*`, `dash.gatepass.*`, `dash.frieghtmemo.*`, `dash.challan.*` — any logged-in user can hit any of these.

---

## 14. Test Inventory

- `phpunit.xml` configured for an SQLite memory DB.
- `tests/` directory exists but **no actual test files** were found (no Feature/Unit test classes).

---

## 15. Summary of what this app actually does

1. **Authentication** — default Laravel login/registration/password reset. Users have an `office` field.
2. **Admin** — Spatie-based roles/permissions management.
3. **GR (Goods Receipt)** — the heart of the system. Each office generates a per-office `gr_no` (like `AA-00001`). A GR captures consignor/consignee with GSTIN, packages (nugs/meth), description, weight, E-Way bill, freight + surcharges + GST + BC + total, and a paid/to-pay checkbox. The GR is the source-of-truth document.
4. **Gatepass** — created from a GR (pre-fills consignor, freight, weight, total). Records the delivery release with freight/labour/other/DC amounts.
5. **Freight Memo** — a settlement document for the truck. Has 4 charge entries + truck freight + commission + other + extra + balance to S.N. The create form posts correctly but `store()` is a stub.
6. **Challan** — links a truck (with driver, license) to one or more GRs. Items are added via AJAX row-by-row. `challan_no` is the PK (no `id` column).
7. **Truck/Driver master** — CRUD with regex validation on truck number (`GJ 05 JK 7896` style) and driving license number (Indian format).
8. **Print** — `copies_print.blade.php` is the only dedicated print template. The print has a hard-coded Windows path `<img src="G:\revan\img1.jpg" />` which will not work in production.

---

## 16. Open Questions for User

Before any code is written, the following must be confirmed:

1. **Office multi-tenancy**: is `users.office` the only tenancy key? Will you add a `branches` table? (See Phase 5 plan.)
2. **GR uniqueness per office**: confirm the AA-XXXXX scheme is still the desired numbering format.
3. **Gatepass & Challan GR cardinality**: confirm that one GR can map to multiple Gatepasses and multiple ChallanItems (i.e., remove the unique on `gr_no`).
4. **Print template**: is the hard-coded `G:\revan\img1.jpg` logo path fine for production, or do you have a proper asset? (Recommend `public/images/logo.png`.)
5. **Customer / Vendor / Vehicle / Driver / Route masters**: are these all in scope for modernization, or do you want to keep the flat `truckdriver` master for now?
6. **POD (Proof of Delivery)**: not present in current code. Are PODs out of scope, or should we add a `pod_uploads` table?
7. **Settlement process**: `freight_memos` is currently a stub. Do you want a real settlement workflow (vendor payable, paid, balance)?

These will be tracked in `implementation-roadmap.md`.
