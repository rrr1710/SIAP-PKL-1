# Centralized Context State (`CONTEXT_STATE.md`)

This file acts as the single source of truth for cross-agent memory, tracking project status, architecture decisions, phase progress, and active blockers.

---

## 🚦 Phase Execution Roadmap & Status

| Phase | Scope | Status | Notes / File Targets |
| :--- | :--- | :--- | :--- |
| **Phase 1: Database & Migrations** | 9 clean migrations (`instansi`, `users`, `sub_instansi`, `permohonan_pkl`, `anggota_permohonan`, `peserta_magang`, Spatie `activity_log`, etc.) | ✅ **COMPLETED** | Archive in [`database/migrations_archive/`](file:///e:/SIAP-PKL/SIAP-PKL/database/migrations_archive). Verified via `migrate:fresh`. |
| **Phase 2: Eloquent Models** | 6 clean models with relationships & casts (`User`, `Instansi`, `SubInstansi`, `PermohonanPkl`, `AnggotaPermohonan`, `PesertaMagang`). | ✅ **COMPLETED** | Located in [`app/Models/`](file:///e:/SIAP-PKL/SIAP-PKL/app/Models). 12 legacy models deleted. |
| **Phase 3: Seeders & Test Data** | Populate `instansi`, `sub_instansi`, roles, permissions, and test applications. | ✅ **COMPLETED** | [`DatabaseSeeder.php`](file:///e:/SIAP-PKL/SIAP-PKL/database/seeders/DatabaseSeeder.php), [`RoleSeeder.php`](file:///e:/SIAP-PKL/SIAP-PKL/database/seeders/RoleSeeder.php), [`InstansiSeeder.php`](file:///e:/SIAP-PKL/SIAP-PKL/database/seeders/InstansiSeeder.php), [`PermohonanPklSeeder.php`](file:///e:/SIAP-PKL/SIAP-PKL/database/seeders/PermohonanPklSeeder.php). Verified via `migrate:fresh --seed`. |
| **Phase 4: Backend Controllers & Routes** | Update controllers & [`routes/web.php`](file:///e:/SIAP-PKL/SIAP-PKL/routes/web.php). | ✅ **COMPLETED** | Realigned [`PengajuanPklController.php`](file:///e:/SIAP-PKL/SIAP-PKL/app/Http/Controllers/PengajuanPklController.php), [`HomeController.php`](file:///e:/SIAP-PKL/SIAP-PKL/app/Http/Controllers/HomeController.php), [`AdminApplicationController.php`](file:///e:/SIAP-PKL/SIAP-PKL/app/Http/Controllers/Admin/AdminApplicationController.php), [`AdminBidangController.php`](file:///e:/SIAP-PKL/SIAP-PKL/app/Http/Controllers/Admin/AdminBidangController.php), [`AuditLogController.php`](file:///e:/SIAP-PKL/SIAP-PKL/app/Http/Controllers/SuperAdmin/AuditLogController.php). All 41 routes verified. |
| **Phase 5: Frontend Alignment & Data Contracts** | Connect Vue 3 Inertia props, payload normalization & schema adapters. | ✅ **COMPLETED** | Two-way compatibility adapters implemented in controllers for [`Pengajuan/Index.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Pages/Pengajuan/Index.vue), [`Katalog/Index.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Pages/Katalog/Index.vue), [`Riwayat/Index.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Pages/Riwayat/Index.vue), [`Status/Index.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Pages/Status/Index.vue), and [`Admin/Pengajuan/Index.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Pages/Admin/Pengajuan/Index.vue). |

---

## 🛠️ Architecture & Compatibility Bridges

1. **Dual Schema Normalization in Controllers:**
   * Controllers ([`PengajuanPklController.php`](file:///e:/SIAP-PKL/SIAP-PKL/app/Http/Controllers/PengajuanPklController.php), [`AdminApplicationController.php`](file:///e:/SIAP-PKL/SIAP-PKL/app/Http/Controllers/Admin/AdminApplicationController.php)) automatically normalize request payloads between legacy English keys (`division_id`, `start_date`, `document`, `members`, `accepted`) and modern Indonesian keys (`id_sub_instansi`, `tanggal_mulai`, `berkas_permohonan`, `anggota`, `diterima`).
   * Controller responses provide both property sets (`subInstansiList` + `divisions`, `kuota_total` + `batas_kuota`, etc.), ensuring zero template crashes across Vue pages.

2. **Audit Logging Integration:**
   * [`AuditLogController.php`](file:///e:/SIAP-PKL/SIAP-PKL/app/Http/Controllers/SuperAdmin/AuditLogController.php) queries `Spatie\Activitylog\Models\Activity` with eager-loaded user/causer relationships, resolving the missing `App\Models\AuditLog` reference.

3. **AES-256 Encrypted Field Verification:**
   * `nim` and `no_hp` are encrypted at rest in MySQL (`TEXT` columns) and automatically decrypted on Eloquent hydration. Tested and verified in `_verify_state.php`.

4. **Anti-Overbooking Concurrency Engine:**
   * In [`PengajuanPklController.php`](file:///e:/SIAP-PKL/SIAP-PKL/app/Http/Controllers/PengajuanPklController.php), `store()` wraps submission logic in `DB::transaction()` with pessimistic locking (`lockForUpdate()`) on both active user submissions (`PermohonanPkl`) and target division (`SubInstansi`), serialized against the composite index `idx_kuota_peserta` to completely eliminate race conditions and overbooking.

5. **Private Document Storage & Temporary Signed URLs:**
   * Proposal and submission documents are isolated on the private `local` disk (`storage/app/proposals/`).
   * Downloads are authenticated and governed via temporary signed URLs (`URL::temporarySignedRoute('proposal.download', now()->addMinutes(15), ...)`).
   * Controllers ([`PengajuanPklController.php`](file:///e:/SIAP-PKL/SIAP-PKL/app/Http/Controllers/PengajuanPklController.php), [`AdminApplicationController.php`](file:///e:/SIAP-PKL/SIAP-PKL/app/Http/Controllers/Admin/AdminApplicationController.php)) support backward-compatibility streaming from both private `local` and legacy `public` disks.

6. **Auth-Aware Public Shell & Direct Application Linking:**
   * [`PublicLayout.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Layouts/PublicLayout.vue) detects authenticated sessions to render a portal dashboard button and logout action instead of the guest login prompt.
   * `Katalog/Index.vue` and `BidangCard.vue` route authenticated applicants directly to `/pengajuan?division={id}`, and render standard anchor tags (`<a href="...">`) for unauthenticated OAuth redirections, eliminating Inertia/Axios CORS Network Errors.
   * `Admin/Dashboard.vue` maps both `nama`/`nama_sub_instansi` and `kuota`/`batas_kuota` for live division gauges.
   * `AppLayout.vue` includes the `check-circle` icon and `StatusPendaftaranController` passes `activeNav: 'status'` for proper sidebar alignment.

---

## ⚠️ Known Blockers & Technical Gotchas

1. **Windows PHP CLI Command Execution (`browscap.ini`):**
   * *Issue:* Running standard `php artisan` commands on this Windows machine fails with a fatal error due to missing `browscap.ini` path.
   * *Solution:* Pass `-d browscap=""` flag when executing artisan commands via terminal, e.g.:
     `php -d browscap="" artisan migrate:fresh --seed`

2. **Windows File Lock on Dev Tools (`laravel/pint`):**
   * *Issue:* Running `composer install` without flags may fail on Windows due to binary file lock on `pint.exe`.
   * *Workaround:* Use `composer install --no-dev` or execute terminal with Administrator privileges.

---

## 📋 Protocols for Autonomous Agents

1. **Summarize, Don't Dump:** Keep raw code out of this file. Use clickable file links (e.g., [`PermohonanPkl.php`](file:///e:/SIAP-PKL/SIAP-PKL/app/Models/PermohonanPkl.php)) and short summaries.
2. **Update Before Expiry:** Any agent completing a phase or hitting 90% usage MUST update `CONTEXT_STATE.md` before ending its turn.
3. **Verify Before Progressing:** Always verify schema compatibility with `php -d browscap="" artisan migrate:fresh --seed` and `php -d browscap="" artisan route:list`.
