# Ghost Field Audit — Saurashtra Express

> Static scan of every reference to model columns in views, controllers, and models.
> A "ghost field" is one that is referenced but does not exist, is a typo, or is a dead field.

---

## 1. Pure ghost fields (column doesn't exist anywhere)

### 1.1 `$copy->packeges` in `copies_print.blade.php:131`
- **Type:** Pure typo. Should be `nugs` (or rename to `packages`).
- **Effect:** The "Packages" column on the GR print is always empty.
- **Confidence:** 100%
- **Recommended action:** Rename `$copy->packeges` → `$copy->nugs` in the print view. Or, if a real `packages` field is desired, add a migration adding `grs.packages` as a string (free-text override of numeric `nugs`).

### 1.2 `gst_amount` form input in `gate_pass_edit.blade.php`
- **Type:** Field referenced in form and in `GatepassController::update()` line 201 (`$gp->gst_amount = $request->get('other');`) but the column doesn't exist in the migration, doesn't exist in `$fillable`, and the `other` value is being incorrectly assigned to a non-existent property.
- **Effect:** The value is silently dropped. If the form is submitted, the user thinks they updated `gst_amount` but the database `gatepasses.other` (which already gets set elsewhere) is not updated either, because the controller is **assigning to a non-existent property** on a non-persisted state — the form's input value for `gst_amount` is effectively lost.
- **Confidence:** 100%
- **Recommended action:** Remove the `gst_amount` input from `gate_pass_edit.blade.php`; remove `$gp->gst_amount = ...` from the controller. Or add a `gst_amount` column to `gatepasses` (decimal(12,2), nullable) and put it in `$fillable`.

### 1.3 `<img src="G:\revan\img1.jpg">` in `copies_print.blade.php:13`
- **Type:** Hard-coded Windows absolute path. Will not work in production (Linux server, browser, etc.).
- **Effect:** Logo image is broken on every print.
- **Confidence:** 100%
- **Recommended action:** Move the image to `public/images/logo.png` and use `{{ asset('images/logo.png') }}`.

### 1.4 `gst_amount` (typo of "GST amount") in views
- Appears in `gate_pass_edit.blade.php` as a form field. Already covered in §1.2.

---

## 2. Typo'd columns (column exists but with misspelling)

### 2.1 `grs.nor_adress` → should be `consignor_address`
- Migration: `$table->string('nor_adress');` (no length)
- Model `$fillable`: `nor_adress`
- View refs: `$copy->nor_adress`, `nor_adress` form input
- Controller refs: `$request->post('nor_adress')` (GrController)
- **Confidence:** 100% it's a typo (looks like "Norther(n)" or "nor(ther) address")
- **Recommended action:** Rename to `consignor_address` (varchar(255)). Add migration with `renameColumn`. Update model $fillable, all views, all controllers. **Defer** to Phase 5 modernization.

### 2.2 `grs.nee_adress` → should be `consignee_address`
- Same situation as §2.1 but for the consignee (nee = "Norther East"? unclear origin).
- **Recommended action:** Rename to `consignee_address`.

### 2.3 `grs.nor_gst_no` / `grs.nee_gst_no` → should be `consignor_gst_no` / `consignee_gst_no`
- Same situation. "nor" = "north (consignor)", "nee" = "north-east (consignee)".
- **Recommended action:** Rename both to `consignor_gst_no` and `consignee_gst_no`.

### 2.4 `gatepasses.m_s` → should be `consignor`
- Migration: `$table->string('m_s');`
- View: `m_s` form input, displayed as "MS"
- Controller: `$request->post('m_s')` (GatepassController)
- **Confidence:** 100% it's a typo / abbreviation (M/S = "Messrs"?)
- **Recommended action:** Rename to `consignor` to match GR convention. (Defer.)

### 2.5 `frieghts` (table) / `Freight` (model) / `frieght` (URL) → should be `freight_memos` / `FreightMemo` / `freight-memo`
- **Confidence:** 100% it's a typo (frieght vs freight).
- **Recommended action:** Migrate spelling to `freight_memos`. (Already a hardcoded redirect needed for backward compat — see `project-analysis.md §3.4`.)

### 2.6 `challan_iteams` (table) / `ChallanItem` (model) → should be `challan_items` / `ChallanLine` or `ChallanItem`
- "iteams" is a misspelling of "items".
- **Recommended action:** Rename table and class.

### 2.7 `frieghts.entry_1`, `entry_1_amount`, etc. → should be `charge_1`, `charge_1_amount`
- Not strictly a typo, but the `entry_N` naming is opaque. (Defer — internal change.)

### 2.8 `frieghts.balance_to_sn` → should be `balance_to_settle` or `balance_due`
- "S.N." is internal shorthand. The form has a "Balance to S.N." label.
- **Recommended action:** Document in the modernization phase; rename in migration.

### 2.9 `gatepasses.dc_amount` → should be `delivery_charge_amount` or `dc_amount`
- "DC" = "Delivery Charge"? Ambiguous.
- **Recommended action:** Document; rename in migration.

---

## 3. Dead / unused fields

### 3.1 `users.office` — used heavily, NOT dead
- This is the de-facto tenancy key. See `project-analysis.md §13`.

### 3.2 `posts.*` — dead in business logic
- Sample/legacy. Used only by `ClearanceMiddleware`. Safe to remove.

### 3.3 `User::officeall()` returns a Collection but is treated as a scalar
- `GrController::create()` does `strcmp($officecenter, "Rajkot")`. `strcmp()` on a Collection gives a warning. **This is a runtime bug** in the production app.
- **Confidence:** 100% (verified by reading `User.php:51-56` and `GrController.php:188`).
- **Recommended action:** Change to `pluck('office')->first()` or `value('office')`.

### 3.4 `User::officeall()` is never actually used
- A `Grep` for `officeall(` returns only the definition. **Dead method.** The fact that it's a Collection is moot — nothing calls it.
- **Recommended action:** Remove the method.

### 3.5 `HomeController::index()` is dead
- Returns `view('home')` (default Laravel 8 welcome) which is overridden by `routes/web.php` returning `view('auth.login')`.
- **Recommended action:** Remove the route or the controller.

### 3.6 `DashboardController` (root) is dead
- No route maps to it.
- **Recommended action:** Remove.

### 3.7 `dash\DashboardController` returns nothing useful
- The view `admin/dashboard.blade.php` is a stub. The "dashboard" is essentially empty.
- **Recommended action:** Build a real dashboard (count of GRs, Gatepasses, Challans, etc.) in the modernization pass.

### 3.8 `dash\ChallanItemController` (class `ChallanItamsController`) is dead
- Has no methods implemented; no route maps to it (the only `challan*` routes go to `ChallanController`).
- **Recommended action:** Delete the file.

---

## 4. Orphan tables

### 4.1 `posts` — orphan
- The `PostController` referenced by `Route::resource('/posts', 'App\Http\Controllers\PostController');` **does not exist** (verified by directory listing of `app/Http/Controllers/`). So the table exists, the model exists, the routes exist, but the controller is missing. Hitting any `/dash/posts/*` URL will 500.
- **Recommended action:** Remove the routes; remove the table in the modernization migration.

### 4.2 `password_resets` — used by default Laravel 8 auth
- This is fine; the default `Auth\ForgotPasswordController` uses it.

---

## 5. Broken class references

### 5.1 `use App\Models\challan_iteam;` in `dash/ChallanController.php:8`
- **Type:** The class is `App\Models\ChallanItem`. The controller imports `challan_iteam` (singular, lowercase, "iteam" misspelling).
- **Effect:** PHP autoloader fails. `challanIteamStore()` and `challanfetchdata()` and `challandelete()` and `index()` (via `truckdriver::pluck`) will all fatal-error if the autoloader is strict.
- **Confidence:** 100%
- **Recommended action:** Change to `use App\Models\ChallanItem;`.

### 5.2 `dash\ChallanItemController` defines class `ChallanItamsController` (sic)
- **Type:** Class name is `ChallanItamsController` but file is `ChallanItemController.php` (capital I in Item). PSR-4 mismatch.
- **Effect:** File is autoloaded only if the file name and class name match. `ChallanItamsController` ≠ `ChallanItemController` → fatal error on autoload.
- **Recommended action:** Delete the file (it is dead code anyway, see §3.8).

---

## 6. Dead code

### 6.1 `ClearanceMiddleware` gates `posts.*` permissions that have no controller
- The middleware checks `$user->can('Create Post')` etc. for routes that 500. So the middleware is unreachable. (See §4.1.)
- **Recommended action:** Delete the middleware (or rebuild it as `gr.*`, `gatepass.*`, `challan.*` gates).

### 6.2 `app/Models/Post.php` has no controller
- (Same as §4.1.)

### 6.3 `AdminMiddleware` "first user is admin" check
- `User::all()->count() == 1` is meant to bootstrap the very first user as admin before any roles are assigned. **It works** but is fragile: if the first user is deleted (count goes to 0) the next registered user would be admin by accident. And `User::all()->count()` is an unbounded query.
- **Confidence:** 100%
- **Recommended action:** Replace with a config-flag fallback OR a one-time boot check, and cache the result.

---

## 7. Summary of issues and severity

| # | Issue | Severity | Confidence |
|---|---|---|---|
| 1.1 | `packeges` ghost in print view | Medium (cosmetic, prints blank) | 100% |
| 1.2 | `gst_amount` ghost in gatepass update | **High** (data loss on update) | 100% |
| 1.3 | Hard-coded `G:\revan\img1.jpg` path | High (broken logo) | 100% |
| 2.1–2.4 | Address/GST/M-S typos | Medium (naming) | 100% |
| 2.5 | `frieghts` / `frieght` spelling | Medium (naming) | 100% |
| 2.6 | `challan_iteams` / `challan_iteam` spelling | Medium (naming) | 100% |
| 3.3 | `User::officeall()` returns Collection | **High** (runtime bug) | 100% |
| 3.4 | `User::officeall()` is dead | Low (cleanup) | 100% |
| 3.8 | `ChallanItemController` is dead | Low (cleanup) | 100% |
| 4.1 | `posts` table orphaned, no controller | **High** (500 on /dash/posts) | 100% |
| 5.1 | `challan_iteam` typo in use statement | **Critical** (controller will fatal-error) | 100% |
| 5.2 | PSR-4 mismatch in ChallanItemController file | Low (file already dead) | 100% |
| 6.3 | AdminMiddleware first-user bootstrap | Medium (security) | 100% |
