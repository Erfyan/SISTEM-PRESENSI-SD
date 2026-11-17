<template>
  <AppLayout>
    <div class="space-y-6 max-w-6xl mx-auto">
      
      <!-- Top Header -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            <i class="fa-solid fa-chalkboard-user text-teal-600"></i>
            <span>Kelola Akun Guru</span>
          </h1>
          <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
            <i class="fa-regular fa-id-badge text-slate-400"></i>
            Tambah dan kelola hak akses login untuk guru pengampu kelas SD Negeri 1.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="openAddModal"
            class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer"
          >
            <i class="fa-solid fa-user-plus text-xs"></i>
            <span>Tambah Akun Guru</span>
          </button>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-purple-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-chalkboard-user text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Guru</p>
            <p class="text-2xl font-black text-slate-900 mt-0.5">{{ guru.length }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Akun guru terdaftar</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-teal-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-school text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Guru Wali Kelas</p>
            <p class="text-2xl font-black text-slate-900 mt-0.5">{{ totalWali }}</p>
            <p class="text-[11px] text-teal-700 mt-0.5">Mengampu rombongan belajar</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-emerald-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-user-check text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Status Akun Aktif</p>
            <p class="text-2xl font-black text-slate-900 mt-0.5">{{ totalAktif }} / {{ guru.length }}</p>
            <p class="text-[11px] text-emerald-700 mt-0.5">{{ guru.length - totalAktif === 0 ? 'Seluruh akun guru aktif' : (guru.length - totalAktif) + ' akun dinonaktifkan' }}</p>
          </div>
        </div>
      </div>

      <!-- Teachers Table Card -->
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <h2 class="font-bold text-slate-900 text-sm sm:text-base flex items-center gap-2">
            <i class="fa-solid fa-users-gear text-teal-600"></i>
            <span>Daftar Akun Guru Pengampu</span>
          </h2>
          <span class="text-xs text-slate-500 font-semibold px-2.5 py-1 rounded-full bg-slate-100 inline-flex items-center gap-1.5 self-start sm:self-auto">
            <i class="fa-solid fa-user-graduate text-teal-600 text-[10px]"></i>
            {{ guru.length }} Guru Terdata
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-semibold">
              <tr>
                <th class="py-3 px-4 w-12 text-center">No</th>
                <th class="py-3 px-4">Nama Lengkap</th>
                <th class="py-3 px-4">Username</th>
                <th class="py-3 px-4">Kelas Diampu</th>
                <th class="py-3 px-3 text-center">Status</th>
                <th class="py-3 px-4 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(g, index) in guru" :key="g.id" :class="['hover:bg-slate-50 transition', !g.aktif ? 'bg-slate-50/70 opacity-70' : '']">
                <td class="py-3.5 px-4 text-center text-slate-400 font-medium">{{ index + 1 }}</td>
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-teal-100 text-teal-800 font-bold flex items-center justify-center text-xs border border-teal-200 shrink-0">
                      {{ g.nama.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <p class="font-bold text-slate-900 text-sm">{{ g.nama }}</p>
                      <p class="text-[10px] text-slate-400">Guru Kelas SD</p>
                    </div>
                  </div>
                </td>
                <td class="py-3.5 px-4">
                  <span class="font-mono text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md text-[11px] font-semibold">
                    @{{ g.username }}
                  </span>
                </td>
                <td class="py-3.5 px-4">
                  <span v-if="g.kelas_nama && g.kelas_nama !== '-'" class="px-2.5 py-1 rounded-full bg-teal-50 text-teal-700 font-bold border border-teal-200 inline-flex items-center gap-1.5 text-[11px]">
                    <i class="fa-solid fa-school text-[10px]"></i>
                    Kelas {{ g.kelas_nama }}
                  </span>
                  <span v-else class="text-slate-400 italic text-[11px] flex items-center gap-1">
                    <i class="fa-regular fa-circle-question text-[10px]"></i>
                    Belum ditugaskan
                  </span>
                </td>
                <td class="py-3.5 px-3 text-center">
                  <span
                    :class="[
                      'px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1',
                      g.aktif ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'
                    ]"
                  >
                    <i :class="g.aktif ? 'fa-solid fa-circle-check text-[9px]' : 'fa-solid fa-circle-xmark text-[9px]'"></i>
                    <span>{{ g.aktif ? 'Aktif' : 'Nonaktif' }}</span>
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <button
                      type="button"
                      @click="openEditModal(g)"
                      class="px-2.5 py-1.5 text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 cursor-pointer"
                    >
                      <i class="fa-solid fa-pen-to-square text-[11px] text-slate-500"></i>
                      <span>Ubah</span>
                    </button>
                    <button
                      type="button"
                      @click="toggleAktif(g)"
                      :class="[
                        'px-2.5 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 cursor-pointer border',
                        g.aktif ? 'text-rose-700 bg-rose-50 hover:bg-rose-100 border-rose-200' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border-emerald-200'
                      ]"
                    >
                      <i :class="g.aktif ? 'fa-solid fa-ban text-[11px]' : 'fa-solid fa-check text-[11px]'"></i>
                      <span>{{ g.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</span>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="guru.length === 0">
                <td colspan="6" class="p-10 text-center text-slate-400 text-xs">
                  <i class="fa-solid fa-chalkboard-user text-3xl text-slate-300 mb-2 block"></i>
                  Belum ada data guru. Silakan klik tombol "Tambah Akun Guru".
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Tambah / Edit Guru -->
      <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-150">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-bold text-base text-slate-900 flex items-center gap-2">
              <i class="fa-solid fa-chalkboard-user text-teal-600"></i>
              <span>{{ isEditing ? 'Ubah Data Akun Guru' : 'Tambah Akun Guru Baru' }}</span>
            </h3>
            <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer">
              <i class="fa-solid fa-xmark text-base"></i>
            </button>
          </div>

          <form @submit.prevent="submitForm" class="mt-4 space-y-4">
            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700 flex items-center gap-1.5">
                <i class="fa-solid fa-user text-slate-400"></i>
                <span>Nama Lengkap & Gelar</span>
              </label>
              <input
                type="text"
                v-model="form.nama"
                required
                placeholder="Contoh: Siti Rahayu, S.Pd."
                class="mt-1.5 w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700 flex items-center gap-1.5">
                <i class="fa-solid fa-at text-slate-400"></i>
                <span>Username Login</span>
              </label>
              <input
                type="text"
                v-model="form.username"
                required
                placeholder="Contoh: siti_rahayu atau guru1"
                class="mt-1.5 w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
              />
              <p v-if="form.errors.username" class="text-[11px] text-rose-600 mt-1 flex items-center gap-1">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>{{ form.errors.username }}</span>
              </p>
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700 flex items-center gap-1.5">
                <i class="fa-solid fa-lock text-slate-400"></i>
                <span>{{ isEditing ? 'Kata Sandi Baru (Kosongkan jika tidak diubah)' : 'Kata Sandi Awal' }}</span>
              </label>
              <input
                type="password"
                v-model="form.password"
                :required="!isEditing"
                placeholder="Minimal 6 karakter"
                class="mt-1.5 w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
              />
            </div>

            <div class="pt-4 flex justify-end gap-2 border-t border-slate-100">
              <button
                type="button"
                @click="modalOpen = false"
                class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition cursor-pointer"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white text-xs font-bold transition disabled:opacity-50 shadow-xs flex items-center gap-1.5 cursor-pointer"
              >
                <i v-if="form.processing" class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                <i v-else class="fa-solid fa-check text-xs"></i>
                <span>{{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Buat Akun Guru') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  guru: {
    type: Array,
    default: () => [],
  },
});

const totalWali = computed(() => {
  return props.guru.filter((g) => g.kelas_nama && g.kelas_nama !== '-').length;
});

const totalAktif = computed(() => {
  return props.guru.filter((g) => !!g.aktif).length;
});

const modalOpen = ref(false);
const isEditing = ref(false);
const currentGuruId = ref(null);

const form = useForm({
  nama: '',
  username: '',
  password: '',
});

const openAddModal = () => {
  isEditing.value = false;
  currentGuruId.value = null;
  form.reset();
  modalOpen.value = true;
};

const openEditModal = (g) => {
  isEditing.value = true;
  currentGuruId.value = g.id;
  form.nama = g.nama;
  form.username = g.username;
  form.password = '';
  modalOpen.value = true;
};

const submitForm = () => {
  if (isEditing.value) {
    form.put(`/admin/guru/${currentGuruId.value}`, {
      onSuccess: () => {
        modalOpen.value = false;
      },
    });
  } else {
    form.post('/admin/guru', {
      onSuccess: () => {
        modalOpen.value = false;
        form.reset();
      },
    });
  }
};

const toggleAktif = (g) => {
  if (confirm(`Apakah Anda yakin ingin mengubah status keaktifan akun guru ${g.nama}?`)) {
    router.patch(`/admin/guru/${g.id}/toggle-aktif`, {}, {
      preserveScroll: true,
    });
  }
};
</script>
