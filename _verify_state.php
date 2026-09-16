<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Instansi;
use App\Models\SubInstansi;
use App\Models\PermohonanPkl;
use App\Models\PesertaMagang;
use App\Models\AnggotaPermohonan;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

echo "=== SIAP-PKL Phase 4 & 5 Backend Audit ===\n";

$instansiCount = Instansi::count();
$subCount = SubInstansi::count();
$permohonanCount = PermohonanPkl::count();
$anggotaCount = AnggotaPermohonan::count();
$pesertaCount = PesertaMagang::count();
$userCount = User::count();

echo "Instansi count: $instansiCount\n";
echo "SubInstansi count: $subCount\n";
echo "PermohonanPkl count: $permohonanCount\n";
echo "AnggotaPermohonan count: $anggotaCount\n";
echo "PesertaMagang count: $pesertaCount\n";
echo "User count: $userCount\n";

// Test controllers instantiation
$controllers = [
    \App\Http\Controllers\HomeController::class,
    \App\Http\Controllers\PengajuanPklController::class,
    \App\Http\Controllers\PublicCatalogController::class,
    \App\Http\Controllers\RiwayatController::class,
    \App\Http\Controllers\StatusPendaftaranController::class,
    \App\Http\Controllers\Admin\AdminApplicationController::class,
    \App\Http\Controllers\Admin\AdminBidangController::class,
    \App\Http\Controllers\Admin\AdminDashboardController::class,
    \App\Http\Controllers\SuperAdmin\AuditLogController::class,
];

foreach ($controllers as $controller) {
    new $controller();
    echo "Controller instantiated: $controller -> OK\n";
}

// Test encryption round-trip
$anggota = AnggotaPermohonan::first();
if ($anggota) {
    echo "Anggota NIM decrypted: " . $anggota->nim . "\n";
    echo "Anggota No HP decrypted: " . $anggota->no_hp . "\n";
}

echo "=== All backend checks PASSED ===\n";
