<template>
  <AppLayout>
    <div class="space-y-5 max-w-5xl mx-auto">
      
      <!-- Top Header -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
            Kelola Akun Guru
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            Tambah dan kelola akun login untuk guru pengampu kelas.
          </p>
        </div>
        <div>
          <button
            type="button"
            @click="openAddModal"
            class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer"
          >
            <i class="fa-solid fa-user-plus text-xs"></i>
            <span>Tambah Akun Guru</span>
          </button>
        </div>
      </div>

      <!-- Teachers Table -->
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
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
              <tr v-for="(g, index) in guru" :key="g.id" :class="['hover:bg-slate-50 transition', !g.aktif ? 'bg-slate-50 opacity-60' : '']">
                <td class="py-3.5 px-4 text-center text-slate-500 font-medium">{{ index + 1 }}</td>
                <td class="py-3.5 px-4 font-bold text-slate-900 text-sm">{{ g.nama }}</td>
                <td class="py-3.5 px-4 font-mono text-slate-600 font-medium">@{{ g.username }}</td>
                <td class="py-3.5 px-4">
                  <span v-if="g.kelas_nama !== '-'" class="px-2 py-0.5 rounded-md bg-teal-50 text-teal-700 font-semibold border border-teal-200">
                    Kelas {{ g.kelas_nama }}
                  </span>
                  <span v-else class="text-slate-400 italic">Belum mengampu kelas</span>
                </td>
                <td class="py-3.5 px-3 text-center">
                  <span
                    :class="[
                      'px-2 py-0.5 rounded-full text-[10px] font-semibold',
                      g.aktif ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'
                    ]"
                  >
                    {{ g.aktif ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <button
                      type="button"
                      @click="openEditModal(g)"
                      class="px-2.5 py-1 text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs font-semibold transition"
                    >
                      Ubah
                    </button>
                    <button
                      type="button"
                      @click="toggleAktif(g)"
                      :class="[
                        'px-2.5 py-1 rounded-lg text-xs font-semibold transition',
                        g.aktif ? 'text-rose-600 bg-rose-50 hover:bg-rose-100' : 'text-emerald-600 bg-emerald-50 hover:bg-emerald-100'
                      ]"
                    >
                      {{ g.aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Tambah / Edit Guru -->
      <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-md w-full p-6 animate-in fade-in zoom-in-95">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-bold text-base text-slate-900">
              {{ isEditing ? 'Ubah Data Guru' : 'Tambah Akun Guru Baru' }}
            </h3>
            <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer">
              <i class="fa-solid fa-xmark text-base"></i>
            </button>
          </div>

          <form @submit.prevent="submitForm" class="mt-4 space-y-3">
            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700">Nama Lengkap & Gelar</label>
              <input
                type="text"
                v-model="form.nama"
                required
                placeholder="Contoh: Siti Rahayu, S.Pd."
                class="mt-1 w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-teal-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700">Username Login</label>
              <input
                type="text"
                v-model="form.username"
                required
                placeholder="Contoh: siti_rahayu atau guru1"
                class="mt-1 w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-teal-500"
              />
              <p v-if="form.errors.username" class="text-[11px] text-rose-600 mt-0.5">{{ form.errors.username }}</p>
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700">
                {{ isEditing ? 'Kata Sandi Baru (Kosongkan jika tidak diubah)' : 'Kata Sandi Awal' }}
              </label>
              <input
                type="password"
                v-model="form.password"
                :required="!isEditing"
                placeholder="Minimal 6 karakter"
                class="mt-1 w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-teal-500"
              />
            </div>

            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
              <button
                type="button"
                @click="modalOpen = false"
                class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition disabled:opacity-50"
              >
                {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Buat Akun Guru') }}
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  guru: {
    type: Array,
    default: () => [],
  },
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
