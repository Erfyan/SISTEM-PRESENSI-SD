<?php

namespace App\Policies;

use App\Models\Siswa;
use App\Models\User;

class SiswaPolicy
{
    /**
     * Boleh melihat data kehadiran anak:
     * - Admin
     * - Guru (wali kelas anak tersebut)
     * - Orang tua dari anak tersebut
     */
    public function view(User $user, Siswa $siswa): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isGuru()) {
            return $siswa->kelas && $siswa->kelas->wali_id === $user->id;
        }

        if ($user->isOrtu()) {
            return $siswa->orangTua()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Boleh mengubah data siswa:
     * - Admin
     * - Guru (wali kelas anak tersebut)
     */
    public function update(User $user, Siswa $siswa): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isGuru()) {
            return $siswa->kelas && $siswa->kelas->wali_id === $user->id;
        }

        return false;
    }
}
