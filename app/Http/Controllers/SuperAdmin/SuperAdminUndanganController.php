<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Instansi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SuperAdminUndanganController extends Controller
{
    public function index()
    {
        // Get all active agencies for the dropdown
        $instansiOptions = Instansi::where('status', 'aktif')
            ->orderBy('nama_instansi')
            ->get(['id', 'nama_instansi']);

        // Get all agency admins
        $admins = User::role('agency_admin')
            ->with('instansi')
            ->latest()
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'nama_lengkap' => $user->nama_lengkap,
                    'instansi' => $user->instansi ? $user->instansi->nama_instansi : '-',
                    'google_id' => $user->google_id,
                    // Determine connection status based on whether google_id exists and isn't a seed placeholder
                    'oauth_connected' => !empty($user->google_id) && !str_starts_with($user->google_id, 'seed_'),
                    'tanggal' => $user->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return Inertia::render('SuperAdmin/Undangan/Index', [
            'activeNav' => 'superadmin.undangan',
            'instansiOptions' => $instansiOptions,
            'riwayatUndangan' => $admins,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'id_instansi' => ['required', 'integer', 'exists:instansi,id'],
        ]);

        DB::transaction(function () use ($request) {
            $user = User::where('email', $request->email)->first();

            if ($user) {
                // Update existing user
                $user->update([
                    'id_instansi' => $request->id_instansi,
                ]);
                $user->syncRoles(['agency_admin']);
            } else {
                // Create new user (Direct provisioning without Google OAuth initially)
                $user = User::create([
                    'email' => $request->email,
                    'nama_lengkap' => 'Admin ' . Instansi::find($request->id_instansi)->nama_instansi,
                    'id_instansi' => $request->id_instansi,
                    'google_id' => null, // Needs to be explicitly null if not set
                ]);
                $user->assignRole('agency_admin');
            }
        });

        return back()->with('success', 'Admin berhasil diatur untuk email ' . $request->email);
    }
}
