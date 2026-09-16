# Implementation Plan: SIAP-PKL Schema & Architecture Realignment

This implementation plan outlines the steps required to align the `SIAP-PKL` codebase with the Indonesian 6-table schema (`instansi`, `sub_instansi`, `permohonan_pkl`, `anggota_permohonan`, `peserta_magang`, `log_aktivitas_magang`) while resolving breaking dependencies across models, controllers, seeders, and Vue components.

---

## User Review Required

> [!WARNING]
> **Critical Bottleneck & Sequential Execution Risk:**
> Do NOT execute Phase 2 (deleting legacy models and running `php artisan migrate:fresh --seed`) in isolation before updating controllers and seeders.
> All existing controllers (`AdminApplicationController`), `DatabaseSeeder.php`, middleware, and Inertia components currently rely on legacy model classes (`Application`, `Division`) and columns (`agency_id`). Running `migrate:fresh --seed` without realigning controllers and seeders will crash the application.

> [!IMPORTANT]
> **AES-256 Encryption Column Length Fix:**
> `nim` and `no_hp` in `anggota_permohonan` and `peserta_magang` migrations are defined as `VARCHAR(50)` and `VARCHAR(20)`. Applying Laravel's `encrypted` model cast will generate ~180-250 character ciphertexts, triggering SQL truncation errors. They MUST be changed to `text()` before running migrations.

---

## Proposed Changes

### Phase 1: Database Migration Corrections

#### [MODIFY] [`0001_01_01_000006_create_anggota_permohonan_table.php`](file:///e:/SIAP-PKL/SIAP-PKL/database/migrations/0001_01_01_000006_create_anggota_permohonan_table.php)
- Change `$table->string('nim', 50)` to `$table->text('nim')`.
- Change `$table->string('no_hp', 20)` to `$table->text('no_hp')`.

#### [MODIFY] [`0001_01_01_000007_create_peserta_magang_table.php`](file:///e:/SIAP-PKL/SIAP-PKL/database/migrations/0001_01_01_000007_create_peserta_magang_table.php)
- Change `$table->string('nim', 50)` to `$table->text('nim')`.
- Change `$table->string('no_hp', 20)` to `$table->text('no_hp')`.

---

### Phase 2: Model & Encryption Realignment

#### [DELETE] Legacy Models
- Remove deprecated models: `Application.php`, `ApplicationMember.php`, `Division.php`, `Position.php`, `Kelompok.php`, `Lowongan.php`, `PengajuanPkl.php`, `BerkasPkl.php`.

#### [MODIFY] [`AnggotaPermohonan.php`](file:///e:/SIAP-PKL/SIAP-PKL/app/Models/AnggotaPermohonan.php)
- Configure `$casts` with `'nim' => 'encrypted'` and `'no_hp' => 'encrypted'`.
- Define `belongsTo(PermohonanPkl::class)` relationship.

#### [MODIFY] [`PesertaMagang.php`](file:///e:/SIAP-PKL/SIAP-PKL/app/Models/PesertaMagang.php)
- Configure `$casts` with `'nim' => 'encrypted'` and `'no_hp' => 'encrypted'`.
- Add Spatie `LogsActivity` trait for responsibility tracking.

---

### Phase 3: Seeders & Controllers Realignment

#### [MODIFY] [`DatabaseSeeder.php`](file:///e:/SIAP-PKL/SIAP-PKL/database/seeders/DatabaseSeeder.php)
- Replace legacy seeding logic referencing `agency_id` and `Division` with `Instansi` and `SubInstansi`.
- Seed default super-admin, agency admin, and sample `sub_instansi` records.

#### [MODIFY] Application Controllers
- Realign `AdminApplicationController.php`, `ApplicantController.php`, and API endpoints to query `PermohonanPkl` and `SubInstansi`.

---

### Phase 4: Inertia.js Vue 3 Frontend Alignment

#### [MODIFY] Vue Pages & Components
- Align form inputs in `resources/js/Pages/` to post data matching the Indonesian schema.
- Update table column definitions to display decrypted values via Inertia props.

---

## Verification Plan

### Automated Tests
- Run `php artisan migrate:fresh --seed` to confirm database schema and seeding succeed without SQL errors.
- Run `php artisan test` (if unit/feature test suites exist).

### Manual Verification
- Test applicant registration and application submission flow via Inertia.js.
- Verify encrypted fields (`nim`, `no_hp`) are stored as ciphertext in MySQL and decrypted correctly in views.
- Verify Spatie activity log records entry upon status change.
