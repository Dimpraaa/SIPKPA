# SIPKPA Propindo

**Sistem Informasi Prediksi Kelulusan Peserta Pendidikan Khusus Profesi Advokat (PKPA)** adalah aplikasi internal Advokat Propindo untuk mengelola angkatan dan peserta, mencatat penilaian akademik, menjalankan prediksi kelulusan dengan Random Forest, serta menyusun laporan akademik.

## Fitur

- Pengelolaan angkatan dan data peserta PKPA.
- Pencatatan dan impor penilaian peserta dari CSV atau Excel.
- Prediksi kelulusan individual maupun per angkatan melalui layanan machine learning.
- Ringkasan data akademik, evaluasi model, dan peringatan dini.
- Laporan rekapitulasi, ekspor PDF/Excel, serta Kartu Hasil Studi (KHS) PDF.
- Hak akses admin akademik dan manajemen.

## Teknologi

- **Aplikasi web:** Laravel 11, PHP 8.5, Blade, Tailwind CSS 4, dan Vite.
- **Layanan machine learning:** FastAPI, scikit-learn, pandas, NumPy, dan joblib.
- **Database:** MySQL.

## Prasyarat

- PHP **8.5** dengan ekstensi yang diperlukan Laravel dan PhpSpreadsheet.
- Composer.
- Node.js dan npm.
- Python 3.10 atau lebih baru.
- MySQL.

## Menjalankan di Windows

`start-dev.bat` menjalankan Laravel dan FastAPI sekaligus. Siapkan aplikasi web, database, virtual environment ML, dan aset frontend terlebih dahulu.

### 1. Siapkan database dan Laravel

Buat database MySQL bernama `db_sipkpa_propindo`, lalu jalankan:

```powershell
cd webapp
composer install
Copy-Item .env.example .env
```

Atur `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` pada `webapp/.env` sesuai konfigurasi MySQL Anda. Konfigurasi layanan ML bawaan adalah `ML_SERVICE_URL=http://127.0.0.1:8001`.

Kemudian buat kunci aplikasi, jalankan migrasi dan seeder, serta siapkan frontend:

```powershell
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```

Seeder menyediakan akun berikut untuk pengembangan lokal:

| Peran | Email | Kata sandi |
|---|---|---|
| Admin akademik | `admin@propindo.id` | `password123` |
| Manajemen | `manajemen@propindo.id` | `password123` |

Kredensial ini hanya untuk lingkungan pengembangan lokal. Ganti atau hapus sebelum menggunakan lingkungan bersama maupun produksi.

### 2. Siapkan layanan machine learning

```powershell
cd ml-service
py -3 -m venv venv
.\venv\Scripts\python.exe -m pip install -r requirements.txt
```

Kembali ke root repository dan mulai aplikasi:

```powershell
cd ..
.\start-dev.bat
```

Secara default aplikasi tersedia pada:

- Laravel: <http://127.0.0.1:8000>
- Health check FastAPI: <http://127.0.0.1:8001/health>
- Dokumentasi FastAPI: <http://127.0.0.1:8001/docs>

Port Laravel dan FastAPI dapat diatur dengan `SIPKPA_LARAVEL_PORT` dan `SIPKPA_ML_PORT`. Skrip startup meneruskan port ML sebagai `ML_SERVICE_URL` kepada proses Laravel.

Untuk menjalankan Laravel dan FastAPI secara terpisah, gunakan dua terminal dari root repository:

```powershell
cd ml-service
.\venv\Scripts\python.exe -m uvicorn main:app --host 127.0.0.1 --port 8001
```

```powershell
cd webapp
php artisan serve --host=127.0.0.1 --port=8000
```

## Menjalankan pengujian

Pengujian aplikasi Laravel:

```powershell
cd webapp
php artisan test
```

Pengujian frontend dan pagination:

```powershell
cd webapp
node --test tests/*.test.js
```

Pengujian layanan machine learning:

```powershell
cd ml-service
.\venv\Scripts\python.exe -m unittest
```

Untuk membangun aset frontend setelah perubahan:

```powershell
cd webapp
npm run build
```

## Kontrak layanan machine learning

FastAPI menyediakan endpoint `GET /health`, `POST /api/train`, dan `POST /api/predict`. Laravel berkomunikasi dengan layanan melalui `ML_SERVICE_URL`. Model yang telah dilatih disimpan pada `ml-service/models/`.

## Catatan konfigurasi

- Jangan commit `webapp/.env`, kredensial database, atau rahasia aplikasi.
- Pastikan MySQL berjalan sebelum migrasi dan sebelum menggunakan aplikasi.
- Untuk menjalankan proses lokal, `start-dev.bat` memerlukan PHP 8.5 pada `PATH`, dependensi Composer dan Python yang telah terpasang, serta berkas `.env`.
