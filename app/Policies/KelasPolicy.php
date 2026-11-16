<?php

namespace App\Policies;

use App\Models\Kelas;
use App\Models\User;

class KelasPolicy
{
    /**
     * Boleh melihat absensi/rekap kelas jika admin atau wali kelas tersebut.
     */
    public function view(User $user, Kelas $kelas): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isGuru() && $kelas->wali_id === $user->id;
    }

    /**
     * Boleh menginput/mengubah absensi kelas jika admin atau wali kelas tersebut.
     */
    public function update(User $user, Kelas $kelas): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isGuru() && $kelas->wali_id === $user->id;
    }
}
