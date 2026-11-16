<template>
  <AppLayout>
    <div class="space-y-5 max-w-4xl mx-auto">
      
      <!-- Child Switcher (O3) if parent has multiple children -->
      <div v-if="anakList.length > 1" class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
          <i class="fa-solid fa-children text-teal-600"></i>
          <span>Pilih Anak:</span>
        </label>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="anak in anakList"
            :key="anak.id"
            type="button"
            @click="switchAnak(anak.id)"
            :class="[
              'px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition flex items-center gap-2 cursor-pointer',
              anakTerpilih?.id === anak.id
                ? 'bg-teal-600 text-white shadow-sm ring-2 ring-teal-600 ring-offset-1'
                : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
            ]"
          >
            <i :class="anak.jenis_kelamin === 'L' ? 'fa-solid fa-child' : 'fa-solid fa-child-dress'"></i>
            <span>{{ anak.nama }}</span>
            <span class="text-[11px] opacity-80">(Kelas {{ anak.kelas }})</span>
          </button>
        </div>
      </div>

      <!-- Child Identity Banner & Month Filter -->
      <div v-if="anakTerpilih" class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-teal-100 text-teal-800 flex items-center justify-center text-xl border border-teal-200 shadow-xs">
              <i :class="anakTerpilih.jenis_kelamin === 'L' ? 'fa-solid fa-child text-2xl' : 'fa-solid fa-child-dress text-2xl'"></i>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                  {{ anakTerpilih.nama }}
                </h1>
                <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                  Kelas {{ anakTerpilih.kelas }}
                </span>
              </div>
              <p class="text-xs text-slate-500 mt-0.5">
                NIS: {{ anakTerpilih.nis }} · Wali Kelas: {{ anakTerpilih.wali_kelas }}
              </p>
            </div>
          </div>

          <!-- Month Selector -->
          <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-slate-500 hidden sm:inline flex items-center gap-1">
              <i class="fa-solid fa-calendar-days text-slate-400"></i>
              Bulan:
            </span>
            <input
              type="month"
              v-model="selectedBulan"
              @change="refreshData"
              class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-teal-500"
            />
          </div>
        </div>

        <!-- Monthly Attendance Summary Cards (O2) -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 sm:gap-3 mt-6 pt-5 border-t border-slate-100">
          <!-- % Hadir -->
          <div class="col-span-2 sm:col-span-1 p-3.5 bg-gradient-to-br from-teal-500 to-emerald-600 rounded-2xl text-white shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-bold uppercase tracking-wider text-teal-100 flex items-center gap-1">
              <i class="fa-solid fa-percent text-[10px]"></i>
              Kehadiran
            </span>
            <p class="text-2xl font-black mt-1">{{ ringkasan.persentase }}%</p>
            <span class="text-[10px] text-teal-100 mt-1">{{ ringkasan.hari_efektif }} hari sekolah</span>
          </div>

          <!-- Hadir -->
          <div class="p-3.5 bg-emerald-50 rounded-2xl border border-emerald-200">
            <span class="text-[11px] font-bold uppercase text-emerald-800 flex items-center gap-1">
              <i class="fa-solid fa-circle-check text-[10px]"></i>
              Hadir
            </span>
            <p class="text-2xl font-black text-emerald-900 mt-1">{{ ringkasan.hadir }}</p>
            <span class="text-[10px] text-emerald-700">Hari</span>
          </div>

          <!-- Izin -->
          <div class="p-3.5 bg-amber-50 rounded-2xl border border-amber-200">
            <span class="text-[11px] font-bold uppercase text-amber-800 flex items-center gap-1">
              <i class="fa-solid fa-envelope-open-text text-[10px]"></i>
              Izin
            </span>
            <p class="text-2xl font-black text-amber-900 mt-1">{{ ringkasan.izin }}</p>
            <span class="text-[10px] text-amber-700">Hari</span>
          </div>

          <!-- Sakit -->
          <div class="p-3.5 bg-blue-50 rounded-2xl border border-blue-200">
            <span class="text-[11px] font-bold uppercase text-blue-800 flex items-center gap-1">
              <i class="fa-solid fa-head-side-cough text-[10px]"></i>
              Sakit
            </span>
            <p class="text-2xl font-black text-blue-900 mt-1">{{ ringkasan.sakit }}</p>
            <span class="text-[10px] text-blue-700">Hari</span>
          </div>

          <!-- Alpa -->
          <div class="p-3.5 bg-rose-50 rounded-2xl border border-rose-200">
            <span class="text-[11px] font-bold uppercase text-rose-800 flex items-center gap-1">
              <i class="fa-solid fa-circle-xmark text-[10px]"></i>
              Alpa
            </span>
            <p class="text-2xl font-black text-rose-900 mt-1">{{ ringkasan.alpa }}</p>
            <span class="text-[10px] text-rose-700">Hari</span>
          </div>
        </div>
      </div>

      <!-- Attendance History Log (O1) -->
      <div v-if="anakTerpilih" class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <h2 class="font-bold text-sm sm:text-base text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-teal-600"></i>
            <span>Catatan Kehadiran — Periode {{ namaBulan }}</span>
          </h2>
          <span class="text-xs text-slate-500 font-medium">
            {{ daftarAbsensi.length }} Catatan
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
              <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex flex-col items-center justify-center font-bold shrink-0">
                <span class="text-[10px] uppercase font-semibold text-slate-400">{{ item.hari.substring(0, 3) }}</span>
                <span class="text-sm font-extrabold text-slate-800 leading-none">{{ item.tanggal.split('-')[2] }}</span>
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
                  'px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1.5',
                  item.status === 'H' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : '',
                  item.status === 'I' ? 'bg-amber-100 text-amber-800 border border-amber-300' : '',
                  item.status === 'S' ? 'bg-blue-100 text-blue-800 border border-blue-300' : '',
                  item.status === 'A' ? 'bg-rose-100 text-rose-800 border border-rose-300' : ''
                ]"
              >
                <template v-if="item.status === 'H'">
                  <i class="fa-solid fa-circle-check text-xs"></i>
                  <span>Hadir</span>
                </template>
                <template v-else-if="item.status === 'I'">
                  <i class="fa-solid fa-envelope-open-text text-xs"></i>
                  <span>Izin</span>
                </template>
                <template v-else-if="item.status === 'S'">
                  <i class="fa-solid fa-head-side-cough text-xs"></i>
                  <span>Sakit</span>
                </template>
                <template v-else>
                  <i class="fa-solid fa-circle-xmark text-xs"></i>
                  <span>Alpa</span>
                </template>
              </span>
            </div>
          </div>
        </div>

        <div v-else class="p-10 text-center text-slate-400 text-xs">
          <i class="fa-regular fa-calendar-xmark text-3xl text-slate-300 mb-2 block"></i>
          Belum ada catatan absensi anak untuk periode bulan ini.
        </div>
      </div>

      <!-- If no children linked -->
      <div v-else class="p-10 bg-white rounded-2xl border border-slate-200 text-center">
        <i class="fa-solid fa-user-xmark text-4xl text-slate-300 mb-2 block"></i>
        <p class="text-slate-600 text-sm">
          Akun Anda belum terhubung dengan data siswa manapun. Silakan hubungi wali kelas.
        </p>
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
