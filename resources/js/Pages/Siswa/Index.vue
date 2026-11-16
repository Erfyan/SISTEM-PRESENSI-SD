<template>
  <AppLayout>
    <div class="space-y-5 max-w-6xl mx-auto">
      
      <!-- Top Action Bar -->
      <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
              Data Siswa & Wali Murid
            </h1>
            <p class="text-xs text-slate-500 mt-1">
              {{ kelas ? 'Kelas ' + kelas.nama + ' (' + kelas.tahun_ajaran + ')' : 'Pilih kelas' }} · Total {{ siswa.length }} Siswa
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2.5">
            <!-- Kelas Selector (if Admin) -->
            <div v-if="kelasList.length > 1">
              <select
                v-model="selectedKelasId"
                @change="refreshData"
                class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-teal-500"
              >
                <option v-for="k in kelasList" :key="k.id" :value="k.id">
                  Kelas {{ k.nama }} ({{ k.tahun_ajaran }})
                </option>
              </select>
            </div>

            <!-- Unduh Template Button -->
            <a
              href="/siswa/import/template"
              target="_blank"
              class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 border border-slate-300 shadow-xs cursor-pointer"
              title="Unduh format template Excel untuk data siswa"
            >
              <i class="fa-solid fa-file-arrow-down text-emerald-600 text-sm"></i>
              <span>Unduh Template</span>
            </a>

            <!-- Impor dari Excel Button -->
            <button
              v-if="kelas"
              type="button"
              @click="importModalOpen = true"
              class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer"
            >
              <i class="fa-solid fa-file-excel text-sm"></i>
              <span>Impor dari Excel</span>
            </button>

            <!-- Add Student Button (G6) -->
            <button
              v-if="kelas"
              type="button"
              @click="openAddModal"
              class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer"
            >
              <i class="fa-solid fa-user-plus text-sm"></i>
              <span>Tambah Siswa</span>
            </button>
          </div>
        </div>

        <!-- Search Bar -->
        <div class="mt-4 pt-3 border-t border-slate-100">
          <div class="relative sm:max-w-md">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Cari siswa berdasarkan nama, NIS, atau nama orang tua..."
              class="w-full text-xs pl-9 pr-3.5 py-2 rounded-xl bg-slate-50 border border-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-teal-500"
            />
          </div>
        </div>
      </div>

      <!-- Students Table / Cards -->
      <div v-if="filteredSiswa.length > 0" class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-semibold">
              <tr>
                <th class="py-3 px-4 w-12 text-center">No</th>
                <th class="py-3 px-4 w-24">NIS</th>
                <th class="py-3 px-4">Nama Siswa</th>
                <th class="py-3 px-3 w-12 text-center">L/P</th>
                <th class="py-3 px-4">Nama Orang Tua</th>
                <th class="py-3 px-4">WhatsApp Ortu</th>
                <th class="py-3 px-3 text-center">Akun Ortu</th>
                <th class="py-3 px-3 text-center">Status</th>
                <th class="py-3 px-4 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="(s, index) in filteredSiswa"
                :key="s.id"
                :class="['hover:bg-slate-50 transition', !s.aktif ? 'bg-slate-50/70 opacity-60' : '']"
              >
                <td class="py-3.5 px-4 text-center font-medium text-slate-500">{{ index + 1 }}</td>
                <td class="py-3.5 px-4 font-mono font-medium text-slate-700">{{ s.nis }}</td>
                <td class="py-3.5 px-4 font-bold text-slate-900">{{ s.nama }}</td>
                <td class="py-3.5 px-3 text-center">
                  <span
                    :class="[
                      'px-1.5 py-0.5 rounded text-[10px] font-bold',
                      s.jenis_kelamin === 'L' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700'
                    ]"
                  >
                    {{ s.jenis_kelamin }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-slate-800">{{ s.nama_ortu }}</td>
                <td class="py-3.5 px-4 font-mono text-slate-700">
                  <a
                    :href="`https://wa.me/${s.no_wa}`"
                    target="_blank"
                    class="text-teal-700 hover:underline flex items-center gap-1.5"
                  >
                    <i class="fa-brands fa-whatsapp text-emerald-600 text-sm"></i>
                    <span>{{ s.no_wa }}</span>
                  </a>
                </td>
                <td class="py-3.5 px-3 text-center">
                  <span
                    v-if="s.akun_ortu"
                    class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300"
                    :title="'Username: ' + s.akun_ortu.username"
                  >
                    Tersedia (@{{ s.akun_ortu.username }})
                  </span>
                  <span
                    v-else
                    class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500 border border-slate-200"
                  >
                    Belum Dibuat
                  </span>
                </td>
                <td class="py-3.5 px-3 text-center">
                  <span
                    :class="[
                      'px-2 py-0.5 rounded-full text-[10px] font-semibold',
                      s.aktif ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'
                    ]"
                  >
                    {{ s.aktif ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <!-- Edit Button -->
                    <button
                      type="button"
                      @click="openEditModal(s)"
                      class="px-2.5 py-1 text-slate-700 hover:text-teal-700 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs font-semibold transition"
                    >
                      Ubah
                    </button>

                    <!-- Reset / Create Parent Account (G7) -->
                    <button
                      type="button"
                      @click="buatAtauResetAkunOrtu(s)"
                      class="px-2.5 py-1 text-amber-800 hover:text-amber-900 bg-amber-50 hover:bg-amber-100 rounded-lg text-xs font-semibold border border-amber-200 transition"
                      :title="s.akun_ortu ? 'Reset PIN Akun Ortu' : 'Buat Akun Ortu Baru'"
                    >
                      {{ s.akun_ortu ? 'Reset PIN' : 'Buat Akun' }}
                    </button>

                    <!-- Toggle Aktif/Nonaktif -->
                    <button
                      type="button"
                      @click="toggleAktif(s)"
                      :class="[
                        'px-2.5 py-1 rounded-lg text-xs font-semibold transition',
                        s.aktif ? 'text-rose-600 bg-rose-50 hover:bg-rose-100' : 'text-emerald-600 bg-emerald-50 hover:bg-emerald-100'
                      ]"
                    >
                      {{ s.aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="p-10 bg-white rounded-2xl border border-slate-200 text-center">
        <p class="text-slate-500 text-sm">
          {{ searchQuery ? 'Tidak ada siswa yang sesuai pencarian.' : (error_message || 'Belum ada siswa di kelas ini.') }}
        </p>
      </div>

      <!-- Modal Tambah / Edit Siswa -->
      <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-md w-full p-6 animate-in fade-in zoom-in-95">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-bold text-base text-slate-900">
              {{ isEditing ? 'Ubah Data Siswa' : 'Tambah Siswa Baru' }}
            </h3>
            <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer">
              <i class="fa-solid fa-xmark text-base"></i>
            </button>
          </div>

          <form @submit.prevent="submitForm" class="mt-4 space-y-3">
            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700">NIS (Nomor Induk Siswa)</label>
              <input
                type="text"
                v-model="form.nis"
                required
                placeholder="Contoh: 1007"
                class="mt-1 w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-teal-500"
              />
              <p v-if="form.errors.nis" class="text-[11px] text-rose-600 mt-0.5">{{ form.errors.nis }}</p>
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700">Nama Lengkap Siswa</label>
              <input
                type="text"
                v-model="form.nama"
                required
                placeholder="Nama lengkap anak"
                class="mt-1 w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-teal-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700">Jenis Kelamin</label>
              <select
                v-model="form.jenis_kelamin"
                required
                class="mt-1 w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-teal-500"
              >
                <option value="L">Laki-laki (L)</option>
                <option value="P">Perempuan (P)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700">Nama Orang Tua / Wali</label>
              <input
                type="text"
                v-model="form.nama_ortu"
                required
                placeholder="Contoh: Bpk. Bambang"
                class="mt-1 w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-teal-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700">Nomor WhatsApp Orang Tua</label>
              <input
                type="text"
                v-model="form.no_wa"
                required
                placeholder="Contoh: 08123456789 atau 628123456789"
                class="mt-1 w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-teal-500"
              />
              <p class="text-[10px] text-slate-500 mt-0.5">Sistem otomatis mengubah ke format internasional (62...).</p>
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
                {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Siswa') }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Impor Siswa Massal dari Excel -->
      <ImportSiswaModal
        v-if="kelas"
        :isOpen="importModalOpen"
        :kelasId="kelas.id"
        :kelasNama="kelas.nama"
        @close="importModalOpen = false"
        @imported="refreshData"
      />

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ImportSiswaModal from '@/Components/ImportSiswaModal.vue';

const props = defineProps({
  kelasList: {
    type: Array,
    default: () => [],
  },
  kelas: {
    type: Object,
    default: null,
  },
  siswa: {
    type: Array,
    default: () => [],
  },
  error_message: {
    type: String,
    default: null,
  },
});

const selectedKelasId = ref(props.kelas?.id || (props.kelasList[0]?.id ?? null));
const searchQuery = ref('');

const filteredSiswa = computed(() => {
  if (!searchQuery.value) return props.siswa;
  const q = searchQuery.value.toLowerCase();
  return props.siswa.filter(s =>
    s.nama.toLowerCase().includes(q) ||
    s.nis.toLowerCase().includes(q) ||
    s.nama_ortu.toLowerCase().includes(q)
  );
});

const refreshData = () => {
  router.get('/siswa', {
    kelas_id: selectedKelasId.value,
  }, {
    preserveState: false,
    preserveScroll: true,
  });
};

const modalOpen = ref(false);
const importModalOpen = ref(false);
const isEditing = ref(false);
const currentSiswaId = ref(null);

const form = useForm({
  kelas_id: props.kelas?.id,
  nis: '',
  nama: '',
  jenis_kelamin: 'L',
  nama_ortu: '',
  no_wa: '',
});

const openAddModal = () => {
  isEditing.value = false;
  currentSiswaId.value = null;
  form.reset();
  form.kelas_id = props.kelas?.id;
  modalOpen.value = true;
};

const openEditModal = (s) => {
  isEditing.value = true;
  currentSiswaId.value = s.id;
  form.kelas_id = s.kelas_id;
  form.nis = s.nis;
  form.nama = s.nama;
  form.jenis_kelamin = s.jenis_kelamin;
  form.nama_ortu = s.nama_ortu;
  form.no_wa = s.no_wa;
  modalOpen.value = true;
};

const submitForm = () => {
  if (isEditing.value) {
    form.put(`/siswa/${currentSiswaId.value}`, {
      onSuccess: () => {
        modalOpen.value = false;
      },
    });
  } else {
    form.post('/siswa', {
      onSuccess: () => {
        modalOpen.value = false;
        form.reset();
      },
    });
  }
};

const toggleAktif = (s) => {
  if (confirm(`Apakah Anda yakin ingin ${s.aktif ? 'menonaktifkan' : 'mengaktifkan'} siswa ${s.nama}?`)) {
    router.patch(`/siswa/${s.id}/toggle-aktif`, {}, {
      preserveScroll: true,
    });
  }
};

const buatAtauResetAkunOrtu = (s) => {
  const aksi = s.akun_ortu ? 'mereset PIN' : 'membuat akun orang tua';
  if (confirm(`Apakah Anda yakin ingin ${aksi} untuk siswa ${s.nama}?`)) {
    router.post(`/siswa/${s.id}/generate-akun-ortu`, {}, {
      preserveScroll: true,
    });
  }
};
</script>
