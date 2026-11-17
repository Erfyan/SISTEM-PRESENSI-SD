const fs = require('fs');
const path = require('path');

const rootDir = path.resolve(__dirname, '..');
const distDir = path.join(rootDir, 'dist');
const publicDir = path.join(rootDir, 'public');

// Hapus dist lama jika ada
if (fs.existsSync(distDir)) {
    fs.rmSync(distDir, { recursive: true, force: true });
}
fs.mkdirSync(distDir, { recursive: true });

// Copy semua isi public ke dist
if (fs.existsSync(publicDir)) {
    fs.cpSync(publicDir, distDir, { recursive: true });
}

// Hapus file backend PHP & Apache dari dist agar tidak bocor sebagai teks statis
const removeFiles = ['index.php', '.htaccess'];
for (const file of removeFiles) {
    const target = path.join(distDir, file);
    if (fs.existsSync(target)) {
        fs.unlinkSync(target);
    }
}

// Pastikan manifest PWA ada di root /manifest.webmanifest maupun /build/manifest.webmanifest
const buildManifest = path.join(publicDir, 'build', 'manifest.webmanifest');
if (fs.existsSync(buildManifest)) {
    fs.copyFileSync(buildManifest, path.join(distDir, 'manifest.webmanifest'));
}

console.log('✅ [Vercel Build] Berhasil menyalin aset statis public ke dist untuk Vercel CDN.');
