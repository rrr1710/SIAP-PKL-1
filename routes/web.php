<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Admin\AdminBidangController;
use App\Http\Controllers\Admin\AdminApplicationController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PengajuanPklController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicCatalogController;
use App\Http\Controllers\RiwayatController;
use App\Http\Controllers\StatusPendaftaranController;
use App\Models\SubInstansi;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdmin\AuditLogController;

// Redirect root to public catalog
Route::get('/', fn () => redirect()->route('katalog.index'));

// Public Catalog (accessible without login)
Route::get('/katalog', [PublicCatalogController::class, 'index'])->name('katalog.index');
Route::get('/katalog/{subInstansi}', [PublicCatalogController::class, 'show'])
    ->name('katalog.show');

// Route aliases for backward compatibility with frontend links
Route::get('/bidang', fn () => redirect()->route('katalog.index'))->name('bidang.index');
Route::get('/bidang/{subInstansi}', fn ($subInstansi) => redirect()->route('katalog.show', $subInstansi))->name('bidang.show');
Route::get('/kelompok', fn () => redirect()->route('pengajuan.index'))->name('kelompok.index');

// Google OAuth Routes
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

// Auth routes (requires authentication)
Route::middleware('auth')->group(function () {

    // Student Portal Routes
    Route::middleware('ensure.onboarded')->group(function () {
        Route::get('/dashboard', fn () => redirect()->route('home'))->name('dashboard');
        Route::get('/home', [HomeController::class, 'index'])->name('home');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

        Route::get('/pengajuan', [PengajuanPklController::class, 'index'])->name('pengajuan.index');
        Route::get('/pengajuan/check-availability', [PengajuanPklController::class, 'checkAvailability'])->name('pengajuan.check-availability');
        Route::post('/pengajuan', [PengajuanPklController::class, 'store'])->name('pengajuan.store');
        Route::post('/pengajuan/{permohonan}/reupload', [PengajuanPklController::class, 'reupload'])->name('pengajuan.reupload');
        Route::get('/proposal/{permohonan}/download', [PengajuanPklController::class, 'downloadSignedDocument'])
            ->name('proposal.download')
            ->middleware('signed');

        Route::get('/status', [StatusPendaftaranController::class, 'index'])->name('status.index');
        Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat.index');
    });

    // Admin Portal Routes
    Route::prefix('admin')->name('admin.')->middleware('ensure.agency_admin')->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        Route::get('/bidang', [AdminBidangController::class, 'index'])->name('bidang.index');
        Route::get('/bidang/create', [AdminBidangController::class, 'create'])->name('bidang.create');
        Route::post('/bidang', [AdminBidangController::class, 'store'])->name('bidang.store');
        Route::get('/bidang/{bidang}', [AdminBidangController::class, 'show'])->name('bidang.show');
        Route::get('/bidang/{bidang}/edit', [AdminBidangController::class, 'edit'])->name('bidang.edit');
        Route::put('/bidang/{bidang}', [AdminBidangController::class, 'update'])->name('bidang.update');
        Route::delete('/bidang/{bidang}', [AdminBidangController::class, 'destroy'])->name('bidang.destroy');

        Route::get('/pengajuan', [AdminApplicationController::class, 'index'])->name('pengajuan.index');
        Route::get('/pengajuan/{pengajuan}/document', [AdminApplicationController::class, 'document'])->name('pengajuan.document');
        Route::get('/pengajuan/{pengajuan}', [AdminApplicationController::class, 'show'])->name('pengajuan.show');
        Route::patch('/pengajuan/{pengajuan}/status', [AdminApplicationController::class, 'updateStatus'])->name('pengajuan.status');

        Route::get('/peserta', [AdminApplicationController::class, 'participants'])->name('peserta.index');
        Route::patch('/peserta/{peserta}/complete', [AdminApplicationController::class, 'completeParticipant'])->name('peserta.complete');
        Route::get('/peserta/walk-in/create', [AdminApplicationController::class, 'walkInCreate'])->name('peserta.walk-in.create');
        Route::post('/peserta/walk-in', [AdminApplicationController::class, 'walkInStore'])->name('peserta.walk-in.store');

        Route::get('/profile', fn () => \Inertia\Inertia::render('Admin/Profile/Edit', ['activeNav' => 'admin.profile']))->name('profile.edit');
    });

    // Super Admin Portal Routes
    Route::prefix('super-admin')->name('superadmin.')->middleware('ensure.super_admin')->group(function () {
        Route::get('/dashboard', fn () => \Inertia\Inertia::render('SuperAdmin/Dashboard', ['activeNav' => 'superadmin.dashboard']))->name('dashboard');

        Route::get('/instansi', fn () => \Inertia\Inertia::render('SuperAdmin/Instansi/Index', ['activeNav' => 'superadmin.instansi']))->name('instansi.index');
        Route::get('/instansi/{instansi}', fn () => \Inertia\Inertia::render('SuperAdmin/Instansi/Show', ['activeNav' => 'superadmin.instansi']))->name('instansi.show');

        Route::get('/undangan', fn () => \Inertia\Inertia::render('SuperAdmin/Undangan/Index', ['activeNav' => 'superadmin.undangan']))->name('undangan.index');
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
    });
});

// Development Quick-Login Route (local testing only)
if (app()->environment('local')) {
    Route::get('/dev-login/{role}', function (string $role) {
        $targetUser = match ($role) {
            'superadmin', 'super_admin' => \App\Models\User::role('super_admin')->first(),
            'admin', 'agency_admin' => \App\Models\User::role('agency_admin')->first(),
            default => \App\Models\User::role('student')->first() ?? \App\Models\User::first(),
        };

        if (!$targetUser) {
            return response("User with role '{$role}' not found in database. Run: php -d browscap=\"\" artisan db:seed", 404);
        }

        \Illuminate\Support\Facades\Auth::login($targetUser);

        return match ($role) {
            'superadmin', 'super_admin' => redirect()->route('superadmin.dashboard'),
            'admin', 'agency_admin' => redirect()->route('admin.dashboard'),
            default => redirect()->route('home'),
        };
    })->name('dev.login');
}

require __DIR__ . '/auth.php';
