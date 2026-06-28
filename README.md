# AKSI GURU - Aplikasi Administrasi Guru

Aplikasi pengelolaan administrasi guru modern dengan fitur presensi QR Code dan jadwal mengajar terintegrasi.

## 🛠️ Tech Stack

- **Backend:** CodeIgniter 4 (PHP) - REST API
- **Frontend:** Vue 3 + Vite + Tailwind CSS
- **Database:** MariaDB/MySQL
- **Auth:** JWT (firebase/php-jwt)
- **QR:** chillerlan/php-qrcode (backend), vue-qrcode-reader (frontend)
- **Icons:** Phosphor Icons / Heroicons
- **reCAPTCHA:** Google reCAPTCHA v2

## 📁 Struktur Monorepo

```
AksiGuruNew/
├── api/                    # Backend CodeIgniter 4
│   ├── app/
│   │   ├── Controllers/Api/   # REST API Controller
│   │   ├── Models/            # Database Models
│   │   ├── Filters/           # Middleware (Auth, Maintenance)
│   │   └── Database/Migrations/  # Migration files
│   ├── writable/              # Cache, logs, patches backup
│   └── VERSION                # Nomor versi aplikasi
├── web/                    # Frontend Vue 3 + Vite
│   ├── src/
│   │   ├── layouts/        # DesktopLayout + MobileLayout
│   │   ├── views/          # Page components
│   │   ├── stores/         # Pinia stores
│   │   ├── router/         # Vue Router
│   │   └── services/       # Axios API
│   └── package.json
├── database/               # Shared DB resources
├── patches/                # Output folder untuk patch ZIP (dev)
├── build-patch.js          # Script builder patch (dev)
├── docs/                   # Documentation
└── README.md
```

## 🚀 Quick Start

### Prasyarat
- PHP 8.1+
- Node.js 18+
- MariaDB/MySQL
- Composer

### Backend Setup

```bash
cd api
composer install
cp env .env
# Edit .env: database config
php spark migrate
php spark db:seed DatabaseSeeder
php spark serve --port 8080
```

**Default credentials:**
| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| Kamad | kamad | kamad123 |
| Guru | guru1 | guru123 |

### Frontend Setup

```bash
cd web
npm install
npm run dev
# Open http://localhost:5173
```

## 📱 Fitur Utama

### 🏠 Dashboard
- Statistik ringkasan per role (admin, guru, kamad)

### 👨‍🏫 Data Guru
- CRUD guru, profil lengkap
- Riwayat wali kelas per tahun pelajaran

### 👥 Data Siswa
- CRUD siswa, data orang tua
- Fitur naik kelas massal

### 📅 Jadwal Mengajar
- Jadwal per guru, kelas, hari
- Generate QR Code per sesi untuk presensi

### 📋 Presensi Guru (QR + Manual)
- Guru scan QR code untuk absen
- Admin bisa input manual
- Validasi jadwal & waktu (±15 menit)

### 📖 Jurnal Mengajar
- Catatan harian guru
- Terintegrasi jadwal
- Presensi siswa (default hadir, cukup pilih yang tidak hadir)

### 📝 Agenda Guru (Opsional)
- Agenda harian guru (pending/selesai)

### 🎯 Penugasan Guru (Admin)
- Penempatan mata pelajaran pada guru per tahun pelajaran
- Referensi otomatis untuk dropdown mapel di Jadwal
- CRUD + batch assign

### 🔄 Patch System (Admin)
- Upload & apply patch ZIP langsung dari panel admin
- Auto-backup file & database sebelum apply
- Rollback ke versi sebelumnya
- Maintenance mode toggle

### ⚙️ Pengaturan Aplikasi
- Detail aplikasi (versi, environment, framework)
- Maintenance mode toggle
- Patch System (upload, preview, apply, history, rollback)
- Identitas sekolah (nama, jenjang)
- Google reCAPTCHA (site key, secret key, toggle)

### 🎨 UI/UX
- **Tema:** Tosca (#0D9488) + Orange (#F97316)
- **Dark mode** support
- **Mobile SPA** dengan Bottom Navigation per role
- **Desktop** dengan Sidebar + Topbar

## 👥 Role-Based Access Control

| Modul | Admin | Guru | Kamad |
|-------|-------|------|-------|
| **Dashboard** | ✅ | ✅ | ✅ |
| **Data Guru** | ✅ CRUD | ❌ | ✅ View |
| **Data Siswa** | ✅ CRUD | ❌ | ✅ View |
| **Users** | ✅ CRUD | ❌ | ❌ |
| **Jadwal Index** | ✅ Kelas cards → CRUD | ✅ Tabel jadwal pribadi | ✅ Kelas cards → View only |
| **Jadwal Detail** | ✅ Full CRUD (tambah/edit/hapus) | ❌ | ✅ View only |
| **Kehadiran Index** | ✅ Semua guru + Manual | ✅ Kartu sendiri + Scan QR | ✅ Semua guru (View only) |
| **Kehadiran Detail** | ✅ + Manual button | ✅ View only | ✅ View only |
| **Jurnal** | ✅ Semua guru | ✅ Jurnal sendiri | ✅ Semua guru |
| **Penilaian** | ✅ | ✅ | ❌ |
| **Agenda** | ✅ | ✅ | ❌ |
| **Penugasan** | ✅ CRUD | ❌ | ❌ |
| **Tahun Pelajaran** | ✅ CRUD | ❌ | ❌ |
| **Mata Pelajaran** | ✅ CRUD | ❌ | ❌ |
| **Kelas** | ✅ CRUD | ❌ | ❌ |
| **Jam Pelajaran** | ✅ CRUD | ❌ | ❌ |
| **Presensi (Pengaturan)** | ✅ | ❌ | ❌ |
| **Patch System** | ✅ | ❌ | ❌ |
| **Pengaturan Aplikasi** | ✅ | ❌ | ❌ |
| **Profil** | ✅ | ✅ | ✅ |

## 📋 Schema Database

### Tabel:
1. `users` - Akun login (admin, guru, kamad)
2. `mst_thn_pelajaran` - Tahun pelajaran (tgl_mulai - tgl_berakhir)
3. `mst_guru` - Data guru
4. `riwayat_walikelas` - Riwayat wali kelas per guru + tahun pelajaran
5. `mst_siswa` - Data siswa
6. `mst_mapel` - Mata pelajaran
7. `mst_kelas` - Kelas
8. `mst_jam` - Jam pelajaran
9. `jadwal_mengajar` - Jadwal + token QR
10. `penugasan_guru` - Penugasan mapel ke guru per tahun
11. `presensi_guru` - Presensi guru (QR/manual)
12. `jurnal_mengajar` - Jurnal harian
13. `presensi_siswa` - Presensi siswa (default 'hadir')
14. `agenda_guru` - Agenda opsional
15. `pengaturan` - Settings (reCAPTCHA, maintenance, identitas, dll)
16. `patch_history` - Log patching

## 🔑 API Endpoints

### Auth
| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| POST | /api/auth/login | - | Login |
| POST | /api/auth/refresh | - | Refresh token |
| GET | /api/auth/me | JWT | Profil user |

### Master Data
| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| GET | /api/tahun-pelajaran | JWT | List TP |
| POST | /api/tahun-pelajaran | JWT | Tambah TP |
| GET | /api/guru | JWT | List guru |
| POST | /api/guru | JWT | Tambah guru |
| GET | /api/siswa | JWT | List siswa |
| POST | /api/siswa/naik-kelas | JWT | Naik kelas massal |
| GET | /api/mapel | JWT | List mapel |
| GET | /api/kelas | JWT | List kelas |
| GET | /api/jam | JWT | List jam pelajaran |

### Jadwal
| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| GET | /api/jadwal | JWT | List jadwal (?guru_id=, ?kelas_id=, ?tahun_pelajaran_id=) |
| GET | /api/jadwal/kelas/:id | JWT | Jadwal per kelas (dengan join) |
| POST | /api/jadwal/assign | JWT | Assign/tambah jadwal |
| PUT | /api/jadwal/:id | JWT | Update jadwal |
| DELETE | /api/jadwal/:id | JWT | Hapus jadwal |

### Kehadiran
| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| GET | /api/kehadiran | JWT | Rekap kehadiran semua guru |
| GET | /api/kehadiran/guru/:id | JWT | Riwayat kehadiran per guru |
| POST | /api/kehadiran | JWT | Input manual (admin) |
| POST | /api/kehadiran/scan | JWT | Presensi via QR |

### Penugasan (Admin)
| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| GET | /api/penugasan | JWT | List penugasan (?tahun_pelajaran_id=, ?guru_id=) |
| GET | /api/penugasan/mapel-oleh-guru/:id | JWT | Mapel yang diampu guru tertentu |
| POST | /api/penugasan | JWT | Tambah penugasan |
| POST | /api/penugasan/batch | JWT | Batch assign mapel ke guru |
| PUT | /api/penugasan/:id | JWT | Update penugasan |
| DELETE | /api/penugasan/:id | JWT | Hapus penugasan |

### Patch System (Admin)
| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| GET | /api/patch/status | JWT | Versi + maintenance + latest patch |
| GET | /api/patch/history | JWT | Riwayat patch |
| POST | /api/patch/upload | JWT | Upload ZIP patch |
| POST | /api/patch/apply | JWT | Apply patch |
| POST | /api/patch/rollback | JWT | Rollback ke versi sebelumnya |
| POST | /api/patch/maintenance | JWT | Toggle maintenance mode |

### Lainnya
| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| GET | /api/jurnal | JWT | List jurnal |
| POST | /api/jurnal | JWT | Tambah jurnal |
| GET | /api/agenda | JWT | List agenda |
| POST | /api/agenda | JWT | Tambah agenda |
| GET | /api/pengaturan | JWT | List pengaturan |
| POST | /api/pengaturan | JWT | Update pengaturan |
| GET | /api/dashboard/admin | JWT | Stats dashboard admin |
| GET | /api/dashboard/guru | JWT | Stats dashboard guru |
| GET | /api/dashboard/kamad | JWT | Stats dashboard kamad |

---

## 🔄 Patch System — Panduan Development

### Konsep

Fitur Patch memungkinkan update aplikasi di production tanpa akses SSH/FTP. Developer membuat ZIP patch, lalu Admin meng-upload dan meng-apply langsung dari panel admin.

### Struktur ZIP Patch

```
aksi-guru-patch-v1.2.0.zip
├── manifest.json               # Metadata patch (wajib)
├── api/                        # File backend (PHP)
│   ├── VERSION                 # Nomor versi baru
│   ├── app/
│   │   ├── Controllers/...
│   │   ├── Models/...
│   │   ├── Database/Migrations/...  # Migrasi baru
│   │   └── Filters/...
│   └── Config/Routes.php
└── web/
    └── dist/                   # Build frontend (Vite output)
        ├── index.html
        └── assets/
            ├── index-abc123.js
            └── index-abc123.css
```

### manifest.json

```json
{
  "version": "1.2.0",
  "previous_version": "1.1.0",
  "release_date": "2026-06-26",
  "description": "Fix jadwal guru & tambah fitur penugasan",
  "file_count": 85,
  "min_app_version": "1.0.0",
  "migrations": ["2025-01-13-000001_CreatePatchHistoryTable.php"],
  "sql": [],
  "checksums": {
    "api/app/Controllers/Api/Jadwal.php": "sha256...",
    "web/dist/assets/index-abc123.js": "sha256..."
  }
}
```

### Cara Membuat Patch (Developer)

Gunakan script **`build-patch.js`** yang sudah disediakan di root project:

```bash
node build-patch.js <versi_baru> <versi_sebelumnya> "deskripsi patch"
```

**Contoh:**
```bash
node build-patch.js 1.2.0 1.1.0 "Fix bug jadwal guru & tambah fitur penugasan"
node build-patch.js 1.3.0 1.2.0 "Tambah modul penilaian & perbaikan UI mobile"
```

**Yang dilakukan script:**
1. `npm run build` di folder `web/` — membuild frontend Vue
2. Mengumpulkan semua file `api/` + `web/dist/` (skip `vendor`, `node_modules`, `writable`)
3. Deteksi migration baru (via `git diff` atau full scan)
4. Generate `manifest.json` dengan checksum SHA256
5. Bikin ZIP di folder `patches/aksi-guru-patch-vX.Y.Z.zip`
6. Tampilkan ringkasan

**Output:**
```
✨ Patch v1.2.0 siap!
  File : aksi-guru-patch-v1.2.0.zip
  Size : 245.3 KB
  Files: 47 + manifest

  📤 Upload ZIP ini melalui:
     Pengaturan → Aplikasi → Patch System
```

### Cara Apply Patch (Admin)

1. Login sebagai **Admin**
2. Buka **Pengaturan → Aplikasi**
3. Drag & drop file ZIP ke area upload, lalu klik **Upload & Preview**
4. Review manifest (versi, deskripsi, migrasi)
5. Klik **✅ Apply Patch** → konfirmasi
6. Sistem akan:
   - Backup semua file + database
   - Aktifkan maintenance mode
   - Ekstrak file
   - Jalankan migrasi
   - Update versi
   - Clear cache
   - Nonaktifkan maintenance mode

### Rollback (Admin)

Jika terjadi masalah setelah patch:
1. Buka **Pengaturan → Aplikasi**
2. Scroll ke **Riwayat Patch**
3. Klik tombol **Rollback** pada versi yang ingin dikembalikan
4. Sistem akan restore file + database dari backup otomatis

**Catatan penting:**
- Backup tersimpan di `api/writable/patches/backups/vX.X.X_timestamp/`
- Backup otomatis dihapus oleh sistem (untuk hemat space, hanya backup terbaru disimpan)
- Rollback hanya bisa ke versi yang pernah sukses di-apply

### Workflow Rilis Patch (Best Practice)

```
┌────────────────┐
│ 1. Development │  git checkout -b feature/v1.2.0
│    & Testing   │  Coding + test di local
│                │  Update api/VERSION → "1.2.0"
│                │  git add, git commit
└───────┬────────┘
        │
┌───────▼────────┐
│ 2. Build       │  git checkout main && git merge feature/v1.2.0
│    Patch       │  git tag v1.2.0
│                │  node build-patch.js 1.2.0 1.1.0 "..."
└───────┬────────┘
        │
┌───────▼────────┐
│ 3. Deploy      │  Upload patches/aksi-guru-patch-v1.2.0.zip
│    via Panel   │  melalui Pengaturan → Aplikasi → Patch System
└───────┬────────┘
        │
┌───────▼────────┐
│ 4. Verify      │  Cek versi di Detail Aplikasi
│                │  Cek fitur baru berjalan normal
│                │  Jika gagal → Rollback
└────────────────┘
```

### Tips Keamanan Patch

- **Tag Git**: Selalu tag setiap versi rilis (`git tag v1.2.0`)
- **Test di staging**: Uji patch di environment staging sebelum production
- **Backup manual**: Sebelum apply patch besar, export database manual sebagai cadangan ekstra
- **Changelog**: Catat perubahan di `description` manifest.json
- **SQL opsional**: Jika perlu modifikasi data, isi `sql` array di manifest sebelum build

---

## 📄 Lisensi

Internal - AKSI GURU © 2025
