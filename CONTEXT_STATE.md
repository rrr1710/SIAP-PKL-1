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
| **Phase 6: Student UI Refactor & Pipeline Hardening** | Batch 1 minor scrubs: dynamic layout swap (`AppLayout`/`PublicLayout`), quota obfuscation, UU PDP removal, pre-selection, `menunggu` status mapping. Batch 2: Instansi > SubInstansi grouped hierarchy in Katalog. Batch 3: Disabled apply buttons when full (`BidangCard`, `Katalog/Show`), percentage removal in Show, and hardened submission pipeline (`PengajuanPklController` + `Pengajuan/Index`). | ✅ **COMPLETED** (Batch 1)<br>✅ **COMPLETED** (Batch 2 Instansi Hierarchy)<br>✅ **COMPLETED** (Batch 3 Quota Disabling & Submission Pipeline) | Cleaned [`Home.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Pages/Home.vue), [`Katalog/Index.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Pages/Katalog/Index.vue), [`Katalog/Show.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Pages/Katalog/Show.vue), [`Pengajuan/Index.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Pages/Pengajuan/Index.vue), [`BidangCard.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Components/BidangCard.vue), and [`PengajuanPklController.php`](file:///e:/SIAP-PKL/SIAP-PKL/app/Http/Controllers/PengajuanPklController.php). All tests verified end-to-end. |

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

## 📂 Frontend Architecture, View Folder Structure & Data Contracts

### 1. View Directory Map (`resources/js/`)
* **Layouts (`resources/js/Layouts/`):**
  * [`AppLayout.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Layouts/AppLayout.vue): Authenticated student layout with Ulap Doyo green sidebar, dynamic active state highlighting (`activeNav`), and user profile menu.
  * [`PublicLayout.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Layouts/PublicLayout.vue): Unauthenticated public layout with header login CTA and footer.
  * [`AdminLayout.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Layouts/AdminLayout.vue): Agency Admin layout.
  * [`SuperAdminLayout.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Layouts/SuperAdminLayout.vue): SuperAdmin portal layout.
* **Student Pages (`resources/js/Pages/`):**
  * [`Home.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Pages/Home.vue) (`/home`): Clean Task Center banner (Empty CTA vs Stepper Review vs Acceptance info).
  * [`Katalog/Index.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Pages/Katalog/Index.vue) (`/katalog`): Dynamic layout wrapper (`AppLayout` when authenticated, `PublicLayout` for guest), auth-aware hero banner, keyword search, instansi filter, and `"Hanya Slot Tersedia"` toggle.
  * [`Katalog/Show.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Pages/Katalog/Show.vue) (`/katalog/{id}`): Dynamic layout wrapper, division detail, quota obfuscation (`Kuota Penuh` if overbooked), without hardcoded kualifikasi.
  * [`Pengajuan/Index.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Pages/Pengajuan/Index.vue) (`/pengajuan`): Pre-selects division via `?bidang_id=X` or `?division=X`, single application guard for users with `menunggu`/`diterima` applications, PDP consent checkbox removed.
  * [`Status/Index.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Pages/Status/Index.vue) (`/status`): Real-time application stepper (`menunggu` -> "Dalam proses"), proposal download link, and empty state CTA to `/katalog`.
  * [`Riwayat/Index.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Pages/Riwayat/Index.vue) (`/riwayat`): Past application table with status badges and detail links.
  * [`Profile/Edit.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Pages/Profile/Edit.vue) (`/profile`): Student profile management.
* **Admin & SuperAdmin Pages:**
  * Agency Admin: [`Admin/Dashboard.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Pages/Admin/Dashboard.vue), `Admin/Bidang/`, `Admin/Pengajuan/`, `Admin/Peserta/`, `Admin/Profile/`.
  * SuperAdmin: [`SuperAdmin/Dashboard.vue`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/Pages/SuperAdmin/Dashboard.vue), `SuperAdmin/AuditLog/`, `SuperAdmin/Instansi/`, `SuperAdmin/Undangan/`.
* **Archived Pages:**
  * 17 legacy/unused files archived in [`resources/js/_archive/`](file:///e:/SIAP-PKL/SIAP-PKL/resources/js/_archive).

### 2. Page Props & Data Contracts Reference
| Page | Inertia Props Expected | Form Payload / Controller Mapping | Notes |
| :--- | :--- | :--- | :--- |
| **`Home.vue`** | `pendaftaranAktif` (nullable object) | Read-only | Renders dynamic Task Center banner |
| **`Katalog/Index.vue`** | `divisions` (flat fallback), `groupedInstansi` (preferred, server-grouped: `nama_instansi`, `total_bidang`, `total_slot_tersisa`, `total_slot_terisi`, `divisions[]`), `instansiList`, `instansi`, `stats`, `filters`, `activeNav: 'katalog'` | Query params: `search`, `instansi`, `status`, `only_available` | Dynamic layout (`AppLayout` or `PublicLayout`). Grid renders parent Instansi headers with aggregate slot badges, then nested SubInstansi `BidangCard` grids. `✦ Kuota Penuh` badge shown at instansi level if all slots are filled. |
| **`Katalog/Show.vue`** | `division`, `subInstansi`, `activeNav: 'katalog'` | Read-only | Quota percentage removed. CTA button disabled with "Kuota Penuh" if `sisa_total <= 0` or overbooked. |
| **`Pengajuan/Index.vue`** | `divisions`, `hasActiveApplication`, `activeApplication`, `activeNav: 'pengajuan'` | **POST `/pengajuan`:**<br>- `division_id` / `id_sub_instansi`<br>- `start_date` / `tanggal_mulai`<br>- `end_date` / `tanggal_selesai`<br>- `tipe` ('individu' / 'kelompok')<br>- `ketua` (`name`/`nama_mahasiswa`, `nim`, `school`/`sekolah`, `major`, `phone`/`no_hp`)<br>- `members` / `anggota` (array of `{name, nim, school, major, phone}`, omitted when individu)<br>- `document` / `berkas_permohonan` (PDF file, max 5MB) | Dual-key normalization in controller and form transform; accepts both `berkas_permohonan` and `document`; handles individu without triggering empty-array validation errors. Guard blocks form if `hasActiveApplication` is true. |
| **`Status/Index.vue`** | `pendaftaran` (object with `division`, `position`, `status`, `catatan_revisi`, `download_url`, etc.), `activeNav: 'status'` | **POST `/pengajuan/{id}/reupload`** (`document`) | Status values mapped: `menunggu`, `diajukan`, `revisi`, `diterima`, `ditolak`, `selesai` |
| **`Riwayat/Index.vue`** | `riwayat` (array of past applications), `activeNav: 'riwayat'` | Read-only | Empty table state handled gracefully |

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

