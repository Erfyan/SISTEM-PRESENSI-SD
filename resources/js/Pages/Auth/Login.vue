<template>
  <div class="min-h-screen bg-gradient-to-br from-teal-50 via-slate-50 to-emerald-50 flex flex-col justify-center py-10 sm:px-6 lg:px-8 font-sans">
    <div class="sm:mx-auto sm:w-full sm:max-w-md px-4">
      <div class="flex justify-center">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-teal-700 via-teal-600 to-emerald-500 text-white flex items-center justify-center text-3xl shadow-xl shadow-teal-600/30">
          <i class="fa-solid fa-graduation-cap"></i>
        </div>
      </div>
      <h2 class="mt-4 text-center text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
        Sipreline
      </h2>
      <p class="mt-1 text-center text-xs sm:text-sm text-slate-600 flex items-center justify-center gap-1.5">
        <i class="fa-solid fa-school text-teal-600"></i>
        <span>Website Absensi SD Negeri 1 — Cepat, Akurat, Terkoneksi WhatsApp</span>
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
      <div class="bg-white py-8 px-5 sm:px-10 shadow-xl shadow-slate-200/50 rounded-2xl border border-slate-100">
        
        <!-- Flash Alert -->
        <div v-if="$page.props.flash.info" class="mb-5 p-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-xs flex items-center gap-2">
          <i class="fa-solid fa-circle-info text-blue-600 shrink-0"></i>
          <span>{{ $page.props.flash.info }}</span>
        </div>
        <div v-if="$page.props.flash.error" class="mb-5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-2">
          <i class="fa-solid fa-circle-xmark text-rose-600 shrink-0"></i>
          <span>{{ $page.props.flash.error }}</span>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <!-- Username / ID -->
          <div>
            <label for="username" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
              <i class="fa-solid fa-user text-slate-400 text-xs"></i>
              <span>Username atau NIS</span>
            </label>
            <div class="mt-1.5 relative">
              <input
                id="username"
                v-model="form.username"
                type="text"
                autocomplete="username"
                required
                placeholder="Contoh: guru1, ortu_1001, atau admin"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-900 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition placeholder:text-slate-400"
                :class="{ 'border-rose-400 focus:ring-rose-400': form.errors.username }"
              />
            </div>
            <p v-if="form.errors.username" class="mt-1 text-xs text-rose-600 flex items-center gap-1">
              <i class="fa-solid fa-triangle-exclamation text-xs"></i>
              <span>{{ form.errors.username }}</span>
            </p>
          </div>

          <!-- Password / PIN -->
          <div>
            <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
              <i class="fa-solid fa-lock text-slate-400 text-xs"></i>
              <span>Kata Sandi / PIN</span>
            </label>
            <div class="mt-1.5 relative">
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                required
                placeholder="Masukkan kata sandi atau 6 digit PIN"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-900 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition placeholder:text-slate-400 pr-10"
                :class="{ 'border-rose-400 focus:ring-rose-400': form.errors.password }"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer"
                :title="showPassword ? 'Sembunyikan' : 'Tampilkan'"
              >
                <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
              </button>
            </div>
            <p v-if="form.errors.password" class="mt-1 text-xs text-rose-600 flex items-center gap-1">
              <i class="fa-solid fa-triangle-exclamation text-xs"></i>
              <span>{{ form.errors.password }}</span>
            </p>
          </div>

          <!-- Remember me -->
          <div class="flex items-center justify-between pt-1">
            <label class="flex items-center cursor-pointer">
              <input
                type="checkbox"
                v-model="form.remember"
                class="w-4 h-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
              />
              <span class="ml-2 text-xs text-slate-600">Ingat saya di perangkat ini</span>
            </label>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full flex justify-center items-center py-2.5 px-4 rounded-xl shadow-sm text-sm font-bold text-white bg-teal-600 hover:bg-teal-700 active:bg-teal-800 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition disabled:opacity-50 mt-4 cursor-pointer"
          >
            <i v-if="form.processing" class="fa-solid fa-circle-notch fa-spin mr-2"></i>
            <i v-else class="fa-solid fa-right-to-bracket mr-2"></i>
            <span>{{ form.processing ? 'Memproses...' : 'Masuk ke Aplikasi' }}</span>
          </button>
        </form>

        <!-- Demo Accounts Quick Fill -->
        <div class="mt-6 pt-5 border-t border-slate-100">
          <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2.5 text-center flex items-center justify-center gap-1.5">
            <i class="fa-solid fa-bolt text-amber-500"></i>
            <span>Pilihan Akun Demo (Klik untuk Isi Cepat)</span>
          </p>
          <div class="grid grid-cols-2 gap-2">
            <button
              type="button"
              @click="fillDemo('guru1', 'password')"
              class="p-2.5 rounded-xl border border-teal-200 bg-teal-50 hover:bg-teal-100 text-left transition cursor-pointer"
            >
              <div class="flex items-center gap-1.5 text-teal-900 font-bold text-[11px]">
                <i class="fa-solid fa-chalkboard-user text-teal-700"></i>
                <span>Wali Kelas 1A</span>
              </div>
              <p class="text-[10px] text-teal-700 font-mono mt-0.5">user: guru1</p>
            </button>

            <button
              type="button"
              @click="fillDemo('ortu_joko', 'password')"
              class="p-2.5 rounded-xl border border-amber-200 bg-amber-50 hover:bg-amber-100 text-left transition cursor-pointer"
            >
              <div class="flex items-center gap-1.5 text-amber-900 font-bold text-[11px]">
                <i class="fa-solid fa-hands-holding-child text-amber-700"></i>
                <span>Orang Tua Murid</span>
              </div>
              <p class="text-[10px] text-amber-700 font-mono mt-0.5">user: ortu_joko</p>
            </button>

            <button
              type="button"
              @click="fillDemo('admin', 'admin123')"
              class="p-2.5 rounded-xl border border-purple-200 bg-purple-50 hover:bg-purple-100 text-left transition cursor-pointer"
            >
              <div class="flex items-center gap-1.5 text-purple-900 font-bold text-[11px]">
                <i class="fa-solid fa-shield-halved text-purple-700"></i>
                <span>Admin Sekolah</span>
              </div>
              <p class="text-[10px] text-purple-700 font-mono mt-0.5">user: admin</p>
            </button>

            <button
              type="button"
              @click="fillDemo('guru2', 'password')"
              class="p-2.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-left transition cursor-pointer"
            >
              <div class="flex items-center gap-1.5 text-slate-900 font-bold text-[11px]">
                <i class="fa-solid fa-chalkboard-user text-slate-700"></i>
                <span>Wali Kelas 4B</span>
              </div>
              <p class="text-[10px] text-slate-700 font-mono mt-0.5">user: guru2</p>
            </button>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const showPassword = ref(false);

const form = useForm({
  username: '',
  password: '',
  remember: false,
});

const fillDemo = (u, p) => {
  form.username = u;
  form.password = p;
};

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};
</script>
