<template>
  <AppLayout>
    <div class="space-y-6 max-w-6xl mx-auto">
      
      <!-- Top Header -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            <i class="fa-solid fa-server text-teal-600"></i>
            <span>Status Sistem & Ketahanan Database</span>
          </h1>
          <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
            <i class="fa-regular fa-circle-check text-slate-400"></i>
            Pemantauan performa database, ketahanan konkurensi, dan cadangan data aplikasi Sipreline.
          </p>
        </div>

        <!-- Trigger Backup Button -->
        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="triggerBackup"
            :disabled="loadingBackup"
            class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer disabled:opacity-50"
          >
            <i class="fa-solid fa-database text-xs" :class="{ 'fa-spin': loadingBackup }"></i>
            <span>{{ loadingBackup ? 'Mencadangkan...' : 'Cadangkan Database Sekarang' }}</span>
          </button>
        </div>
      </div>

      <!-- Metric Stat Cards (Consistent with Admin Dashboard) -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-purple-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-code-branch text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Versi Aplikasi</p>
            <p class="text-xl font-black text-slate-900 mt-0.5 truncate">{{ status.versi_aplikasi }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">PHP {{ status.versi_php }} · Laravel {{ status.versi_laravel }}</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-teal-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-database text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Ukuran Database</p>
            <p class="text-xl font-black text-teal-900 mt-0.5">{{ status.db_size }}</p>
            <p class="text-[11px] text-teal-700 mt-0.5">{{ status.db_driver }} cloud persistent</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-emerald-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-bolt text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Koneksi Database</p>
            <p class="text-xl font-black text-emerald-900 mt-0.5">{{ status.wal_mode }}</p>
            <p class="text-[11px] text-emerald-700 mt-0.5">Timeout: {{ status.busy_timeout }}</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-blue-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-clock-rotate-left text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Cadangan Terakhir</p>
            <p class="text-sm font-black text-blue-900 mt-1 truncate">{{ status.terakhir_cadangan }}</p>
            <p class="text-[11px] text-blue-600 mt-0.5">{{ status.total_cadangan }} file tersimpan</p>
          </div>
        </div>
      </div>

      <!-- Resiliency & Operational Information Card -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs">
        <h2 class="text-sm sm:text-base font-bold text-slate-900 mb-3 flex items-center gap-2 pb-3 border-b border-slate-100">
          <i class="fa-solid fa-shield-halved text-teal-600"></i>
          <span>Ketahanan Sistem & Arsitektur Operasional Sekolah</span>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-600">
          <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-1.5 hover:border-teal-200 transition">
            <div class="flex items-center gap-2 font-bold text-slate-800">
              <i class="fa-solid fa-bolt text-teal-600"></i>
              <span>1. Konkurensi & Anti Database Lock</span>
            </div>
            <p class="text-slate-600 leading-relaxed">
              Arsitektur database cloud PostgreSQL Supabase dengan Session Pooler dan penanganan timeout mencegah lock, sehingga seluruh guru dapat mengisi absensi serentak tanpa kendala.
            </p>
          </div>

          <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-1.5 hover:border-teal-200 transition">
            <div class="flex items-center gap-2 font-bold text-slate-800">
              <i class="fa-solid fa-clock text-teal-600"></i>
              <span>2. Jadwal Cadangan Otomatis</span>
            </div>
            <p class="text-slate-600 leading-relaxed">
              Database cloud menjaga snapshots dan sistem mencadangkan data berkala sehingga arsip aman tersimpan di cloud storage.
            </p>
          </div>

          <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-1.5 hover:border-teal-200 transition">
            <div class="flex items-center gap-2 font-bold text-slate-800">
              <i class="fa-solid fa-right-left text-teal-600"></i>
              <span>3. Portabilitas Database</span>
            </div>
            <p class="text-slate-600 leading-relaxed">
              Seluruh akses data memakai Eloquent ORM & Query Builder standar tanpa sintaks kustom vendor, sehingga kompatibel di PostgreSQL, SQLite, maupun MySQL.
            </p>
          </div>

          <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-1.5 hover:border-teal-200 transition">
            <div class="flex items-center gap-2 font-bold text-slate-800">
              <i class="fa-solid fa-terminal text-teal-600"></i>
              <span>4. Pemulihan Cepat (Disaster Recovery)</span>
            </div>
            <p class="text-slate-600 leading-relaxed">
              Data tersimpan permanen di cloud database Supabase. Jika terjadi masalah pada deployment serverless Vercel, cukup deploy ulang dari Git tanpa risiko kehilangan data presensi.
            </p>
          </div>
        </div>
      </div>

      <!-- Backup Files History Table Card -->
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <h2 class="text-sm sm:text-base font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-box-archive text-teal-600"></i>
            <span>Daftar File Cadangan Database (14 Hari Terakhir)</span>
          </h2>
          <span class="text-xs text-slate-500 font-semibold px-2.5 py-1 rounded-full bg-slate-100 inline-flex items-center gap-1.5 self-start sm:self-auto">
            <i class="fa-solid fa-folder-open text-teal-600 text-[10px]"></i>
            {{ daftarCadangan.length }} File Tersimpan
          </span>
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
                  <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center text-xs shrink-0 border border-emerald-200">
                    <i class="fa-solid fa-database"></i>
                  </div>
                  <span>{{ b.nama }}</span>
                </td>
                <td class="py-3 px-4 text-slate-600 font-semibold">
                  <span class="px-2 py-0.5 rounded-md bg-slate-100 text-[11px] font-mono">
                    {{ b.ukuran }}
                  </span>
                </td>
                <td class="py-3 px-4 text-slate-500">{{ b.waktu }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="p-12 text-center text-slate-400 text-xs">
          <i class="fa-solid fa-database text-4xl text-slate-300 mb-2 block"></i>
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
