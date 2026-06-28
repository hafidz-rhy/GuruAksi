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
