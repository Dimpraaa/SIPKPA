# Checkpoint redesign SIPKPA

## Arah visual dan audit (Fase 1)

- Referensi DesignPrompts: URL tidak disertakan dalam konteks. Menggunakan arah cadangan editorial enterprise, **Premium Academic Analytics Workspace**.
- Stack terverifikasi: Laravel 11.57, Blade, Tailwind CSS 4, Vite 8, Chart.js 4; DomPDF 3.1 dan PhpSpreadsheet 5. Tidak memerlukan dependency baru.
- Working tree sudah berisi perubahan lokal pada controller, ekspor, tampilan, pagination, dan pengujian sebelum redesign. Perubahan tersebut menjadi baseline; jangan melakukan reset working tree.
- `resources/css/app.css` menjadi sumber token dan komponen umum. Stylesheet domain yang sudah ada dipertahankan untuk struktur khusus halaman, tanpa tema terpisah.
- Palet: tinta hijau `#102f28`, teal aksi `#176b56`, kanvas `#f2f4ef`, permukaan putih, border `#dce3dc`, teks `#182e24`. Status aktual gagal memakai merah; estimasi risiko memakai amber; informasi memakai biru terbatas.
- Tipografi: Instrument Sans existing (dibundel Vite), Segoe UI/system sebagai fallback, Georgia untuk judul editorial. Angka menggunakan tabular numerals.
- Spacing berbasis 4 px; panel 24 px, control minimal 44 px, radius control 6 px/panel 10 px. Elevation hanya untuk elemen mengambang. Breakpoint 640/768/1024/1280/1536 px.
- Audit grafik: Dashboard sudah memiliki agregat backend, pemisahan aktual/prediksi, dan filter angkatan. Rata-rata harus mempertahankan nol dan null berbeda, skala 0–100. Status aktual membutuhkan jumlah + persentase. Radar menggunakan dua satuan (kehadiran %, nilai /100) yang harus diperjelas. Confusion matrix eksperimen belum memiliki sumbu kelas yang jelas. Rekap belum memiliki visual perbandingan.
- Komponen bersama: button, field, status, panel, tabel, pagination, tabs, chart state, metric strip, feature bars, dan matrix. Seluruh desain tetap menggunakan bahasa Indonesia.

## Status fase

1. Design system: selesai. `npm run build` lulus; Vite memberi peringatan fontaine opsional dari konfigurasi font existing.
2. Layout & Dashboard: selesai. Sidebar tinta hijau, topbar kontekstual, header editorial, skip link, navigasi mobile dengan Escape/focus trap. Grafik nilai berlabel langsung (0–100), distribusi aktual batang jumlah/persentase, donut prediksi terpisah dengan penyebut terprediksi. Confusion matrix dan feature bars menggunakan data model aktif. Build, 7 tes Dashboard (105 assertions), dan 5 tes grafik lulus.
3. Halaman operasional: selesai. Angkatan memakai tabel dengan identitas kode/tindakan bersama; formulir peserta/angkatan dan impor memiliki hierarki terstruktur. Detail peserta membedakan status aktual, nilai terkini, dan riwayat. Radar berlabel satuan dan ambang simulasi. Prediksi memiliki indikator kesiapan dan estimasi; dialog menggunakan tema yang sama. Peringatan dini memakai konteks amber serta indikator terpisah. 41 tes operasional (285 assertions), 12 tes pagination, 5 tes grafik lulus. Build lulus.
4. Analitik, pelaporan & login: selesai. Eksperimen menampilkan banner model aktif global, konfigurasi, metrik berdefinisi, matrix kelas aktual/prediksi, dan feature bars. Riwayat membandingkan parameter/metrik berdampingan. Rekap memiliki grafik perbandingan seluruh angkatan dan rincian nilai/penyebut aktual; filter dan ekspor peserta tetap sesuai cakupannya. Login memakai masthead institusional dan formulir yang nyaman. Penambahan controller hanya data presentasi read-only. Build, 10 tes alur model/autentikasi/agregat rekap (138 assertions), dan 7 tes grafik lulus.
5. QA: selesai. Pengujian penuh lulus setelah penyempurnaan bersama; overflow rekap yang ditemukan saat browser QA diperbaiki dengan containing block pada tabel dan label skor. Seluruh fase memiliki implementasi yang dapat dilanjutkan dari checkpoint ini.

## Verifikasi akhir

- `php artisan test --compact`: **70 tes lulus, 713 assertions**. Termasuk autentikasi/otorisasi, CRUD akademik, impor, filter/pagination, prediksi, kontrak alur model, dan ekspor PDF/Excel.
- `node --test tests/dashboard-charts.test.js tests/table-pagination.test.js`: **19 tes lulus**. Termasuk rendering Chart.js, label langsung, nol vs null, komposisi prediksi, perbandingan rekap, fallback grafik, dan pagination asinkron.
- `npm run build`: lulus. Peringatan existing `fontaine` opsional tetap tidak menghambat build; tidak menambahkan dependency.
- `php vendor/bin/pint --dirty --format agent`: passed.
- `php artisan view:cache --no-interaction`: seluruh Blade berhasil dikompilasi.
- `git diff --check`: bersih.
- Browser QA menggunakan Chrome headless yang sudah terpasang, melalui CDP tanpa dependency baru. Seluruh halaman utama, tambah angkatan/peserta, impor, dan detail peserta diperiksa pada desktop 1440 px dan mobile 390 px. Halaman analitik/form juga diperiksa pada 320, 768, 900, dan 1024 px.
- Tidak ada overflow horizontal pada viewport halaman yang diperiksa; tabel lebar tetap dapat digulir di dalam area tabel. Grafik Dashboard, rekap, dan radar berhasil dirender tanpa error JavaScript.
- Filter peserta dan perpindahan halaman asinkron diverifikasi melalui browser. Dialog prediksi dibuka/ditutup dengan Escape. Drawer mobile mengatur `aria-expanded`, focus trap (Tab dan Shift+Tab), konten inert, dan pengembalian fokus; resize ke desktop menutup drawer dan memulihkan konten. Login/logout berhasil. Detail dan formulir ubah peserta juga diperiksa pada 320/390 px.
- Screenshot QA dan skrip browser sementara disimpan di direktori temp OpenCode di luar repository. Browser dan server sementara QA telah ditutup.

## Peta implementasi

- Token/komponen: `resources/css/app.css`, stylesheet domain existing, `resources/views/components/{layout,status,score}.blade.php`, dan `resources/views/laporan/tabs.blade.php`.
- Grafik: `resources/js/{chart-theme,dashboard-charts,participant-radar,cohort-comparison,app}.js`.
- Halaman: Dashboard, Angkatan (daftar/form), Peserta (daftar melalui tema bersama, detail/form/impor), Prediksi (daftar/dialog), Peringatan Dini (daftar/dialog), Eksperimen, Rekapitulasi, Riwayat Evaluasi, Login, dan 403. Layout PDF bersama mengikuti palet yang sama.
- Penambahan data presentasi read-only: `DashboardController`, `LaporanPesertaController::rekap`, dan `ModelMlController::index`. Tidak mengubah endpoint tulis, threshold, dataset, pelatihan, prediksi, atau skema database.
- Tes grafik/agregat: `tests/dashboard-charts.test.js`, `tests/Feature/DashboardVisualizationTest.php`, dan `tests/Feature/ReportExportTest.php`.

## Hal yang masih bergantung pada masukan

- URL DesignPrompts belum tersedia, sehingga brief eksternal belum dibaca. Arah visual cadangan dari instruksi pengguna telah diterapkan. Tidak ada fase implementasi yang tertunda.

## Batas data

Status aktual, indikator akademik, dan prediksi Random Forest harus selalu dibedakan. Nilai 70 adalah asumsi pemodelan; kehadiran 80% memiliki rujukan PERADI dan belum diverifikasi sebagai kebijakan internal Propindo. Data penelitian merupakan simulasi. Probabilitas bukan jaminan kelulusan. Jangan mengubah threshold, dataset, model, migrasi, API, atau logika pelatihan/prediksi untuk desain.
