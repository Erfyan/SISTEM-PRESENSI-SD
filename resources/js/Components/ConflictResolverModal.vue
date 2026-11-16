<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150">
      
      <!-- Modal Header -->
      <div class="p-5 bg-amber-50 border-b border-amber-200 flex items-start gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div>
          <h3 class="text-base font-bold text-amber-950">
            Perbedaan Data Absensi (Konflik Sinkronisasi)
          </h3>
          <p class="text-xs text-amber-800 mt-0.5">
            Server sudah memiliki catatan absensi untuk tanggal ini yang berbeda dengan data yang Anda catat saat offline. Pilih versi mana yang ingin digunakan.
          </p>
        </div>
      </div>

      <!-- Conflicts List -->
      <div class="p-5 max-h-80 overflow-y-auto divide-y divide-slate-100 space-y-4">
        <div v-for="item in conflicts" :key="item.siswa_id" class="pt-3 first:pt-0">
          <div class="flex items-center justify-between mb-2">
            <span class="font-bold text-sm text-slate-900">{{ item.nama }}</span>
            <span class="text-xs text-slate-500">Pilihan: {{ getChoiceLabel(resolutions[item.siswa_id]) }}</span>
          </div>

          <div class="grid grid-cols-2 gap-2 text-xs">
            <!-- Offline Version (HP) -->
            <button
              type="button"
              @click="setChoice(item.siswa_id, 'offline')"
              :class="[
                'p-3 rounded-xl border text-left transition cursor-pointer',
                resolutions[item.siswa_id] === 'offline'
                  ? 'border-teal-500 bg-teal-50 ring-2 ring-teal-500/30'
                  : 'border-slate-200 hover:bg-slate-50'
              ]"
            >
              <div class="flex items-center justify-between font-bold text-teal-800 mb-1">
                <span>Versi Offline (HP Anda)</span>
                <span v-if="resolutions[item.siswa_id] === 'offline'" class="text-teal-600 font-bold">
                  <i class="fa-solid fa-check"></i>
                </span>
              </div>
              <p class="font-semibold text-slate-800">
                Status: <span class="uppercase font-mono px-1.5 py-0.5 rounded bg-slate-100">{{ item.offline_status }}</span>
                {{ statusLabel(item.offline_status) }}
              </p>
              <p v-if="item.offline_catatan" class="text-slate-600 mt-1 italic">
                "{{ item.offline_catatan }}"
              </p>
            </button>

            <!-- Server Version -->
            <button
              type="button"
              @click="setChoice(item.siswa_id, 'server')"
              :class="[
                'p-3 rounded-xl border text-left transition cursor-pointer',
                resolutions[item.siswa_id] === 'server'
                  ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500/30'
                  : 'border-slate-200 hover:bg-slate-50'
              ]"
            >
              <div class="flex items-center justify-between font-bold text-indigo-800 mb-1">
                <span>Versi Server</span>
                <span v-if="resolutions[item.siswa_id] === 'server'" class="text-indigo-600 font-bold">
                  <i class="fa-solid fa-check"></i>
                </span>
              </div>
              <p class="font-semibold text-slate-800">
                Status: <span class="uppercase font-mono px-1.5 py-0.5 rounded bg-slate-100">{{ item.server_status }}</span>
                {{ statusLabel(item.server_status) }}
              </p>
              <p v-if="item.server_catatan" class="text-slate-600 mt-1 italic">
                "{{ item.server_catatan }}"
              </p>
            </button>
          </div>
        </div>
      </div>

      <!-- Quick Action Buttons & Confirmation -->
      <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-2 w-full sm:w-auto">
          <button
            type="button"
            @click="setAll('offline')"
            class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-teal-300 text-teal-800 bg-white hover:bg-teal-50 cursor-pointer"
          >
            Pilih Semua Offline
          </button>
          <button
            type="button"
            @click="setAll('server')"
            class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-indigo-300 text-indigo-800 bg-white hover:bg-indigo-50 cursor-pointer"
          >
            Pilih Semua Server
          </button>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 cursor-pointer"
          >
            Batal
          </button>
          <button
            type="button"
            @click="submitResolution"
            class="px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-md shadow-teal-600/30 cursor-pointer"
          >
            Terapkan & Simpan
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  isOpen: Boolean,
  conflicts: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['resolve', 'close']);

const resolutions = reactive({});

watch(() => props.conflicts, (newConflicts) => {
  newConflicts.forEach(item => {
    // Default: gunakan versi offline
    resolutions[item.siswa_id] = 'offline';
  });
}, { immediate: true });

const setChoice = (siswaId, choice) => {
  resolutions[siswaId] = choice;
};

const setAll = (choice) => {
  props.conflicts.forEach(item => {
    resolutions[item.siswa_id] = choice;
  });
};

const statusLabel = (code) => {
  return {
    'H': '(Hadir)',
    'I': '(Izin)',
    'S': '(Sakit)',
    'A': '(Alpa)'
  }[code] || '';
};

const getChoiceLabel = (choice) => {
  return choice === 'server' ? 'Pakai Server' : 'Pakai Offline (HP)';
};

const submitResolution = () => {
  emit('resolve', { ...resolutions });
};
</script>
