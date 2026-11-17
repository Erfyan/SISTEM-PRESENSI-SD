<template>
  <AppLayout>
    <div class="space-y-6 max-w-6xl mx-auto">
      
      <!-- Top Header & Child Identity Banner -->
      <div v-if="anakTerpilih" class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <div class="w-14 h-14 rounded-2xl bg-teal-100 text-teal-800 flex items-center justify-center text-2xl border border-teal-200 shadow-xs shrink-0">
            <i :class="anakTerpilih.jenis_kelamin === 'L' ? 'fa-solid fa-child text-2xl' : 'fa-solid fa-child-dress text-2xl'"></i>
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                {{ anakTerpilih.nama }}
              </h1>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-teal-50 text-teal-700 border border-teal-200 inline-flex items-center gap-1">
                <i class="fa-solid fa-school text-[10px]"></i>
                Kelas {{ anakTerpilih.kelas }}
              </span>
            </div>
            <p class="text-xs text-slate-500 mt-1 flex items-center gap-2 flex-wrap">
              <span class="font-mono font-medium">NIS: {{ anakTerpilih.nis }}</span>
              <span>·</span>
              <span class="inline-flex items-center gap-1">
                <i class="fa-solid fa-chalkboard-user text-slate-400"></i>
                Wali Kelas: <strong class="text-slate-700">{{ anakTerpilih.wali_kelas || '-' }}</strong>
              </span>
            </p>
          </div>
        </div>

        <!-- Month Selector -->
        <div class="flex items-center gap-2 self-start sm:self-auto">
          <label class="text-xs font-semibold text-slate-500 flex items-center gap-1">
            <i class="fa-solid fa-calendar-days text-slate-400"></i>
            <span>Bulan:</span>
          </label>
          <input
            type="month"
            v-model="selectedBulan"
            @change="refreshData"
            class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-teal-500 transition"
          />
        </div>
      </div>

      <!-- Child Switcher (if parent has multiple children) -->
      <div v-if="anakList.length > 1" class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
          <i class="fa-solid fa-children text-teal-600"></i>
          <span>Pilih Profil Anak:</span>
        </label>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="anak in anakList"
            :key="anak.id"
            type="button"
            @click="switchAnak(anak.id)"
            :class="[
              'px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition flex items-center gap-2 cursor-pointer shadow-xs',
              anakTerpilih?.id === anak.id
                ? 'bg-teal-600 text-white shadow-sm ring-2 ring-teal-600 ring-offset-2'
                : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'
            ]"
          >
            <i :class="anak.jenis_kelamin === 'L' ? 'fa-solid fa-child' : 'fa-solid fa-child-dress'"></i>
            <span>{{ anak.nama }}</span>
            <span class="text-[11px] opacity-80">(Kelas {{ anak.kelas }})</span>
          </button>
        </div>
      </div>

      <!-- Monthly Attendance Summary Cards (Consistent with Admin Dashboard) -->
      <div v-if="anakTerpilih" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <!-- % Kehadiran -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5 hover:border-teal-300 transition col-span-2 sm:col-span-1">
          <div class="w-12 h-12 rounded-2xl bg-teal-600 text-white flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-percent text-lg"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Kehadiran</p>
            <p class="text-2xl font-black text-teal-700 mt-0.5">{{ ringkasan.persentase }}%</p>
            <p class="text-[11px] text-slate-400 mt-0.5">{{ ringkasan.hari_efektif }} hari sekolah</p>
          </div>
        </div>

        <!-- Hadir -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5 hover:border-emerald-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-circle-check text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Hadir</p>
            <p class="text-2xl font-black text-emerald-700 mt-0.5">{{ ringkasan.hadir }}</p>
            <p class="text-[11px] text-emerald-600 mt-0.5">Hari masuk</p>
          </div>
        </div>

        <!-- Izin -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5 hover:border-amber-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-envelope-open-text text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Izin</p>
            <p class="text-2xl font-black text-amber-700 mt-0.5">{{ ringkasan.izin }}</p>
            <p class="text-[11px] text-amber-600 mt-0.5">Hari berizin</p>
          </div>
        </div>

        <!-- Sakit -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5 hover:border-blue-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-head-side-cough text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Sakit</p>
            <p class="text-2xl font-black text-blue-700 mt-0.5">{{ ringkasan.sakit }}</p>
            <p class="text-[11px] text-blue-600 mt-0.5">Hari surat sakit</p>
          </div>
        </div>

        <!-- Alpa -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5 hover:border-rose-300 transition col-span-2 sm:col-span-1">
          <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-circle-xmark text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Tanpa Keterangan</p>
            <p class="text-2xl font-black text-rose-700 mt-0.5">{{ ringkasan.alpa }}</p>
            <p class="text-[11px] text-rose-600 mt-0.5">Hari alpa</p>
          </div>
        </div>
      </div>

      <!-- Attendance History Log Card -->
      <div v-if="anakTerpilih" class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <h2 class="font-bold text-slate-900 text-sm sm:text-base flex items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-teal-600"></i>
            <span>Riwayat Kehadiran Harian — Periode {{ namaBulan }}</span>
          </h2>
          <span class="text-xs text-slate-500 font-semibold px-2.5 py-1 rounded-full bg-slate-100 inline-flex items-center gap-1.5 self-start sm:self-auto">
            <i class="fa-solid fa-list-check text-teal-600 text-[10px]"></i>
            {{ daftarAbsensi.length }} Catatan Kehadiran
          </span>
        </div>

        <div v-if="daftarAbsensi.length > 0" class="divide-y divide-slate-100">
          <div
            v-for="item in daftarAbsensi"
            :key="item.id"
            class="p-4 sm:px-6 hover:bg-slate-50 transition flex items-center justify-between gap-4"
          >
            <!-- Date & Day -->
            <div class="flex items-center gap-3">
              <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 flex flex-col items-center justify-center font-bold shrink-0 border border-slate-200">
                <span class="text-[9px] uppercase font-bold text-slate-400">{{ item.hari ? item.hari.substring(0, 3) : '' }}</span>
                <span class="text-sm font-black text-slate-800 leading-none">{{ item.tanggal ? item.tanggal.split('-')[2] : '' }}</span>
              </div>
              <div>
                <p class="text-xs sm:text-sm font-bold text-slate-900">{{ item.tanggal_formatted }}</p>
                <p v-if="item.catatan" class="text-xs text-slate-500 mt-0.5">
                  Catatan Guru: <span class="text-slate-700 italic">"{{ item.catatan }}"</span>
                </p>
              </div>
            </div>

            <!-- Status Pill (Using Font Awesome Icons) -->
            <div>
              <span
                :class="[
                  'px-3 py-1.5 rounded-full text-xs font-bold inline-flex items-center gap-1.5 shadow-2xs',
                  item.status === 'H' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : '',
                  item.status === 'I' ? 'bg-amber-100 text-amber-800 border border-amber-300' : '',
                  item.status === 'S' ? 'bg-blue-100 text-blue-800 border border-blue-300' : '',
                  item.status === 'A' ? 'bg-rose-100 text-rose-800 border border-rose-300' : ''
                ]"
              >
                <template v-if="item.status === 'H'">
                  <i class="fa-solid fa-circle-check text-xs text-emerald-600"></i>
                  <span>Hadir</span>
                </template>
                <template v-else-if="item.status === 'I'">
                  <i class="fa-solid fa-envelope-open-text text-xs text-amber-600"></i>
                  <span>Izin</span>
                </template>
                <template v-else-if="item.status === 'S'">
                  <i class="fa-solid fa-head-side-cough text-xs text-blue-600"></i>
                  <span>Sakit</span>
                </template>
                <template v-else>
                  <i class="fa-solid fa-circle-xmark text-xs text-rose-600"></i>
                  <span>Alpa</span>
                </template>
              </span>
            </div>
          </div>
        </div>

        <div v-else class="p-12 text-center text-slate-400 text-xs">
          <i class="fa-regular fa-calendar-xmark text-4xl text-slate-300 mb-2 block"></i>
          Belum ada catatan absensi anak untuk periode bulan ini.
        </div>
      </div>

      <!-- If no children linked -->
      <div v-else class="p-12 bg-white rounded-2xl border border-slate-200 text-center shadow-xs">
        <i class="fa-solid fa-user-xmark text-4xl text-slate-300 mb-2 block"></i>
        <p class="text-slate-700 text-sm font-bold">
          Akun Anda belum terhubung dengan data siswa manapun.
        </p>
        <p class="text-slate-500 text-xs mt-1">Silakan hubungi wali kelas atau pihak tata usaha sekolah.</p>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  anakList: {
    type: Array,
    default: () => [],
  },
  anakTerpilih: {
    type: Object,
    default: null,
  },
  bulan: {
    type: String,
    required: true,
  },
  namaBulan: {
    type: String,
    default: '',
  },
  ringkasan: {
    type: Object,
    default: () => ({
      hadir: 0,
      izin: 0,
      sakit: 0,
      alpa: 0,
      hari_efektif: 0,
      persentase: 0,
    }),
  },
  daftarAbsensi: {
    type: Array,
    default: () => [],
  },
});

const selectedBulan = ref(props.bulan);

const switchAnak = (id) => {
  router.get('/portal-ortu', {
    siswa_id: id,
    bulan: selectedBulan.value,
  }, {
    preserveState: false,
    preserveScroll: true,
  });
};

const refreshData = () => {
  router.get('/portal-ortu', {
    siswa_id: props.anakTerpilih?.id,
    bulan: selectedBulan.value,
  }, {
    preserveState: false,
    preserveScroll: true,
  });
};
</script>
