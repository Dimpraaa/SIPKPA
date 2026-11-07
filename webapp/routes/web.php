<?php

use App\Http\Controllers\AngkatanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KhsController;
use App\Http\Controllers\LaporanModelController;
use App\Http\Controllers\LaporanPesertaController;
use App\Http\Controllers\ModelMlController;
use App\Http\Controllers\PenilaianImportController;
use App\Http\Controllers\PesertaController;
use App\Http\Controllers\PrediksiController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'auth.login')->middleware('guest');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'role:admin_akademik,manajemen'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/angkatan', [AngkatanController::class, 'index'])->name('angkatan.index');
    Route::get('/peserta', [PesertaController::class, 'index'])->name('peserta.index');
    Route::get('/eksperimen-model', [ModelMlController::class, 'index'])->name('model.index');
    Route::get('/prediksi', [PrediksiController::class, 'index'])->name('prediksi.index');
    Route::get('/laporan/berisiko', [LaporanPesertaController::class, 'berisiko'])->name('laporan.berisiko');
    Route::get('/laporan/berisiko/{format}', [LaporanPesertaController::class, 'exportBerisiko'])->whereIn('format', ['pdf', 'excel'])->name('laporan.berisiko.export');
    Route::get('/laporan/rekap', [LaporanPesertaController::class, 'rekap'])->name('laporan.rekap');
    Route::get('/laporan/rekap/{format}', [LaporanPesertaController::class, 'exportRekap'])->whereIn('format', ['pdf', 'excel'])->name('laporan.rekap.export');
    Route::get('/laporan/model', [LaporanModelController::class, 'index'])->name('laporan.model');
    Route::get('/laporan/model/pdf', [LaporanModelController::class, 'pdf'])->name('laporan.model.pdf');
    Route::get('/peserta/{peserta}/khs', KhsController::class)->name('peserta.khs');

    Route::middleware('role:admin_akademik')->group(function (): void {
        Route::resource('angkatan', AngkatanController::class)->except(['index', 'show'])->parameters(['angkatan' => 'angkatan']);
        Route::get('/penilaian/import', [PenilaianImportController::class, 'create'])->name('penilaian.import');
        Route::post('/penilaian/import', [PenilaianImportController::class, 'store'])->name('penilaian.import.store');
        Route::get('/penilaian/template', [PenilaianImportController::class, 'template'])->name('penilaian.template');
        Route::resource('peserta', PesertaController::class)->except(['index', 'show'])->parameters(['peserta' => 'peserta']);
        Route::post('/eksperimen-model', [ModelMlController::class, 'store'])->name('model.store');
        Route::patch('/eksperimen-model/{model}/aktifkan', [ModelMlController::class, 'activate'])->name('model.activate');
        Route::post('/peserta/{peserta}/prediksi', [PrediksiController::class, 'individual'])->name('prediksi.individual');
        Route::post('/angkatan/{angkatan}/prediksi', [PrediksiController::class, 'angkatan'])->name('prediksi.angkatan');
    });
    Route::get('/peserta/{peserta}', [PesertaController::class, 'show'])->name('peserta.show');
});
