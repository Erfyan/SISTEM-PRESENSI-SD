<template>
  <AppLayout>
    <div class="space-y-6 max-w-5xl mx-auto">
      
      <!-- Header -->
      <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
              Status Sistem & Ketahanan Database
            </h1>
            <p class="text-xs text-slate-500 mt-1">
              Pemantauan performa SQLite (WAL Mode), cadangan database harian, dan kesehatan aplikasi Sipreline.
            </p>
          </div>

          <!-- Trigger Backup Button -->
          <div>
            <button
              type="button"
              @click="triggerBackup"
              :disabled="loadingBackup"
              class="w-full sm:w-auto px-4 py-2.5 bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 shadow-xs cursor-pointer disabled:opacity-50"
            >
              <i class="fa-solid fa-database text-xs" :class="{ 'fa-spin': loadingBackup }"></i>
              <span>{{ loadingBackup ? 'Mencadangkan...' : 'Cadangkan Database Sekarang' }}</span>
            </button>
          </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5 pt-4 border-t border-slate-100">
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
            <span class="text-[11px] font-semibold text-slate-500 uppercase flex items-center gap-1">
              <i class="fa-solid fa-code-branch text-slate-400"></i>
              Versi Aplikasi
            </span>
            <p class="text-sm font-black text-slate-900 mt-1 truncate">{{ status.versi_aplikasi }}</p>
            <p class="text-[10px] text-slate-500 mt-0.5">PHP {{ status.versi_php }} · Laravel {{ status.versi_laravel }}</p>
          </div>

          <div class="p-3 bg-teal-50 rounded-xl border border-teal-200">
            <span class="text-[11px] font-semibold text-teal-700 uppercase flex items-center gap-1">
              <i class="fa-solid fa-hard-drive text-teal-600"></i>
              Ukuran Database
            </span>
            <p class="text-xl font-black text-teal-900 mt-0.5">{{ status.db_size }}</p>
            <p class="text-[10px] text-teal-700 mt-0.5">{{ status.db_path }} ({{ status.db_driver }})</p>
          </div>

          <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200">
            <span class="text-[11px] font-semibold text-emerald-700 uppercase flex items-center gap-1">
              <i class="fa-solid fa-bolt text-emerald-600"></i>
              SQLite WAL Mode
            </span>
            <p class="text-xl font-black text-emerald-900 mt-0.5">{{ status.wal_mode }}</p>
            <p class="text-[10px] text-emerald-700 mt-0.5">Timeout: {{ status.busy_timeout }}</p>
          </div>

          <div class="p-3 bg-blue-50 rounded-xl border border-blue-200">
            <span class="text-[11px] font-semibold text-blue-700 uppercase flex items-center gap-1">
              <i class="fa-solid fa-clock-rotate-left text-blue-600"></i>
              Cadangan Terakhir
            </span>
            <p class="text-xs font-bold text-blue-900 mt-1 truncate">{{ status.terakhir_cadangan }}</p>
            <p class="text-[10px] text-blue-600 mt-0.5">{{ status.total_cadangan }} file tersimpan</p>
          </div>
        </div>
      </div>

      <!-- Resiliency & Operational Information Card -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
        <h2 class="text-sm font-bold text-slate-900 mb-2 flex items-center gap-2">
          <i class="fa-solid fa-shield-halved text-teal-600"></i>
          <span>Status Ketahanan SQLite & Operasional Sekolah</span>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-600">
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
            <span class="font-bold text-slate-800">1. Konkurensi & Anti Database Lock</span>
            <p>
              WAL (Write-Ahead Logging) diaktifkan bersama <code>busy_timeout: 5000ms</code>. Guru dapat menginput absensi secara bersamaan di jam sibuk tanpa error lock.
            </p>
          </div>
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
            <span class="font-bold text-slate-800">2. Jadwal Cadangan Otomatis</span>
            <p>
              Sistem mencadangkan database setiap hari pukul 01:00 pagi dan menyimpan arsip 14 hari terakhir secara otomatis via Task Scheduler / Cron.
            </p>
          </div>
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
            <span class="font-bold text-slate-800">3. Portabilitas Database</span>
            <p>
              Seluruh akses data memakai Eloquent ORM & Query Builder standar tanpa sintaks kustom SQLite, sehingga siap dimigrasikan ke MySQL/PostgreSQL kapan saja.
            </p>
          </div>
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
            <span class="font-bold text-slate-800">4. Perintah Pemulihan Cepat (Disaster Recovery)</span>
            <p>
              Bila terjadi kerusakan server, jalankan command: <br />
              <code class="bg-slate-200 px-1 py-0.5 rounded text-[11px] font-mono text-slate-900">php artisan app:restore-db --latest</code>
            </p>
          </div>
        </div>
      </div>

      <!-- Backup Files History Table -->
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
          <h2 class="text-sm font-bold text-slate-900">Daftar File Cadangan (14 Hari Terakhir)</h2>
          <span class="text-xs text-slate-500">{{ daftarCadangan.length }} file tersimpan di storage/app/backups</span>
        </div>

        <div v-if="daftarCadangan.length > 0" class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
              <tr>
                <th class="py-3 px-4 w-12 text-center">No</th>
                <th class="py-3 px-4">Nama File Cadangan</th>
                <th class="py-3 px-4 w-32">Ukuran</th>
                <th class="py-3 px-4 w-48">Waktu Pembuatan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(b, idx) in daftarCadangan" :key="b.nama" class="hover:bg-slate-50 transition">
                <td class="py-3 px-4 text-center font-medium text-slate-400">{{ idx + 1 }}</td>
                <td class="py-3 px-4 font-mono font-medium text-slate-800 flex items-center gap-2">
                  <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                  </svg>
                  <span>{{ b.nama }}</span>
                </td>
                <td class="py-3 px-4 text-slate-600 font-semibold">{{ b.ukuran }}</td>
                <td class="py-3 px-4 text-slate-500">{{ b.waktu }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="p-8 text-center text-slate-500 text-xs">
          Belum ada file cadangan yang tersimpan. Klik tombol "Cadangkan Database Sekarang" di atas.
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  status: {
    type: Object,
    required: true,
  },
  daftarCadangan: {
    type: Array,
    default: () => [],
  },
});

const loadingBackup = ref(false);

const triggerBackup = () => {
  loadingBackup.value = true;
  router.post('/admin/status-sistem/backup', {}, {
    preserveScroll: true,
    onFinish: () => {
      loadingBackup.value = false;
    },
  });
};
</script>
