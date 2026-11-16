<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $userData = null;
        if ($user) {
            $user->loadMissing(['kelasDiampu', 'anak.kelas']);
            $userData = [
                'id' => $user->id,
                'username' => $user->username,
                'nama' => $user->nama,
                'peran' => $user->peran,
                'aktif' => $user->aktif,
                'kelas_diampu' => $user->kelasDiampu ? [
                    'id' => $user->kelasDiampu->id,
                    'nama' => $user->kelasDiampu->nama,
                    'tahun_ajaran' => $user->kelasDiampu->tahun_ajaran,
                ] : null,
                'anak' => $user->anak->map(fn ($s) => [
                    'id' => $s->id,
                    'nis' => $s->nis,
                    'nama' => $s->nama,
                    'kelas' => $s->kelas ? $s->kelas->nama : '-',
                ]),
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $userData,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'info' => $request->session()->get('info'),
                'warning' => $request->session()->get('warning'),
                'temp_password' => $request->session()->get('temp_password'),
                'temp_username' => $request->session()->get('temp_username'),
            ],
        ];
    }
}
