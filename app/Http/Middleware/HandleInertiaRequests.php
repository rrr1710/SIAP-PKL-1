<?php

namespace App\Http\Middleware;

use App\Models\PermohonanPkl;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // Compute once; lazy closure so it only runs when Inertia actually serialises
        $hasActiveApplication = $user
            ? fn () => PermohonanPkl::where('id_pemohon', $user->id)
                ->whereIn('status', ['menunggu', 'diterima'])
                ->exists()
            : false;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), [
                    'tipe_pendaftaran' => $user->tipe_pendaftaran,
                ]) : null,
                'roles' => $user ? $user->getRoleNames() : [],
                'has_active_application' => $hasActiveApplication,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
