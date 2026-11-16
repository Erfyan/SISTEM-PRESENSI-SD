<template>
  <div class="min-h-screen bg-slate-50 flex flex-col font-sans text-slate-800">
    <!-- Navbar Header -->
    <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/90 sticky top-0 z-30 shadow-xs transition-all">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
          
          <!-- Logo & Brand -->
          <div class="flex items-center gap-3">
            <Link href="/" class="flex items-center gap-2.5 group">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-teal-700 via-teal-600 to-emerald-500 text-white flex items-center justify-center text-lg shadow-sm shadow-teal-600/20 group-hover:scale-105 transition-transform duration-200">
                <i class="fa-solid fa-graduation-cap"></i>
              </div>
              <div>
                <div class="flex items-center gap-1.5">
                  <span class="text-lg font-black tracking-tight text-slate-900 group-hover:text-teal-700 transition">
                    Sipreline
                  </span>
                  <span class="hidden sm:inline-flex items-center gap-1 text-[11px] px-2 py-0.5 bg-teal-50 text-teal-700 font-bold rounded-full border border-teal-200">
                    <i class="fa-solid fa-school text-[9px]"></i>
                    SD Negeri 1
                  </span>
                </div>
                <p class="text-[11px] text-slate-500 hidden md:block">
                  Sistem Presensi & Komunikasi Siswa
                </p>
              </div>
            </Link>
          </div>

          <!-- Desktop Navigation -->
          <nav class="hidden lg:flex items-center space-x-1">
            <!-- Guru Menu -->
            <template v-if="user?.peran === 'guru'">
              <Link
                href="/absensi"
                :class="[
                  'px-3 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-1.5',
                  isUrl('/absensi')
                    ? 'bg-teal-600 text-white shadow-xs'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
                ]"
              >
                <i class="fa-solid fa-clipboard-check text-xs"></i>
                <span>Absensi Harian</span>
              </Link>
              <Link
                href="/rekap"
                :class="[
                  'px-3 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-1.5',
                  isUrl('/rekap')
                    ? 'bg-teal-600 text-white shadow-xs'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
                ]"
              >
                <i class="fa-solid fa-chart-column text-xs"></i>
                <span>Rekap Bulanan</span>
              </Link>
              <Link
                href="/siswa"
                :class="[
                  'px-3 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-1.5',
                  isUrl('/siswa')
                    ? 'bg-teal-600 text-white shadow-xs'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
                ]"
              >
                <i class="fa-solid fa-users text-xs"></i>
                <span>Data Siswa</span>
              </Link>
              <Link
                href="/kalender-libur"
                :class="[
                  'px-3 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-1.5',
                  isUrl('/kalender-libur')
                    ? 'bg-teal-600 text-white shadow-xs'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
                ]"
              >
                <i class="fa-solid fa-calendar-days text-xs"></i>
                <span>Kalender Libur</span>
              </Link>
            </template>

            <!-- Admin Menu -->
            <template v-if="user?.peran === 'admin'">
              <Link
                href="/admin/dashboard"
                :class="[
                  'px-2.5 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1.5',
                  isUrl('/admin/dashboard')
                    ? 'bg-teal-600 text-white shadow-xs'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
                ]"
              >
                <i class="fa-solid fa-gauge-high text-xs"></i>
                <span>Overview</span>
              </Link>
              <Link
                href="/rekap"
                :class="[
                  'px-2.5 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1.5',
                  isUrl('/rekap')
                    ? 'bg-teal-600 text-white shadow-xs'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
                ]"
              >
                <i class="fa-solid fa-chart-pie text-xs"></i>
                <span>Rekap</span>
              </Link>
              <Link
                href="/admin/kelas"
                :class="[
                  'px-2.5 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1.5',
                  isUrl('/admin/kelas')
                    ? 'bg-teal-600 text-white shadow-xs'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
                ]"
              >
                <i class="fa-solid fa-school text-xs"></i>
                <span>Kelas</span>
              </Link>
              <Link
                href="/admin/guru"
                :class="[
                  'px-2.5 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1.5',
                  isUrl('/admin/guru')
                    ? 'bg-teal-600 text-white shadow-xs'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
                ]"
              >
                <i class="fa-solid fa-chalkboard-user text-xs"></i>
                <span>Guru</span>
              </Link>
              <Link
                href="/siswa"
                :class="[
                  'px-2.5 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1.5',
                  isUrl('/siswa')
                    ? 'bg-teal-600 text-white shadow-xs'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
                ]"
              >
                <i class="fa-solid fa-users text-xs"></i>
                <span>Siswa</span>
              </Link>
              <Link
                href="/kalender-libur"
                :class="[
                  'px-2.5 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1.5',
                  isUrl('/kalender-libur')
                    ? 'bg-teal-600 text-white shadow-xs'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
                ]"
              >
                <i class="fa-solid fa-calendar-day text-xs"></i>
                <span>Libur</span>
              </Link>
              <Link
                href="/admin/status-sistem"
                :class="[
                  'px-2.5 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1.5',
                  isUrl('/admin/status-sistem')
                    ? 'bg-teal-600 text-white shadow-xs'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
                ]"
              >
                <i class="fa-solid fa-server text-xs"></i>
                <span>Sistem</span>
              </Link>
            </template>

            <!-- Ortu Menu -->
            <template v-if="user?.peran === 'ortu'">
              <Link
                href="/portal-ortu"
                :class="[
                  'px-3.5 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-1.5',
                  isUrl('/portal-ortu')
                    ? 'bg-teal-600 text-white shadow-xs'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
                ]"
              >
                <i class="fa-solid fa-child-reaching text-xs"></i>
                <span>Portal Kehadiran Anak</span>
              </Link>
            </template>
          </nav>

          <!-- User Menu Dropdown & Mobile Toggle -->
          <div class="flex items-center gap-2 sm:gap-3">
            <!-- User Info Badge Dropdown -->
            <div class="relative">
              <button
                @click="userMenuOpen = !userMenuOpen"
                class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-100 transition focus:outline-hidden cursor-pointer"
              >
                <div class="w-9 h-9 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-sm border border-teal-200 shadow-xs">
                  <span v-if="user?.nama">{{ user.nama.charAt(0).toUpperCase() }}</span>
                  <i v-else class="fa-solid fa-user text-xs"></i>
                </div>
                <div class="text-left hidden sm:block">
                  <div class="text-xs font-bold text-slate-800 leading-tight truncate max-w-[140px]">
                    {{ user?.nama }}
                  </div>
                  <div class="flex items-center gap-1 mt-0.5">
                    <span
                      :class="[
                        'text-[10px] font-semibold px-1.5 py-0.2 rounded-md uppercase inline-flex items-center gap-1',
                        user?.peran === 'admin' ? 'bg-purple-100 text-purple-700 border border-purple-200' : '',
                        user?.peran === 'guru' ? 'bg-teal-100 text-teal-800 border border-teal-200' : '',
                        user?.peran === 'ortu' ? 'bg-amber-100 text-amber-800 border border-amber-200' : ''
                      ]"
                    >
                      <i v-if="user?.peran === 'admin'" class="fa-solid fa-shield-halved text-[9px]"></i>
                      <i v-else-if="user?.peran === 'guru'" class="fa-solid fa-chalkboard-user text-[9px]"></i>
                      <i v-else class="fa-solid fa-hands-holding-child text-[9px]"></i>
                      {{ user?.peran === 'guru' ? (user?.kelas_diampu ? 'Wali ' + user.kelas_diampu.nama : 'Guru') : user?.peran }}
                    </span>
                  </div>
                </div>
                <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-150" :class="{ 'rotate-180': userMenuOpen }"></i>
              </button>

              <!-- Dropdown Menu -->
              <div
                v-if="userMenuOpen"
                @click.outside="userMenuOpen = false"
                class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-200 py-1.5 z-50 animate-in fade-in zoom-in-95 duration-100"
              >
                <div class="px-4 py-2.5 border-b border-slate-100">
                  <p class="text-xs font-bold text-slate-900 truncate">{{ user?.nama }}</p>
                  <p class="text-[11px] text-slate-500 font-mono">@{{ user?.username }} · {{ user?.peran }}</p>
                </div>

                <Link
                  href="/profil/password"
                  class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-teal-700 transition"
                  @click="userMenuOpen = false"
                >
                  <i class="fa-solid fa-key text-slate-400 w-4 text-center"></i>
                  <span>Ganti Kata Sandi / PIN</span>
                </Link>

                <div class="border-t border-slate-100 my-1"></div>

                <Link
                  href="/logout"
                  method="post"
                  as="button"
                  class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition text-left cursor-pointer"
                >
                  <i class="fa-solid fa-right-from-bracket text-rose-500 w-4 text-center"></i>
                  <span>Keluar dari Aplikasi</span>
                </Link>
              </div>
            </div>

            <!-- Mobile Hamburger Button -->
            <button
              @click="mobileMenuOpen = !mobileMenuOpen"
              class="lg:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100 transition cursor-pointer"
              aria-label="Buka Menu Navigasi"
            >
              <i v-if="!mobileMenuOpen" class="fa-solid fa-bars text-lg w-5 h-5 flex items-center justify-center"></i>
              <i v-else class="fa-solid fa-xmark text-lg w-5 h-5 flex items-center justify-center"></i>
            </button>
          </div>

        </div>
      </div>

      <!-- Mobile Navigation Drawer -->
      <div v-if="mobileMenuOpen" class="lg:hidden border-t border-slate-200 bg-white px-4 pt-2 pb-4 space-y-1 shadow-lg animate-in slide-in-from-top-2 duration-150">
        <!-- Guru Mobile Menu -->
        <template v-if="user?.peran === 'guru'">
          <Link
            href="/absensi"
            @click="mobileMenuOpen = false"
            :class="[
              'flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-semibold transition',
              isUrl('/absensi') ? 'bg-teal-600 text-white' : 'text-slate-700 hover:bg-slate-100'
            ]"
          >
            <i class="fa-solid fa-clipboard-check w-4 text-center"></i>
            <span>Absensi Harian</span>
          </Link>
          <Link
            href="/rekap"
            @click="mobileMenuOpen = false"
            :class="[
              'flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-semibold transition',
              isUrl('/rekap') ? 'bg-teal-600 text-white' : 'text-slate-700 hover:bg-slate-100'
            ]"
          >
            <i class="fa-solid fa-chart-column w-4 text-center"></i>
            <span>Rekap Bulanan</span>
          </Link>
          <Link
            href="/siswa"
            @click="mobileMenuOpen = false"
            :class="[
              'flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-semibold transition',
              isUrl('/siswa') ? 'bg-teal-600 text-white' : 'text-slate-700 hover:bg-slate-100'
            ]"
          >
            <i class="fa-solid fa-users w-4 text-center"></i>
            <span>Data Siswa</span>
          </Link>
          <Link
            href="/kalender-libur"
            @click="mobileMenuOpen = false"
            :class="[
              'flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-semibold transition',
              isUrl('/kalender-libur') ? 'bg-teal-600 text-white' : 'text-slate-700 hover:bg-slate-100'
            ]"
          >
            <i class="fa-solid fa-calendar-days w-4 text-center"></i>
            <span>Kalender Libur</span>
          </Link>
        </template>

        <!-- Admin Mobile Menu -->
        <template v-if="user?.peran === 'admin'">
          <Link
            href="/admin/dashboard"
            @click="mobileMenuOpen = false"
            :class="[
              'flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-semibold transition',
              isUrl('/admin/dashboard') ? 'bg-teal-600 text-white' : 'text-slate-700 hover:bg-slate-100'
            ]"
          >
            <i class="fa-solid fa-gauge-high w-4 text-center"></i>
            <span>Overview Presensi</span>
          </Link>
          <Link
            href="/rekap"
            @click="mobileMenuOpen = false"
            :class="[
              'flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-semibold transition',
              isUrl('/rekap') ? 'bg-teal-600 text-white' : 'text-slate-700 hover:bg-slate-100'
            ]"
          >
            <i class="fa-solid fa-chart-pie w-4 text-center"></i>
            <span>Rekap Sekolah</span>
          </Link>
          <Link
            href="/admin/kelas"
            @click="mobileMenuOpen = false"
            :class="[
              'flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-semibold transition',
              isUrl('/admin/kelas') ? 'bg-teal-600 text-white' : 'text-slate-700 hover:bg-slate-100'
            ]"
          >
            <i class="fa-solid fa-school w-4 text-center"></i>
            <span>Kelola Kelas</span>
          </Link>
          <Link
            href="/admin/guru"
            @click="mobileMenuOpen = false"
            :class="[
              'flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-semibold transition',
              isUrl('/admin/guru') ? 'bg-teal-600 text-white' : 'text-slate-700 hover:bg-slate-100'
            ]"
          >
            <i class="fa-solid fa-chalkboard-user w-4 text-center"></i>
            <span>Kelola Guru</span>
          </Link>
          <Link
            href="/siswa"
            @click="mobileMenuOpen = false"
            :class="[
              'flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-semibold transition',
              isUrl('/siswa') ? 'bg-teal-600 text-white' : 'text-slate-700 hover:bg-slate-100'
            ]"
          >
            <i class="fa-solid fa-users w-4 text-center"></i>
            <span>Data Siswa</span>
          </Link>
          <Link
            href="/kalender-libur"
            @click="mobileMenuOpen = false"
            :class="[
              'flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-semibold transition',
              isUrl('/kalender-libur') ? 'bg-teal-600 text-white' : 'text-slate-700 hover:bg-slate-100'
            ]"
          >
            <i class="fa-solid fa-calendar-days w-4 text-center"></i>
            <span>Kalender Libur</span>
          </Link>
          <Link
            href="/admin/status-sistem"
            @click="mobileMenuOpen = false"
            :class="[
              'flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-semibold transition',
              isUrl('/admin/status-sistem') ? 'bg-teal-600 text-white' : 'text-slate-700 hover:bg-slate-100'
            ]"
          >
            <i class="fa-solid fa-server w-4 text-center"></i>
            <span>Status Sistem</span>
          </Link>
        </template>

        <!-- Ortu Mobile Menu -->
        <template v-if="user?.peran === 'ortu'">
          <Link
            href="/portal-ortu"
            @click="mobileMenuOpen = false"
            :class="[
              'flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-semibold transition',
              isUrl('/portal-ortu') ? 'bg-teal-600 text-white' : 'text-slate-700 hover:bg-slate-100'
            ]"
          >
            <i class="fa-solid fa-child-reaching w-4 text-center"></i>
            <span>Portal Kehadiran Anak</span>
          </Link>
        </template>
      </div>
    </header>

    <!-- Notification Toasts / Banners -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full mt-4">
      <!-- Success Banner -->
      <div v-if="$page.props.flash.success" class="mb-4 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs sm:text-sm flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-circle-check text-base"></i>
          </div>
          <span class="font-medium">{{ $page.props.flash.success }}</span>
        </div>
      </div>

      <!-- Error Banner -->
      <div v-if="$page.props.flash.error" class="mb-4 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs sm:text-sm flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-circle-xmark text-base"></i>
          </div>
          <span class="font-medium">{{ $page.props.flash.error }}</span>
        </div>
      </div>

      <!-- Info Banner -->
      <div v-if="$page.props.flash.info" class="mb-4 p-4 rounded-2xl bg-blue-50 border border-blue-200 text-blue-900 text-xs sm:text-sm flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-circle-info text-base"></i>
          </div>
          <span class="font-medium">{{ $page.props.flash.info }}</span>
        </div>
      </div>

      <!-- Flash Modal / Alert for Temporary PIN (Replaced emoji 🔑 with Font Awesome icon) -->
      <div v-if="$page.props.flash.temp_password" class="mb-5 p-5 rounded-2xl bg-amber-50 border-2 border-amber-300 text-amber-900 shadow-md">
        <div class="flex items-start gap-3">
          <div class="w-9 h-9 rounded-xl bg-amber-200 text-amber-800 flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
            <i class="fa-solid fa-key text-base"></i>
          </div>
          <div class="flex-1">
            <h4 class="font-bold text-amber-950 text-base flex items-center gap-2">
              <span>Informasi Akun Orang Tua (Tampil Satu Kali)</span>
            </h4>
            <p class="text-xs text-amber-800 mt-0.5">
              Berikan informasi ini kepada orang tua siswa. PIN ini hanya ditampilkan satu kali demi keamanan data siswa:
            </p>
            <div class="mt-3 flex flex-wrap gap-4 items-center bg-white p-3.5 rounded-xl border border-amber-200">
              <div>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1">
                  <i class="fa-solid fa-user text-[10px]"></i>
                  Username:
                </span>
                <p class="font-mono text-base font-bold text-slate-900 mt-0.5">{{ $page.props.flash.temp_username }}</p>
              </div>
              <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>
              <div>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1">
                  <i class="fa-solid fa-lock text-[10px]"></i>
                  PIN Masuk:
                </span>
                <p class="font-mono text-xl font-extrabold text-amber-600 tracking-wider mt-0.5">{{ $page.props.flash.temp_password }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
      <slot />
    </main>

    <!-- Mobile Quick Navigation Bar (Sticky Bottom for high mobility on phones) -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur-md border-t border-slate-200 z-20 py-1.5 px-3 shadow-lg">
      <div class="flex items-center justify-around">
        <template v-if="user?.peran === 'guru'">
          <Link
            href="/absensi"
            :class="[
              'flex flex-col items-center py-1 px-3 rounded-xl text-[10px] font-semibold transition',
              isUrl('/absensi') ? 'text-teal-700 font-bold' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            <i class="fa-solid fa-clipboard-check text-base mb-0.5"></i>
            <span>Absensi</span>
          </Link>
          <Link
            href="/rekap"
            :class="[
              'flex flex-col items-center py-1 px-3 rounded-xl text-[10px] font-semibold transition',
              isUrl('/rekap') ? 'text-teal-700 font-bold' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            <i class="fa-solid fa-chart-column text-base mb-0.5"></i>
            <span>Rekap</span>
          </Link>
          <Link
            href="/siswa"
            :class="[
              'flex flex-col items-center py-1 px-3 rounded-xl text-[10px] font-semibold transition',
              isUrl('/siswa') ? 'text-teal-700 font-bold' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            <i class="fa-solid fa-users text-base mb-0.5"></i>
            <span>Siswa</span>
          </Link>
          <Link
            href="/kalender-libur"
            :class="[
              'flex flex-col items-center py-1 px-3 rounded-xl text-[10px] font-semibold transition',
              isUrl('/kalender-libur') ? 'text-teal-700 font-bold' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            <i class="fa-solid fa-calendar-days text-base mb-0.5"></i>
            <span>Libur</span>
          </Link>
        </template>

        <template v-if="user?.peran === 'admin'">
          <Link
            href="/admin/dashboard"
            :class="[
              'flex flex-col items-center py-1 px-2.5 rounded-xl text-[10px] font-semibold transition',
              isUrl('/admin/dashboard') ? 'text-teal-700 font-bold' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            <i class="fa-solid fa-gauge-high text-base mb-0.5"></i>
            <span>Overview</span>
          </Link>
          <Link
            href="/rekap"
            :class="[
              'flex flex-col items-center py-1 px-2.5 rounded-xl text-[10px] font-semibold transition',
              isUrl('/rekap') ? 'text-teal-700 font-bold' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            <i class="fa-solid fa-chart-pie text-base mb-0.5"></i>
            <span>Rekap</span>
          </Link>
          <Link
            href="/admin/kelas"
            :class="[
              'flex flex-col items-center py-1 px-2.5 rounded-xl text-[10px] font-semibold transition',
              isUrl('/admin/kelas') ? 'text-teal-700 font-bold' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            <i class="fa-solid fa-school text-base mb-0.5"></i>
            <span>Kelas</span>
          </Link>
          <Link
            href="/admin/guru"
            :class="[
              'flex flex-col items-center py-1 px-2.5 rounded-xl text-[10px] font-semibold transition',
              isUrl('/admin/guru') ? 'text-teal-700 font-bold' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            <i class="fa-solid fa-chalkboard-user text-base mb-0.5"></i>
            <span>Guru</span>
          </Link>
          <Link
            href="/admin/status-sistem"
            :class="[
              'flex flex-col items-center py-1 px-2.5 rounded-xl text-[10px] font-semibold transition',
              isUrl('/admin/status-sistem') ? 'text-teal-700 font-bold' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            <i class="fa-solid fa-server text-base mb-0.5"></i>
            <span>Sistem</span>
          </Link>
        </template>

        <template v-if="user?.peran === 'ortu'">
          <Link
            href="/portal-ortu"
            :class="[
              'flex flex-col items-center py-1 px-4 rounded-xl text-[10px] font-semibold transition',
              isUrl('/portal-ortu') ? 'text-teal-700 font-bold' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            <i class="fa-solid fa-child-reaching text-base mb-0.5"></i>
            <span>Kehadiran Anak</span>
          </Link>
          <Link
            href="/profil/password"
            :class="[
              'flex flex-col items-center py-1 px-4 rounded-xl text-[10px] font-semibold transition',
              isUrl('/profil/password') ? 'text-teal-700 font-bold' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            <i class="fa-solid fa-key text-base mb-0.5"></i>
            <span>Ganti PIN</span>
          </Link>
        </template>
      </div>
    </nav>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 mt-12 mb-14 lg:mb-0">
      <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
        <div class="flex items-center gap-2">
          <div class="w-6 h-6 rounded-lg bg-teal-600 text-white flex items-center justify-center text-[10px]">
            <i class="fa-solid fa-graduation-cap"></i>
          </div>
          <span>&copy; 2026 <strong>Sipreline</strong> — Aplikasi Presensi Siswa SD Negeri 1.</span>
        </div>
        <div class="flex items-center gap-3 text-slate-400">
          <span class="flex items-center gap-1 text-emerald-600 font-medium">
            <i class="fa-brands fa-whatsapp text-sm"></i> WhatsApp Terintegrasi
          </span>
          <span>·</span>
          <span class="flex items-center gap-1">
            <i class="fa-solid fa-shield-halved text-xs"></i> PWA Siap Offline
          </span>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const mobileMenuOpen = ref(false);
const userMenuOpen = ref(false);

const isUrl = (path) => {
  return page.url.startsWith(path);
};
</script>
