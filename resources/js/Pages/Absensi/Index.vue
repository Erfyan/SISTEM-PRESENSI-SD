<template>
  <AppLayout>
    <div class="space-y-6 max-w-6xl mx-auto pb-28 sm:pb-16">
      
      <!-- Network & Offline Sync Status Indicator Banner -->
      <div
        class="rounded-2xl p-4 border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs shadow-xs"
        :class="bannerStatusClass"
      >
        <div class="flex items-center gap-3">
          <span class="relative flex h-3 w-3 shrink-0">
            <span
              v-if="isOnline && pendingCount === 0"
              class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
            ></span>
            <span
              class="relative inline-flex rounded-full h-3 w-3"
              :class="{
                'bg-emerald-500': isOnline && pendingCount === 0 && syncStatus !== 'syncing',
                'bg-amber-500': isOnline && pendingCount > 0,
                'bg-orange-500': !isOnline,
                'bg-teal-500 animate-spin': syncStatus === 'syncing'
              }"
            ></span>
          </span>
          <div class="space-y-0.5">
            <div class="font-bold flex items-center gap-2">
              <span v-if="!isOnline" class="flex items-center gap-1.5 text-orange-900">
                <i class="fa-solid fa-wifi-slash text-xs"></i>
                Offline — Bekerja Tanpa Internet
              </span>
              <span v-else-if="syncStatus === 'syncing'" class="flex items-center gap-1.5 text-teal-900">
                <i class="fa-solid fa-rotate fa-spin text-xs"></i>
                Menyinkronkan Data ke Server...
              </span>
              <span v-else-if="pendingCount > 0" class="flex items-center gap-1.5 text-amber-900">
                <i class="fa-solid fa-cloud-arrow-up text-xs"></i>
                Menunggu Sinkron ({{ pendingCount }})
              </span>
              <span v-else-if="syncStatus === 'synced'" class="flex items-center gap-1.5 text-emerald-900">
                <i class="fa-solid fa-circle-check text-xs"></i>
                Tersinkron
              </span>
              <span v-else class="flex items-center gap-1.5 text-emerald-900">
                <i class="fa-solid fa-wifi text-xs"></i>
                Online
              </span>

              <span
                v-if="lastOfflineSavedTime"
                class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-orange-100 text-orange-800 border border-orange-200"
              >
                Offline - tersimpan di HP
              </span>
            </div>
            <p class="text-[11px] opacity-90">
              <span v-if="!isOnline">
                Absensi disimpan aman di memori HP (IndexedDB) dan akan otomatis dikirim saat sinyal kembali.
              </span>
              <span v-else-if="pendingCount > 0">
                Ada {{ pendingCount }} absensi offline yang siap disinkronkan ke server.
              </span>
              <span v-else>
                Terhubung ke server. Semua data absensi terbaru sudah tersimpan.
              </span>
            </p>
          </div>
        </div>

        <!-- Sync Action Button -->
        <div v-if="pendingCount > 0 && isOnline" class="flex items-center gap-2 shrink-0">
          <button
            type="button"
            @click="triggerManualSync"
            :disabled="syncStatus === 'syncing'"
            class="w-full sm:w-auto px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white font-bold text-xs transition shadow-xs flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <i class="fa-solid fa-rotate text-xs" :class="{ 'fa-spin': syncStatus === 'syncing' }"></i>
            <span>{{ syncStatus === 'syncing' ? 'Menyinkronkan...' : 'Sinkronkan Sekarang' }}</span>
          </button>
        </div>
      </div>

      <!-- Peringatan Absen Kosong Lewat Jam Batas -->
      <div
        v-if="peringatanAbsenKosong && peringatanAbsenKosong.tampilkan"
        class="rounded-2xl p-4 bg-rose-50 border border-rose-300 text-rose-900 flex items-start sm:items-center gap-3 shadow-xs"
      >
        <div class="w-10 h-10 rounded-xl bg-rose-200 text-rose-800 flex items-center justify-center text-base font-bold shrink-0">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="flex-1">
          <h2 class="font-bold text-sm text-rose-950 flex items-center gap-1.5">
            <span>Pengingat Absensi Harian</span>
          </h2>
          <p class="text-xs text-rose-800 mt-0.5">{{ peringatanAbsenKosong.pesan }} Segera lengkapi dan simpan presensi kelas Anda.</p>
        </div>
      </div>

      <!-- Header & Class/Date Filter -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-clipboard-check text-teal-600"></i>
                <span>Absensi Harian</span>
              </h1>
              <span
                v-if="sudahDisimpan"
                class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 inline-flex items-center gap-1"
              >
                <i class="fa-solid fa-check text-[10px]"></i>
                Sudah Terisi
              </span>
              <span
                v-else
                class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300 inline-flex items-center gap-1"
              >
                <i class="fa-regular fa-clock text-[10px]"></i>
                Belum Disimpan
              </span>
              <span
                v-if="isLoadedFromCache"
                class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-800 border border-blue-200 inline-flex items-center gap-1"
              >
                <i class="fa-solid fa-mobile-screen text-[9px]"></i>
                Data Cache HP
              </span>
            </div>
            <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
              <i class="fa-regular fa-calendar-check text-slate-400"></i>
              {{ kelas ? 'Kelas ' + kelas.nama + ' (' + kelas.tahun_ajaran + ') · Wali: ' + (kelas.wali?.nama || '-') : 'Pilih kelas' }}
            </p>
          </div>

          <!-- Filter Controls -->
          <div class="flex flex-wrap items-center gap-2.5">
            <!-- Kelas Selector (if Admin has multiple classes) -->
            <div v-if="kelasList.length > 1" class="w-full sm:w-auto">
              <select
                v-model="selectedKelasId"
                @change="onFilterChange"
                class="w-full sm:w-auto bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-teal-500"
              >
                <option v-for="k in kelasList" :key="k.id" :value="k.id">
                  Kelas {{ k.nama }} ({{ k.tahun_ajaran }})
                </option>
              </select>
            </div>

            <!-- Date Picker -->
            <div class="w-full sm:w-auto">
              <input
                type="date"
                v-model="selectedTanggal"
                @change="onFilterChange"
                class="w-full sm:w-auto bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-teal-500"
              />
            </div>
          </div>
        </div>

        <!-- Quick Summary & "Semua Hadir" Toolbar -->
        <div v-if="kelas && siswaList.length > 0" class="mt-5 pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <!-- Summary Counter Badges -->
          <div class="flex items-center gap-2 sm:gap-3 text-xs font-semibold flex-wrap">
            <span class="text-slate-500">Total: {{ siswaList.length }} Siswa</span>
            <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
              H: {{ countStatus('H') }}
            </span>
            <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 border border-amber-200">
              I: {{ countStatus('I') }}
            </span>
            <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-200">
              S: {{ countStatus('S') }}
            </span>
            <span class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 border border-rose-200">
              A: {{ countStatus('A') }}
            </span>
          </div>

          <!-- Actions: "Antrean WhatsApp" & "Semua Hadir" Buttons -->
          <div class="flex items-center gap-2 flex-wrap">
            <button
              v-if="antreanWa.length > 0"
              type="button"
              @click="showWaQueueModal = true"
              class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm cursor-pointer"
            >
              <i class="fa-brands fa-whatsapp text-sm"></i>
              <span>Antrean WA ({{ waStats.belum_dihubungi }} belum)</span>
            </button>

            <!-- "Semua Hadir" Action Button (G1) -->
            <button
              type="button"
              @click="setSemuaHadir"
              class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer border border-slate-300 active:scale-95"
            >
              <i class="fa-solid fa-check-double text-emerald-600"></i>
              <span>Tandai Semua Hadir</span>
            </button>
          </div>
        </div>
      </div>

      <!-- WhatsApp Parent Contact Tracker Banner (Target 90%) -->
      <div
        v-if="kelas && sudahDisimpan && antreanWa.length > 0"
        class="bg-emerald-50/80 border border-emerald-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs shadow-xs"
      >
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
            <i class="fa-brands fa-whatsapp text-2xl"></i>
          </div>
          <div>
            <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
              <span v-if="waStats.belum_dihubungi > 0" class="text-rose-700">
                {{ waStats.belum_dihubungi }} siswa belum dihubungi hari ini
              </span>
              <span v-else class="text-emerald-800">
                Semua siswa tidak hadir sudah dihubungi!
              </span>
              <span
                :class="[
                  'px-2 py-0.5 rounded-full text-[10px] font-bold',
                  waStats.persentase >= 90 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'
                ]"
              >
                Capaian: {{ waStats.persentase }}% (Target: &ge;90%)
              </span>
            </div>
            <p class="text-slate-600 mt-0.5">
              Hubungi orang tua untuk memastikan alasan ketidakhadiran siswa.
            </p>
          </div>
        </div>

        <button
          type="button"
          @click="showWaQueueModal = true"
          class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-sm shrink-0 cursor-pointer"
        >
          <span>Buka Antrean Orang Tua ({{ antreanWa.length }})</span>
        </button>
      </div>

      <!-- Warning if teacher has no class assigned -->
      <div v-if="!kelas" class="p-6 bg-white rounded-2xl border border-slate-200 text-center">
        <p class="text-slate-600 text-sm font-medium">
          {{ error_message || 'Tidak ada kelas yang dapat diakses.' }}
        </p>
      </div>

      <!-- Warning if no students in class -->
      <div v-else-if="siswaList.length === 0" class="p-8 bg-white rounded-2xl border border-slate-200 text-center">
        <p class="text-slate-500 text-sm">Belum ada siswa aktif yang terdaftar di kelas ini.</p>
        <Link href="/siswa" class="mt-3 inline-block px-4 py-2 bg-teal-600 text-white rounded-xl text-xs font-semibold">
          Tambah Siswa Baru
        </Link>
      </div>

      <!-- Student Attendance Roster List -->
      <div v-else class="space-y-4">
        <!-- Section Title Header -->
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs flex items-center justify-between">
          <h2 class="font-bold text-slate-900 text-sm sm:text-base flex items-center gap-2">
            <i class="fa-solid fa-users-viewfinder text-teal-600"></i>
            <span>Daftar Presensi Siswa — Kelas {{ kelas.nama }}</span>
          </h2>
          <span class="text-xs text-slate-500 font-semibold px-2.5 py-1 rounded-full bg-slate-100 inline-flex items-center gap-1.5">
            <i class="fa-solid fa-user-check text-teal-600 text-[10px]"></i>
            {{ siswaList.length }} Siswa Siap Diabsen
          </span>
        </div>

        <div
          v-for="(s, index) in siswaList"
          :key="s.id"
          class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs transition hover:border-slate-300"
        >
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            
            <!-- Student Info -->
            <div class="flex items-start gap-3 min-w-[200px]">
              <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">
                {{ index + 1 }}
              </span>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="font-bold text-slate-900 text-sm sm:text-base leading-tight">
                    {{ s.nama }}
                  </h3>
                  <span
                    :class="[
                      'text-[10px] font-bold px-1.5 py-0.5 rounded-md',
                      s.jenis_kelamin === 'L' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700'
                    ]"
                  >
                    {{ s.jenis_kelamin }}
                  </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5 font-mono">NIS: {{ s.nis }}</p>
                <p class="text-[11px] text-slate-600 mt-0.5">
                  Ortu: {{ s.nama_ortu }} · <span class="font-mono text-slate-500">{{ s.no_wa }}</span>
                </p>
              </div>
            </div>

            <!-- Big Mobile-Friendly Status Buttons (H, I, S, A) with 360px screen optimization -->
            <div class="grid grid-cols-4 sm:flex items-center gap-1.5 sm:gap-2">
              <!-- Hadir (H) -->
              <button
                type="button"
                @click="s.status = 'H'"
                :class="[
                  'py-3 px-3 sm:px-4 rounded-xl font-black text-sm sm:text-base transition cursor-pointer flex flex-col sm:flex-row items-center justify-center gap-1 active:scale-95',
                  s.status === 'H'
                    ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30 ring-2 ring-emerald-600 ring-offset-1'
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 border border-slate-200'
                ]"
              >
                <span>H</span>
                <span class="text-[10px] font-medium sm:font-semibold">Hadir</span>
              </button>

              <!-- Izin (I) -->
              <button
                type="button"
                @click="s.status = 'I'"
                :class="[
                  'py-3 px-3 sm:px-4 rounded-xl font-black text-sm sm:text-base transition cursor-pointer flex flex-col sm:flex-row items-center justify-center gap-1 active:scale-95',
                  s.status === 'I'
                    ? 'bg-amber-500 text-white shadow-md shadow-amber-500/30 ring-2 ring-amber-500 ring-offset-1'
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 border border-slate-200'
                ]"
              >
                <span>I</span>
                <span class="text-[10px] font-medium sm:font-semibold">Izin</span>
              </button>

              <!-- Sakit (S) -->
              <button
                type="button"
                @click="s.status = 'S'"
                :class="[
                  'py-3 px-3 sm:px-4 rounded-xl font-black text-sm sm:text-base transition cursor-pointer flex flex-col sm:flex-row items-center justify-center gap-1 active:scale-95',
                  s.status === 'S'
                    ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 ring-2 ring-blue-600 ring-offset-1'
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 border border-slate-200'
                ]"
              >
                <span>S</span>
                <span class="text-[10px] font-medium sm:font-semibold">Sakit</span>
              </button>

              <!-- Alpa (A) -->
              <button
                type="button"
                @click="s.status = 'A'"
                :class="[
                  'py-3 px-3 sm:px-4 rounded-xl font-black text-sm sm:text-base transition cursor-pointer flex flex-col sm:flex-row items-center justify-center gap-1 active:scale-95',
                  s.status === 'A'
                    ? 'bg-rose-600 text-white shadow-md shadow-rose-600/30 ring-2 ring-rose-600 ring-offset-1'
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 border border-slate-200'
                ]"
              >
                <span>A</span>
                <span class="text-[10px] font-medium sm:font-semibold">Alpa</span>
              </button>
            </div>

            <!-- Notes & WhatsApp Button -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 lg:w-72">
              <input
                type="text"
                v-model="s.catatan"
                placeholder="Catatan (misal: flu, acara)"
                class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-teal-500"
              />

              <!-- WhatsApp Action Button & Tracking Status (G5) -->
              <template v-if="s.status !== 'H'">
                <div class="flex items-center gap-1.5 shrink-0">
                  <a
                    :href="getWhatsAppLink(s)"
                    target="_blank"
                    @click="onDirectWaClick(s)"
                    class="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold flex items-center justify-center gap-1.5 shadow-sm transition active:scale-95"
                    :title="'Kirim pesan WhatsApp ke ' + s.nama_ortu"
                  >
                    <i class="fa-brands fa-whatsapp text-sm"></i>
                    <span>Kirim WA</span>
                  </a>

                  <!-- Status Tracking Pill -->
                  <span
                    v-if="s.wa_status === 'sudah_dikirim'"
                    class="px-2 py-1 rounded-lg bg-emerald-100 text-emerald-800 text-[10px] font-bold border border-emerald-300 flex items-center gap-1"
                    :title="'Terkirim pukul ' + (s.wa_dikirim_pada || '')"
                  >
                    <i class="fa-solid fa-check text-emerald-700"></i>
                    <span>{{ s.wa_dikirim_pada || 'Terkirim' }}</span>
                  </span>
                  <span
                    v-else
                    class="px-2 py-1 rounded-lg bg-slate-100 text-slate-500 text-[10px] font-semibold border border-slate-200"
                  >
                    Belum
                  </span>
                </div>
              </template>
            </div>

          </div>
        </div>
      </div>

      <!-- Sticky Save Bar at Bottom for Phones & Desktops -->
      <div v-if="kelas && siswaList.length > 0" class="fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur-md border-t border-slate-200 py-3 px-4 z-20 shadow-lg">
        <div class="max-w-5xl mx-auto flex items-center justify-between gap-4">
          <div class="hidden sm:block text-xs text-slate-600">
            <span v-if="!isOnline" class="text-orange-700 font-semibold flex items-center gap-1.5">
              <i class="fa-solid fa-wifi-slash text-orange-600"></i>
              Mode Offline: Data akan disimpan ke HP Anda dan dikirim otomatis saat tersambung internet.
            </span>
            <span v-else class="flex items-center gap-1.5">
              <i class="fa-solid fa-circle-check text-emerald-600"></i>
              Pastikan status kehadiran setiap siswa sudah sesuai sebelum menekan simpan.
            </span>
          </div>
          <div class="flex items-center gap-2 w-full sm:w-auto">
            <button
              type="button"
              @click="simpanAbsensi"
              :disabled="saving"
              class="w-full sm:w-auto px-6 py-3.5 bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white font-bold rounded-xl text-sm transition shadow-md shadow-teal-600/30 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <i v-if="saving" class="fa-solid fa-circle-notch fa-spin"></i>
              <i v-else class="fa-solid fa-floppy-disk"></i>
              <span>{{ saveButtonLabel }}</span>
            </button>
          </div>
        </div>
      </div>

    </div>

    <!-- Conflict Resolution Modal -->
    <ConflictResolverModal
      :isOpen="showConflictModal"
      :conflicts="serverConflicts"
      @resolve="handleResolveConflict"
      @close="showConflictModal = false"
    />

    <!-- WhatsApp Queue Modal (Antrean Orang Tua & Pelacak WA) -->
    <WhatsAppQueueModal
      :isOpen="showWaQueueModal"
      :antrean="antreanWa"
      :templateWa="templateWa"
      :defaultTemplateWa="defaultTemplateWa"
      @close="showWaQueueModal = false"
      @updated="refreshData"
    />
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConflictResolverModal from '@/Components/ConflictResolverModal.vue';
import WhatsAppQueueModal from '@/Components/WhatsAppQueueModal.vue';
import {
  cacheRosterData,
  getCachedRosterData,
  enqueueOfflineAttendance,
  getPendingQueue,
  dequeueOfflineAttendance,
  countPendingQueue
} from '@/offline/db.js';

const props = defineProps({
  kelasList: {
    type: Array,
    default: () => [],
  },
  kelas: {
    type: Object,
    default: null,
  },
  tanggal: {
    type: String,
    required: true,
  },
  siswa: {
    type: Array,
    default: () => [],
  },
  sudahDisimpan: {
    type: Boolean,
    default: false,
  },
  error_message: {
    type: String,
    default: null,
  },
  antreanWa: {
    type: Array,
    default: () => [],
  },
  templateWa: {
    type: String,
    default: '',
  },
  defaultTemplateWa: {
    type: String,
    default: '',
  },
  waStats: {
    type: Object,
    default: () => ({ belum_dihubungi: 0, sudah_dikirim: 0, persentase: 100 }),
  },
  peringatanAbsenKosong: {
    type: Object,
    default: null,
  },
});

const showWaQueueModal = ref(false);

const selectedKelasId = ref(props.kelas?.id || (props.kelasList[0]?.id ?? null));
const selectedTanggal = ref(props.tanggal);
const saving = ref(false);

// Offline & Sync States
const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);
const pendingCount = ref(0);
const syncStatus = ref('idle'); // 'idle' | 'syncing' | 'synced' | 'error'
const lastOfflineSavedTime = ref(null);
const isLoadedFromCache = ref(false);

// Conflict handling state
const showConflictModal = ref(false);
const serverConflicts = ref([]);
const activeConflictPayload = ref(null);

// Reactive students roster
const siswaList = reactive(
  props.siswa.map(s => ({
    id: s.id,
    nis: s.nis,
    nama: s.nama,
    jenis_kelamin: s.jenis_kelamin,
    nama_ortu: s.nama_ortu,
    no_wa: s.no_wa,
    status: s.status || 'H',
    catatan: s.catatan || '',
  }))
);

// Style Banner Komputasi
const bannerStatusClass = computed(() => {
  if (!isOnline.value) {
    return 'bg-orange-50 border-orange-200 text-orange-950';
  }
  if (pendingCount.value > 0) {
    return 'bg-amber-50 border-amber-200 text-amber-950';
  }
  if (syncStatus.value === 'synced') {
    return 'bg-emerald-50 border-emerald-200 text-emerald-950';
  }
  return 'bg-slate-50 border-slate-200 text-slate-800';
});

const saveButtonLabel = computed(() => {
  if (saving.value) return 'Menyimpan...';
  if (!isOnline.value) return 'Simpan ke HP (Offline)';
  if (props.sudahDisimpan) return 'Simpan Pembaruan Absen';
  return 'Simpan Absensi Harian';
});

// Update list bila props berubah (dari server)
watch(() => props.siswa, (newSiswa) => {
  siswaList.splice(
    0,
    siswaList.length,
    ...newSiswa.map(s => ({
      id: s.id,
      nis: s.nis,
      nama: s.nama,
      jenis_kelamin: s.jenis_kelamin,
      nama_ortu: s.nama_ortu,
      no_wa: s.no_wa,
      status: s.status || 'H',
      catatan: s.catatan || '',
    }))
  );

  // Simpan data terkini ke cache IndexedDB bila online
  if (props.kelas && isOnline.value) {
    cacheRosterData(props.kelas.id, props.tanggal, {
      kelas: props.kelas,
      siswa: newSiswa,
      sudahDisimpan: props.sudahDisimpan
    });
  }
}, { immediate: true });

const countStatus = (status) => {
  return siswaList.filter(s => s.status === status).length;
};

const setSemuaHadir = () => {
  siswaList.forEach(s => {
    s.status = 'H';
  });
};

const onFilterChange = async () => {
  if (!isOnline.value) {
    // Mode Offline: ambil dari cache IndexedDB
    const cached = await getCachedRosterData(selectedKelasId.value, selectedTanggal.value);
    if (cached) {
      isLoadedFromCache.value = true;
      siswaList.splice(
        0,
        siswaList.length,
        ...cached.siswa.map(s => ({
          id: s.id,
          nis: s.nis,
          nama: s.nama,
          jenis_kelamin: s.jenis_kelamin,
          nama_ortu: s.nama_ortu,
          no_wa: s.no_wa,
          status: s.status || 'H',
          catatan: s.catatan || '',
        }))
      );
    } else {
      alert('Data kelas dan tanggal ini belum tersimpan di cache offline HP Anda. Silakan sambungkan internet terlebih dahulu.');
    }
    return;
  }

  // Mode Online normal
  router.get('/absensi', {
    kelas_id: selectedKelasId.value,
    tanggal: selectedTanggal.value,
  }, {
    preserveState: false,
    preserveScroll: true,
  });
};

const getWhatsAppLink = (s) => {
  const statusTeks = {
    'I': 'Izin',
    'S': 'Sakit',
    'A': 'Alpa (Tanpa Keterangan)',
  }[s.status] || 'Hadir';

  const options = { day: 'numeric', month: 'long', year: 'numeric' };
  const d = new Date(selectedTanggal.value);
  const tanggalIndo = d.toLocaleDateString('id-ID', options);

  const namaGuru = props.kelas?.wali?.nama || 'Wali Kelas';
  const catatanTeks = s.catatan ? ` (${s.catatan})` : '';

  const pesan = `Assalamualaikum Bapak/Ibu ${s.nama_ortu}. Kami informasikan bahwa ${s.nama} kelas ${props.kelas?.nama} pada ${tanggalIndo} tercatat ${statusTeks}${catatanTeks}. Mohon konfirmasinya. Terima kasih. Wali kelas ${namaGuru}`;

  let noWa = (s.no_wa || '').replace(/[^0-9]/g, '');
  if (noWa.startsWith('0')) {
    noWa = '62' + noWa.substring(1);
  }

  return `https://wa.me/${noWa}?text=${encodeURIComponent(pesan)}`;
};

/**
 * Simpan Absensi (otomatis memilih offline queue jika tidak ada koneksi)
 */
const simpanAbsensi = async () => {
  if (!props.kelas) return;

  const payload = {
    kelas_id: props.kelas.id,
    tanggal: selectedTanggal.value,
    absensi: siswaList.map(s => ({
      siswa_id: s.id,
      status: s.status,
      catatan: s.catatan,
      dicatat_offline_pada: !isOnline.value ? new Date().toISOString() : null
    })),
  };

  // Jika sedang offline: simpan langsung ke antrean IndexedDB
  if (!isOnline.value) {
    saving.value = true;
    try {
      await enqueueOfflineAttendance(payload);
      lastOfflineSavedTime.value = new Date().toLocaleTimeString('id-ID');
      pendingCount.value = await countPendingQueue();
      alert('Data absensi berhasil disimpan di HP (offline). Akan otomatis disinkronkan ke server saat tersambung internet.');
    } catch (err) {
      alert('Gagal menyimpan absensi ke HP: ' + err.message);
    } finally {
      saving.value = false;
    }
    return;
  }

  // Jika online: kirim via Inertia post atau sync
  saving.value = true;
  router.post('/absensi', payload, {
    preserveScroll: true,
    onError: async () => {
      // Jika terjadi kegagalan jaringan saat mengirim
      if (!navigator.onLine) {
        await enqueueOfflineAttendance(payload);
        isOnline.value = false;
        lastOfflineSavedTime.value = new Date().toLocaleTimeString('id-ID');
        pendingCount.value = await countPendingQueue();
        alert('Jaringan terputus. Data absensi dialihkan dan disimpan aman di HP.');
      }
    },
    onFinish: async () => {
      saving.value = false;
      syncStatus.value = 'synced';
      pendingCount.value = await countPendingQueue();

      // "Setelah absen disimpan, tampilkan Antrean orang tua berisi siswa Izin/Sakit/Alpa"
      const hasAbsentStudents = siswaList.some(s => s.status !== 'H');
      if (hasAbsentStudents) {
        showWaQueueModal.value = true;
      }
    },
  });
};

const refreshData = () => {
  router.get('/absensi', {
    kelas_id: selectedKelasId.value,
    tanggal: selectedTanggal.value,
  }, {
    preserveState: false,
    preserveScroll: true,
  });
};

const onDirectWaClick = (s) => {
  if (s.absensi_id) {
    const handleReturnFocus = async () => {
      window.removeEventListener('focus', handleReturnFocus);
      try {
        const res = await axios.post(`/absensi/${s.absensi_id}/tandai-wa-terkirim`);
        if (res.data.success) {
          s.wa_status = 'sudah_dikirim';
          s.wa_dikirim_pada = res.data.wa_dikirim_pada;
        }
      } catch (err) {
        // silent
      }
    };
    window.addEventListener('focus', handleReturnFocus, { once: true });
  }
};

/**
 * Sinkronisasi antrean data offline ke server
 */
const syncAllPending = async () => {
  if (!isOnline.value || syncStatus.value === 'syncing') return;

  const queue = await getPendingQueue();
  if (queue.length === 0) {
    pendingCount.value = 0;
    return;
  }

  syncStatus.value = 'syncing';

  for (const item of queue) {
    try {
      const response = await axios.post('/absensi/sync', {
        kelas_id: item.kelas_id,
        tanggal: item.tanggal,
        absensi: item.absensi,
        dicatat_offline_pada: item.dicatat_offline_pada
      });

      if (response.data?.success) {
        await dequeueOfflineAttendance(item.id);
      }
    } catch (err) {
      // Konflik terdeteksi (409 Conflict)
      if (err.response && err.response.status === 409 && err.response.data?.conflict) {
        serverConflicts.value = err.response.data.conflicts || [];
        activeConflictPayload.value = item;
        showConflictModal.value = true;
        syncStatus.value = 'idle';
        return; // Hentikan dan tunggu konfirmasi guru
      } else {
        console.warn('Gagal menyinkronkan item antrean:', err);
      }
    }
  }

  pendingCount.value = await countPendingQueue();
  if (pendingCount.value === 0) {
    syncStatus.value = 'synced';
    lastOfflineSavedTime.value = null;
  } else {
    syncStatus.value = 'idle';
  }
};

const handleResolveConflict = async (resolutions) => {
  showConflictModal.value = false;
  if (!activeConflictPayload.value) return;

  const item = activeConflictPayload.value;
  syncStatus.value = 'syncing';

  try {
    const response = await axios.post('/absensi/sync', {
      kelas_id: item.kelas_id,
      tanggal: item.tanggal,
      absensi: item.absensi,
      dicatat_offline_pada: item.dicatat_offline_pada,
      resolve_conflicts: resolutions
    });

    if (response.data?.success) {
      await dequeueOfflineAttendance(item.id);
      activeConflictPayload.value = null;
      serverConflicts.value = [];
      alert('Konflik berhasil diselesaikan dan absensi telah tersinkron.');
      await syncAllPending();
    }
  } catch (err) {
    alert('Gagal menerapkan resolusi konflik: ' + (err.response?.data?.message || err.message));
    syncStatus.value = 'idle';
  }
};

const triggerManualSync = () => {
  syncAllPending();
};

const handleOnline = async () => {
  isOnline.value = true;
  await syncAllPending();
};

const handleOffline = () => {
  isOnline.value = false;
};

onMounted(async () => {
  if (typeof window !== 'undefined') {
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
  }

  // Hitung antrean dan coba sinkronkan bila ada
  pendingCount.value = await countPendingQueue();
  if (isOnline.value && pendingCount.value > 0) {
    await syncAllPending();
  }
});

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('online', handleOnline);
    window.removeEventListener('offline', handleOffline);
  }
});
</script>
