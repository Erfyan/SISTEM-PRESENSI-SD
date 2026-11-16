<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in duration-150">
      
      <!-- Modal Header -->
      <div class="p-5 bg-emerald-600 text-white flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-xl">
            <i class="fa-brands fa-whatsapp text-2xl"></i>
          </div>
          <div>
            <h3 class="text-base font-bold flex items-center gap-2">
              <span>Antrean WhatsApp Orang Tua</span>
            </h3>
            <p class="text-xs text-emerald-100">
              Siswa Izin, Sakit, atau Alpa hari ini
            </p>
          </div>
        </div>

        <button
          type="button"
          @click="$emit('close')"
          class="text-emerald-100 hover:text-white p-1 rounded-lg hover:bg-white/10 cursor-pointer"
        >
          <i class="fa-solid fa-xmark text-lg"></i>
        </button>
      </div>

      <!-- Quick Metrics Tracker (Target 90%) -->
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
        <div class="flex items-center gap-2">
          <span class="font-bold text-slate-800">
            {{ belumDihubungiCount }} siswa belum dihubungi
          </span>
          <span class="text-slate-400">·</span>
          <span class="text-slate-500">
            {{ sudahDikirimCount }} sudah dikirim
          </span>
        </div>

        <div class="flex items-center gap-2">
          <span class="text-slate-500">Capaian:</span>
          <span
            :class="[
              'px-2 py-0.5 rounded-full font-bold text-[11px]',
              persentaseCapaian >= 90
                ? 'bg-emerald-100 text-emerald-800 border border-emerald-300'
                : 'bg-amber-100 text-amber-800 border border-amber-300'
            ]"
          >
            {{ persentaseCapaian }}% (Target: &ge;90%)
          </span>
        </div>
      </div>

      <!-- Fast Action: "Kirim Berikutnya" Banner -->
      <div v-if="nextSiswa" class="p-4 bg-emerald-50 border-b border-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Berikutnya Dalam Antrean:</span>
          <h4 class="text-sm font-bold text-slate-900">
            {{ nextSiswa.nama }}
            <span class="text-xs font-normal text-slate-600">({{ nextSiswa.status_teks }})</span>
          </h4>
          <p class="text-xs text-slate-500">
            Wali: {{ nextSiswa.nama_ortu }} · <span class="font-mono">{{ nextSiswa.no_wa }}</span>
          </p>
        </div>

        <button
          type="button"
          @click="kirimBerikutnya(nextSiswa)"
          class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/30 flex items-center justify-center gap-2 cursor-pointer transition active:scale-95 shrink-0"
        >
          <i class="fa-brands fa-whatsapp text-base"></i>
          <span>Kirim Berikutnya (wa.me)</span>
        </button>
      </div>

      <!-- Queue Roster List -->
      <div class="flex-1 overflow-y-auto p-4 space-y-3">
        <div
          v-for="(item, idx) in localAntrean"
          :key="item.siswa_id"
          class="p-3.5 rounded-xl border transition-all"
          :class="[
            item.wa_status === 'sudah_dikirim'
              ? 'bg-slate-50/70 border-slate-200'
              : 'bg-white border-slate-200 hover:border-emerald-300 shadow-xs'
          ]"
        >
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start gap-2.5">
              <span class="w-6 h-6 rounded-lg bg-slate-100 font-bold text-xs text-slate-600 flex items-center justify-center shrink-0 mt-0.5">
                {{ idx + 1 }}
              </span>
              <div>
                <div class="flex items-center gap-2">
                  <h5 class="font-bold text-sm text-slate-900">{{ item.nama }}</h5>
                  <span
                    :class="[
                      'px-1.5 py-0.5 rounded text-[10px] font-bold',
                      item.status === 'I' ? 'bg-amber-100 text-amber-800' : (item.status === 'S' ? 'bg-blue-100 text-blue-800' : 'bg-rose-100 text-rose-800')
                    ]"
                  >
                    {{ item.status_teks }}
                  </span>
                </div>
                <p class="text-xs text-slate-500">
                  Ortu: {{ item.nama_ortu }} · <span class="font-mono text-slate-600">{{ item.no_wa }}</span>
                </p>
                <p v-if="item.catatan" class="text-xs text-slate-600 italic mt-0.5">
                  "{{ item.catatan }}"
                </p>
              </div>
            </div>

            <!-- Action & Status Badges -->
            <div class="flex items-center gap-2 shrink-0">
              <div v-if="item.wa_status === 'sudah_dikirim'" class="text-right">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 font-bold text-xs">
                  <i class="fa-solid fa-check text-emerald-700"></i>
                  <span>Sudah Dikirim</span>
                </span>
                <p v-if="item.wa_dikirim_pada" class="text-[10px] text-slate-400 mt-0.5">
                  Pukul {{ item.wa_dikirim_pada }}
                </p>
              </div>

              <div v-else class="flex items-center gap-2">
                <a
                  :href="item.wa_link"
                  target="_blank"
                  @click="onLinkClick(item)"
                  class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-xs cursor-pointer"
                >
                  <i class="fa-brands fa-whatsapp text-sm"></i>
                  <span>Buka WA</span>
                </a>
                <button
                  type="button"
                  @click="markTerkirim(item)"
                  class="px-2.5 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 text-xs font-semibold cursor-pointer"
                  title="Tandai manual sudah dikirim"
                >
                  Tandai Selesai
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="localAntrean.length === 0" class="p-8 text-center text-slate-400 text-xs">
          Semua siswa hari ini berstatus Hadir atau tidak memiliki nomor WhatsApp.
        </div>
      </div>

      <!-- Footer: Template Editor Toggle -->
      <div class="p-4 bg-slate-50 border-t border-slate-200">
        <div class="flex items-center justify-between">
          <button
            type="button"
            @click="showTemplateEditor = !showTemplateEditor"
            class="text-xs font-semibold text-teal-700 hover:text-teal-800 flex items-center gap-1 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            <span>{{ showTemplateEditor ? 'Tutup Pengaturan Template' : 'Ubah Template Pesan Guru' }}</span>
          </button>

          <button
            type="button"
            @click="$emit('close')"
            class="px-5 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold cursor-pointer"
          >
            Tutup
          </button>
        </div>

        <!-- Template Editor Box -->
        <div v-if="showTemplateEditor" class="mt-3 pt-3 border-t border-slate-200 space-y-2">
          <label class="block text-[11px] font-bold text-slate-700">
            Format Pesan WhatsApp (Placeholder: {nama_ortu}, {nama_siswa}, {nama_kelas}, {tanggal}, {status}, {catatan}, {nama_guru}):
          </label>
          <textarea
            v-model="customTemplate"
            rows="3"
            class="w-full text-xs p-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-teal-500"
          ></textarea>
          <div class="flex justify-end gap-2">
            <button
              type="button"
              @click="resetTemplateDefault"
              class="text-xs text-slate-500 hover:text-slate-700 font-medium cursor-pointer"
            >
              Reset Bawaan
            </button>
            <button
              type="button"
              @click="saveTemplate"
              :disabled="savingTemplate"
              class="px-4 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold cursor-pointer"
            >
              {{ savingTemplate ? 'Menyimpan...' : 'Simpan Template Saya' }}
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  isOpen: Boolean,
  antrean: {
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
});

const emit = defineEmits(['close', 'updated']);

const localAntrean = ref([]);
const customTemplate = ref(props.templateWa || props.defaultTemplateWa);
const showTemplateEditor = ref(false);
const savingTemplate = ref(false);
const activeSentItem = ref(null);

watch(() => props.antrean, (newAntrean) => {
  localAntrean.value = JSON.parse(JSON.stringify(newAntrean));
}, { immediate: true });

const belumDihubungiCount = computed(() => {
  return localAntrean.value.filter(item => item.wa_status !== 'sudah_dikirim').length;
});

const sudahDikirimCount = computed(() => {
  return localAntrean.value.filter(item => item.wa_status === 'sudah_dikirim').length;
});

const persentaseCapaian = computed(() => {
  const total = localAntrean.value.length;
  if (total === 0) return 100;
  return Math.round((sudahDikirimCount.value / total) * 100);
});

// Siswa pertama yang belum dihubungi
const nextSiswa = computed(() => {
  return localAntrean.value.find(item => item.wa_status !== 'sudah_dikirim') || null;
});

const markTerkirim = async (item) => {
  if (!item.absensi_id) {
    item.wa_status = 'sudah_dikirim';
    item.wa_dikirim_pada = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    return;
  }

  try {
    const res = await axios.post(`/absensi/${item.absensi_id}/tandai-wa-terkirim`);
    if (res.data.success) {
      item.wa_status = 'sudah_dikirim';
      item.wa_dikirim_pada = res.data.wa_dikirim_pada;
      emit('updated');
    }
  } catch (err) {
    console.warn('Gagal menandai status WA:', err);
  }
};

const onLinkClick = (item) => {
  activeSentItem.value = item;
};

const kirimBerikutnya = (item) => {
  activeSentItem.value = item;
  window.open(item.wa_link, '_blank');
};

// Deteksi saat guru kembali ke tab aplikasi dari WhatsApp
const handleWindowFocus = () => {
  if (activeSentItem.value && activeSentItem.value.wa_status !== 'sudah_dikirim') {
    markTerkirim(activeSentItem.value);
    activeSentItem.value = null;
  }
};

const saveTemplate = async () => {
  savingTemplate.value = true;
  try {
    const res = await axios.post('/profil/template-wa', {
      template_wa: customTemplate.value,
    });
    if (res.data.success) {
      alert('Template pesan WhatsApp berhasil diperbarui.');
      showTemplateEditor.value = false;
      emit('updated');
    }
  } catch (err) {
    alert('Gagal menyimpan template: ' + (err.response?.data?.message || err.message));
  } finally {
    savingTemplate.value = false;
  }
};

const resetTemplateDefault = () => {
  customTemplate.value = props.defaultTemplateWa;
};

onMounted(() => {
  window.addEventListener('focus', handleWindowFocus);
});

onUnmounted(() => {
  window.removeEventListener('focus', handleWindowFocus);
});
</script>
