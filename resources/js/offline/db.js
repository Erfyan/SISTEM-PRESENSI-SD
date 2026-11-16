/**
 * IndexedDB helper untuk mode offline SIPRELINE Absensi SD.
 * Mengelola cache kelas/siswa serta antrean pengisian absen offline.
 */

const DB_NAME = 'sipreline_absensi_db';
const DB_VERSION = 1;

function openDB() {
  return new Promise((resolve, reject) => {
    if (!('indexedDB' in window)) {
      reject(new Error('IndexedDB tidak didukung pada peramban ini.'));
      return;
    }

    const request = indexedDB.open(DB_NAME, DB_VERSION);

    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      
      // Store untuk cache data kelas & siswa terakhir
      if (!db.objectStoreNames.contains('roster_cache')) {
        db.createObjectStore('roster_cache', { keyPath: 'key' });
      }

      // Store untuk antrean absensi offline yang menunggu sinkronisasi
      if (!db.objectStoreNames.contains('offline_queue')) {
        const queueStore = db.createObjectStore('offline_queue', { keyPath: 'id', autoIncrement: true });
        queueStore.createIndex('kelas_tanggal', ['kelas_id', 'tanggal'], { unique: false });
      }
    };

    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

/**
 * Simpan cache roster siswa untuk kelas & tanggal tertentu
 */
export async function cacheRosterData(kelasId, tanggal, data) {
  try {
    const db = await openDB();
    const key = `${kelasId}_${tanggal}`;
    const tx = db.transaction('roster_cache', 'readwrite');
    const store = tx.objectStore('roster_cache');
    store.put({
      key,
      kelas_id: kelasId,
      tanggal,
      data,
      cached_at: new Date().toISOString()
    });
    return new Promise((resolve, reject) => {
      tx.oncomplete = () => resolve(true);
      tx.onerror = () => reject(tx.error);
    });
  } catch (err) {
    console.warn('Gagal menyimpan cache roster offline:', err);
    return false;
  }
}

/**
 * Ambil cache roster siswa saat offline
 */
export async function getCachedRosterData(kelasId, tanggal) {
  try {
    const db = await openDB();
    const key = `${kelasId}_${tanggal}`;
    const tx = db.transaction('roster_cache', 'readonly');
    const store = tx.objectStore('roster_cache');
    const request = store.get(key);

    return new Promise((resolve) => {
      request.onsuccess = () => {
        resolve(request.result ? request.result.data : null);
      };
      request.onerror = () => resolve(null);
    });
  } catch (err) {
    console.warn('Gagal mengambil cache roster offline:', err);
    return null;
  }
}

/**
 * Simpan data absensi ke antrean offline IndexedDB
 */
export async function enqueueOfflineAttendance(payload) {
  const db = await openDB();
  const tx = db.transaction('offline_queue', 'readwrite');
  const store = tx.objectStore('offline_queue');

  // Bersihkan record lama untuk kelas + tanggal yang sama bila ada dalam antrean
  const existingRecords = await getPendingQueue();
  for (const item of existingRecords) {
    if (String(item.kelas_id) === String(payload.kelas_id) && item.tanggal === payload.tanggal) {
      store.delete(item.id);
    }
  }

  const record = {
    kelas_id: payload.kelas_id,
    tanggal: payload.tanggal,
    absensi: payload.absensi,
    dicatat_offline_pada: payload.dicatat_offline_pada || new Date().toISOString(),
    created_at: new Date().toISOString()
  };

  store.add(record);

  return new Promise((resolve, reject) => {
    tx.oncomplete = () => resolve(record);
    tx.onerror = () => reject(tx.error);
  });
}

/**
 * Ambil seluruh antrean absensi offline yang menunggu disinkronkan
 */
export async function getPendingQueue() {
  try {
    const db = await openDB();
    const tx = db.transaction('offline_queue', 'readonly');
    const store = tx.objectStore('offline_queue');
    const request = store.getAll();

    return new Promise((resolve) => {
      request.onsuccess = () => resolve(request.result || []);
      request.onerror = () => resolve([]);
    });
  } catch (err) {
    console.warn('Gagal membaca antrean offline:', err);
    return [];
  }
}

/**
 * Hapus record antrean berdasarkan id
 */
export async function dequeueOfflineAttendance(id) {
  try {
    const db = await openDB();
    const tx = db.transaction('offline_queue', 'readwrite');
    const store = tx.objectStore('offline_queue');
    store.delete(id);

    return new Promise((resolve, reject) => {
      tx.oncomplete = () => resolve(true);
      tx.onerror = () => reject(tx.error);
    });
  } catch (err) {
    console.warn('Gagal menghapus antrean offline:', err);
    return false;
  }
}

/**
 * Hitung jumlah antrean yang belum disinkronkan
 */
export async function countPendingQueue() {
  const items = await getPendingQueue();
  return items.length;
}
