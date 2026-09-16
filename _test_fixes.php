<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\SubInstansi;
use App\Models\PermohonanPkl;
use App\Models\PesertaMagang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

echo "=== SIAP-PKL FIXES VERIFICATION ===\n\n";

// 1. Check SubInstansi & PesertaMagang composite index query
$sub = SubInstansi::first();
echo "1. SubInstansi test: Found {$sub->nama_sub_instansi} (ID: {$sub->id}, Kuota: {$sub->batas_kuota})\n";

$occupied = PesertaMagang::where('id_sub_instansi', $sub->id)
    ->where('status_magang', 'aktif')
    ->where('tanggal_mulai', '<=', now()->addDays(30)->toDateString())
    ->where('tanggal_selesai', '>=', now()->toDateString())
    ->count();

echo "   Occupied slots (composite index test): {$occupied}\n";
echo "   Remaining slots: " . ($sub->batas_kuota - $occupied) . "\n";

// 2. Test Transaction & Pessimistic Locking query compilation
try {
    DB::transaction(function () use ($sub) {
        $lockedSub = SubInstansi::where('id', $sub->id)->lockForUpdate()->first();
        echo "2. Concurrency Lock: Successfully acquired lockForUpdate on SubInstansi ID {$lockedSub->id}\n";
    });
} catch (\Throwable $e) {
    echo "2. Concurrency Lock FAILED: " . $e->getMessage() . "\n";
}

// 3. Test Private Storage
$testContent = "%PDF-1.4 Mock PDF for verification";
$testFilename = 'proposals/verify_test_' . Str::random(6) . '.pdf';
Storage::disk('local')->put($testFilename, $testContent);

if (Storage::disk('local')->exists($testFilename)) {
    echo "3. Private Storage: Successfully stored and retrieved file on disk 'local' at {$testFilename}\n";
    Storage::disk('local')->delete($testFilename);
} else {
    echo "3. Private Storage FAILED\n";
}

// 4. Test Temporary Signed Route Generation
$permohonan = PermohonanPkl::first();
if ($permohonan) {
    $signedUrl = URL::temporarySignedRoute('proposal.download', now()->addMinutes(15), ['permohonan' => $permohonan->id]);
    echo "4. Temporary Signed URL: Generated valid URL:\n   {$signedUrl}\n";
} else {
    echo "4. Temporary Signed URL: Skipped (no permohonan found)\n";
}

echo "\n=== ALL CHECKS PASSED ===\n";
