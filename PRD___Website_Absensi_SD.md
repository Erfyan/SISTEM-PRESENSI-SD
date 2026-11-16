# PRD — Website Absensi SD

Nov 13, 2026 · @Erf

## Ringkasan

Website Absensi SD menggantikan buku absen kertas dengan aplikasi web yang dipakai guru dari HP atau laptop, sehingga kehadiran siswa tercatat per kelas dan orang tua bisa melihat serta dihubungi lewat WhatsApp saat anaknya tidak masuk.

**Masalah.** Absensi manual di kertas sulit direkap, mudah salah hitung, dan orang tua baru tahu anaknya bolos atau sakit lama setelah kejadian. Rekap bulanan untuk laporan sekolah harus disalin ulang ke Excel.

**Solusi.** Aplikasi web penuh dengan database dan login. Guru mengisi absensi harian, sistem menghitung rekap bulanan otomatis, laporan diunduh sebagai Excel atau PDF, dan orang tua memantau kehadiran anaknya lewat akun sendiri atau pesan WhatsApp dari guru.

**Yang diuntungkan.** Guru kelas (waktu rekap berkurang), orang tua (informasi cepat), dan kepala sekolah/admin (data kehadiran yang rapi dan bisa dilaporkan).

## Tujuan dan metrik keberhasilan

Versi 1 dianggap berhasil bila guru mengisi absen lebih cepat daripada di kertas dan orang tua benar-benar memakainya. Angka di kolom target adalah usulan awal yang perlu disepakati dengan pihak sekolah.

| Tujuan | Metrik | Target usulan |
| --- | --- | --- |
| Absen harian cepat | Waktu guru mengisi absen satu kelas | 2 menit atau kurang |
| Rekap tanpa salin ulang | Waktu menyiapkan laporan bulanan | 1 menit (unduh Excel/PDF) |
| Orang tua cepat tahu | Siswa tidak hadir yang orang tuanya dihubungi di hari yang sama | 90% atau lebih |
| Dipakai rutin | Kelas yang absennya terisi di tiap hari sekolah | 95% atau lebih |
| Data akurat | Selisih rekap aplikasi dengan catatan guru | 0 |

## Pengguna dan peran

Aplikasi melayani dua pengguna utama, guru dan orang tua, ditambah satu peran admin kecil yang diusulkan agar akun dan kelas bisa dikelola tanpa mengubah database langsung.

| Peran | Siapa | Kebutuhan utama | Akses |
| --- | --- | --- | --- |
| Guru | Wali kelas | Mengisi absen harian, melihat rekap, menghubungi orang tua lewat WhatsApp | Hanya kelas yang dia ampu |
| Orang tua | Wali murid | Melihat kehadiran dan rekap bulanan anaknya | Hanya data anaknya sendiri (bisa lebih dari satu anak) |
| Admin (usulan) | Kepala sekolah atau tata usaha | Membuat kelas dan akun guru, melihat rekap seluruh sekolah | Semua kelas |

Peran admin belum disebut dalam kebutuhan awal. Jika sekolah tidak membutuhkannya, pengelolaan kelas dan akun guru dilakukan lewat skrip awal oleh pengembang.

## Ruang lingkup

Versi 1 fokus pada absensi harian per kelas, rekap, ekspor, dan kontak WhatsApp; fitur yang butuh biaya atau perangkat tambahan sengaja ditunda.

**Masuk versi 1**

- Login untuk guru, orang tua, dan admin
- Data kelas dan siswa, termasuk nama orang tua dan nomor WhatsApp
- Absensi harian dengan status Hadir, Izin, Sakit, Alpa dan catatan singkat
- Rekap bulanan per siswa dan per kelas
- Ekspor rekap ke Excel dan PDF
- Tombol kirim pesan WhatsApp ke orang tua dengan teks yang sudah terisi
- Portal orang tua untuk melihat kehadiran anak (hanya baca)
- Tampilan responsif untuk HP dan laptop

**Ditunda**

- Pengiriman WhatsApp otomatis lewat API (butuh akun dan biaya penyedia)
- Absensi dengan QR, kartu, atau pengenalan wajah
- Notifikasi push dan aplikasi mobile native
- Impor siswa massal dari Excel
- Absensi per mata pelajaran atau per jam pelajaran
- Kalender libur sekolah otomatis

## Kebutuhan fungsional

Ada 14 kebutuhan: 8 wajib untuk versi 1, 5 penting, dan 1 untuk tahap berikutnya. Prioritas bisa diubah langsung lewat dropdown.

| ID | Peran | Kebutuhan | Prioritas |
| --- | --- | --- | --- |
| G1 | Guru | Memilih kelas dan tanggal, lalu menandai Hadir, Izin, Sakit, atau Alpa untuk tiap siswa, dengan tombol "Semua hadir" sebagai titik awal | Wajib |
| G2 | Guru | Mengoreksi absen yang sudah disimpan | Wajib |
| G3 | Guru | Melihat rekap bulanan kelas: jumlah Hadir, Izin, Sakit, Alpa dan persentase kehadiran per siswa | Wajib |
| G4 | Guru | Mengunduh rekap bulanan sebagai file Excel dan PDF | Wajib |
| G5 | Guru | Mengirim pesan WhatsApp ke orang tua siswa yang tidak hadir, dengan teks yang sudah terisi | Wajib |
| G6 | Guru | Menambah, mengubah, dan menonaktifkan siswa di kelasnya | Wajib |
| G7 | Guru | Membuat akun orang tua dan mereset PIN-nya | Penting |
| O1 | Orang tua | Masuk dan melihat daftar kehadiran anak per bulan | Wajib |
| O2 | Orang tua | Melihat ringkasan jumlah Hadir, Izin, Sakit, dan Alpa anak | Wajib |
| O3 | Orang tua | Berpindah antar anak bila punya lebih dari satu | Penting |
| A1 | Admin | Membuat kelas dan menunjuk wali kelas | Penting |
| A2 | Admin | Membuat dan menonaktifkan akun guru | Penting |
| A3 | Admin | Melihat rekap kehadiran seluruh sekolah | Nanti |
| S1 | Semua | Mengganti kata sandi sendiri | Penting |

## Alur pengguna utama

&#91;embedded content: alur kerja · 9 langkah

Guru menyimpan absensi satu kali; dari catatan yang sama, rekap bulanan, portal orang tua, dan tombol WhatsApp langsung tersedia tanpa input ulang.

## Integrasi WhatsApp

Versi 1 memakai tautan klik-untuk-kirim (wa.me): aplikasi menyiapkan pesan, guru menekan tombol, dan WhatsApp terbuka dengan teks yang sudah terisi, tanpa biaya dan tanpa akun tambahan.

| Opsi | Cara kerja | Biaya | Kekurangan |
| --- | --- | --- | --- |
| Tautan wa.me (dipilih untuk versi 1) | Aplikasi membuat tautan berisi nomor orang tua dan teks pesan; guru menekan Kirim di WhatsApp | Gratis | Dikirim satu per satu oleh guru, bukan otomatis |
| API WhatsApp Business resmi | Server mengirim pesan otomatis lewat penyedia resmi | Berbayar per pesan | Perlu verifikasi bisnis dan template pesan yang disetujui; ditunda ke versi berikutnya |
| Gateway tidak resmi | Server mengirim lewat nomor WhatsApp biasa | Murah atau gratis | Nomor sekolah berisiko diblokir; tidak direkomendasikan |

Teks bawaan yang bisa diubah guru sebelum dikirim:

> Assalamualaikum Bapak/Ibu \[nama orang tua\]. Kami informasikan bahwa \[nama siswa\] kelas \[kelas\] pada \[tanggal\] tercatat \[status\]. Mohon konfirmasinya. Terima kasih. Wali kelas \[nama guru\]

Nomor WhatsApp disimpan dalam format internasional (diawali 62, tanpa tanda plus) agar tautan berfungsi.

## Kebutuhan non-fungsional

Karena aplikasi menyimpan data anak dan nomor telepon orang tua, keamanan dan pembatasan akses menjadi syarat utama, di atas kecepatan dan tampilan.

| Aspek | Kebutuhan |
| --- | --- |
| Keamanan akun | Kata sandi disimpan sebagai hash, bukan teks asli; sesi login punya batas waktu; token CSRF di setiap formulir; semua query database memakai parameter |
| Pembatasan akses | Guru hanya melihat kelasnya; orang tua hanya melihat anaknya; setiap permintaan data diperiksa di server, bukan hanya disembunyikan di tampilan |
| Privasi data anak | Simpan data seperlunya (NIS, nama, kelas, nama dan nomor WhatsApp orang tua); acuan hukum: UU Pelindungan Data Pribadi (UU No. 27 Tahun 2022); sekolah menentukan siapa yang berhak mengakses |
| Transport | Dipasang di belakang HTTPS saat dipakai di internet |
| Performa (usulan) | Halaman absensi satu kelas termuat dalam 2 detik pada jaringan 4G |
| Perangkat | Nyaman dipakai dari layar HP selebar 360 px sampai laptop; tombol cukup besar untuk jari |
| Cadangan data | Database dicadangkan otomatis setiap hari dan bisa dipulihkan |
| Jejak perubahan | Setiap absen mencatat siapa yang mengisi dan kapan, termasuk koreksi |
| Bahasa | Antarmuka seluruhnya berbahasa Indonesia |

## Model data

Database terdiri dari lima tabel, dan satu aturan penting: satu siswa hanya punya satu catatan absensi per tanggal.

| Tabel | Kolom utama | Relasi |
| --- | --- | --- |
| pengguna | id, username, password\_hash, nama, peran (admin, guru, ortu), aktif | Dipakai oleh ketiga peran |
| kelas | id, nama (misalnya 1A), tahun\_ajaran, wali\_id | wali\_id mengarah ke pengguna dengan peran guru |
| siswa | id, nis (unik), nama, jenis\_kelamin, kelas\_id, nama\_ortu, no\_wa, aktif | kelas\_id mengarah ke kelas |
| ortu\_siswa | pengguna\_id, siswa\_id | Menghubungkan akun orang tua dengan anak; satu orang tua bisa punya banyak anak |
| absensi | id, siswa\_id, tanggal, status (H, I, S, A), catatan, dicatat\_oleh, dicatat\_pada | siswa\_id mengarah ke siswa; unik per siswa dan tanggal |

**Aturan rekap.** Hari efektif sebuah kelas adalah tanggal yang punya catatan absensi untuk kelas itu, sehingga hari libur yang tidak diisi tidak ikut dihitung. Persentase kehadiran siswa = jumlah Hadir dibagi hari efektif.

## Arsitektur dan teknologi

&#91;embedded content: arsitektur · 3 lapis\]

Peramban guru, orang tua, dan admin berbicara ke satu aplikasi Laravel (tampilan Vue) yang membaca dan menulis database SQLite; pesan WhatsApp tidak lewat server, melainkan dibuka dari HP guru lewat tautan wa.me.

| Komponen | Pilihan | Alasan |
| --- | --- | --- |
| Backend | PHP dan Laravel | Kerangka kerja matang dengan autentikasi, validasi, proteksi CSRF, dan ORM (Eloquent) bawaan; hosting PHP mudah dan murah didapat |
| Database | SQLite | Satu file tanpa server terpisah, cukup untuk satu sekolah; bisa dipindah ke PostgreSQL bila pemakai bertambah |
| Tampilan | Vue 3 dengan Inertia.js dan Vite | Antarmuka interaktif (tombol status absen langsung berubah tanpa muat ulang halaman) tanpa membangun API terpisah; perlu proses build sebelum dipasang |
| Ekspor | Laravel Excel (maatwebsite/excel) dan laravel-dompdf (PDF) | Paket Laravel yang umum dipakai |

## Halaman dan antarmuka

Aplikasi punya tujuh halaman; yang paling sering dipakai adalah Absensi harian, sehingga halaman itu harus bisa diselesaikan dengan satu tangan di HP.

| Halaman | Peran | Isi |
| --- | --- | --- |
| Masuk | Semua | Username dan kata sandi; pesan kesalahan menyebut apa yang salah |
| Absensi harian | Guru | Pilih kelas dan tanggal; daftar siswa dengan empat tombol status; tombol "Semua hadir"; kolom catatan; Simpan. Setelah tersimpan, siswa yang tidak hadir tampil dengan tombol WhatsApp |
| Rekap bulanan | Guru, admin | Pilih kelas dan bulan; tabel Hadir, Izin, Sakit, Alpa dan persentase per siswa; tombol unduh Excel dan PDF |
| Data siswa | Guru | Daftar siswa kelas; tambah, ubah, nonaktifkan; membuat akun orang tua dan menampilkan PIN awal satu kali |
| Portal orang tua | Orang tua | Ringkasan bulan ini, daftar tanggal beserta statusnya, pilihan anak bila lebih dari satu |
| Kelola kelas dan guru | Admin | Membuat kelas, menunjuk wali kelas, membuat dan menonaktifkan akun guru |
| Ganti kata sandi | Semua | Kata sandi lama, kata sandi baru, konfirmasi |

Di HP, daftar siswa tampil satu kolom dengan tombol status berukuran besar; di laptop, tampil sebagai tabel yang lebih lebar dengan rekap di sampingnya.

## Rencana rilis

&#91;embedded content: rencana rilis · 4 tahap, 4 gerbang\]

Tahap berjalan berurutan dan tahap berikutnya dimulai setelah gerbang sebelumnya terpenuhi. Tanggal belum ditetapkan karena menunggu jawaban sekolah atas pertanyaan terbuka di bawah.

## Risiko dan pertanyaan terbuka

Risiko terbesar bukan teknis, melainkan kebiasaan: bila guru tidak mengisi absen tiap hari, rekap dan pesan ke orang tua tidak ada artinya.

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Guru belum terbiasa memakai aplikasi | Absen tidak terisi rutin | Antarmuka sederhana, tombol "Semua hadir", pelatihan singkat sebelum dipakai |
| Sinyal internet sekolah lemah | Absen gagal tersimpan | Halaman ringan, pesan jelas saat gagal menyimpan; mode offline dipertimbangkan di versi berikutnya |
| Nomor WhatsApp orang tua salah atau berubah | Pesan tidak sampai | Validasi format nomor; guru bisa memperbaiki langsung dari halaman siswa |
| Data anak bocor | Pelanggaran privasi | Pembatasan akses per peran, HTTPS, kata sandi ter-hash, cadangan terenkripsi di tempat aman |
| Orang tua lupa PIN | Tidak bisa melihat kehadiran | Guru bisa mereset PIN dari halaman Data siswa |

**Pertanyaan untuk pihak sekolah**

- [ ] Berapa jumlah kelas, siswa, dan guru yang akan memakai aplikasi?
- [ ] Apakah perlu peran admin, atau akun guru cukup dibuat oleh pengembang?
- [ ] Apakah status absensi cukup Hadir, Izin, Sakit, Alpa, atau perlu tambahan seperti Terlambat?
- [ ] Aplikasi dijalankan di mana: hosting berbayar, server sekolah, atau satu komputer di sekolah?
- [ ] Apakah laporan perlu format resmi tertentu, misalnya kop surat dan kolom tanda tangan?
- [ ] Apakah orang tua perlu login sendiri, atau cukup menerima pesan WhatsApp dari guru?
- [ ] Siapa di sekolah yang berwenang menyetujui penggunaan data siswa dan orang tua?
