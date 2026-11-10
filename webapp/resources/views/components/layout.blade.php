@props(['title', 'description' => 'Pengelolaan akademik Pendidikan Khusus Profesi Advokat.', 'page' => null])
@php
    $section = request()->routeIs('model.*', 'prediksi.*') ? 'Analitik & Pemodelan' : (request()->routeIs('laporan.*') ? 'Pelaporan Akademik' : 'Ruang Akademik');
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · SIPKPA Propindo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @class(['workspace-page', 'prediksi-page' => $page === 'prediksi', 'peringatan-page' => $page === 'peringatan', 'dashboard-page' => $page === 'dashboard'])>
    <a class="skip-link" href="#main-content">Langsung ke konten utama</a>
    <header class="workspace-mobile-header">
        <a href="{{ route('dashboard') }}" class="brand-wordmark">SIPKPA <span>PROPINDO</span></a>
        <button type="button" data-menu-toggle aria-controls="sidebar" aria-expanded="false" class="mobile-menu-button">
            <x-icon name="menu" /> Menu
        </button>
    </header>

    <button type="button" data-menu-backdrop hidden class="workspace-backdrop" aria-label="Tutup navigasi" tabindex="-1"></button>
    <aside id="sidebar" class="workspace-sidebar" tabindex="-1" aria-label="Navigasi SIPKPA">
        <div class="sidebar-brand">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3" aria-label="SIPKPA Propindo · Dasbor">
                <span class="brand-mark" aria-hidden="true">S.</span>
                <span class="brand-wordmark">SIPKPA <span>PROPINDO</span></span>
            </a>
            <button type="button" data-menu-close class="sidebar-close" aria-label="Tutup navigasi">×</button>
        </div>
        <p class="sidebar-caption">Pendidikan Khusus<br>Profesi Advokat</p>

        <p class="sidebar-section-label">Ruang Akademik</p>
        <nav class="sidebar-nav" aria-label="Navigasi utama">
            @foreach ([
                'dashboard' => ['Dasbor', 'dashboard'],
                'angkatan.index' => ['Angkatan PKPA', 'calendar'],
                'peserta.index' => ['Peserta & Penilaian', 'users'],
                'model.index' => ['Eksperimen Model', 'model'],
                'prediksi.index' => ['Prediksi Kelulusan', 'prediction'],
            ] as $route => [$label, $icon])
                @php($active = request()->routeIs($route === 'dashboard' ? $route : explode('.', $route)[0].'.*'))
                <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
                    class="sidebar-link">
                    <x-icon :name="$icon" />
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </nav>

        <p class="sidebar-section-label">Pelaporan</p>
        <nav class="sidebar-nav mb-8" aria-label="Navigasi laporan">
            @foreach ([
                'laporan.berisiko' => ['Peringatan Dini', 'warning'],
                'laporan.rekap' => ['Rekapitulasi Angkatan', 'report'],
                'laporan.model' => ['Riwayat Evaluasi Model', 'clipboard'],
            ] as $name => [$label, $icon])
                @php($active = request()->routeIs($name))
                <a href="{{ route($name) }}" @if ($active) aria-current="page" @endif
                    class="sidebar-link">
                    <x-icon :name="$icon" />
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </nav>

        <div class="sidebar-account">
            <div class="flex min-w-0 items-center gap-3">
                <span class="account-initial" aria-hidden="true">{{ mb_substr(auth()->user()->nama_lengkap, 0, 1) }}</span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold" title="{{ auth()->user()->nama_lengkap }}">{{ auth()->user()->nama_lengkap }}</p>
                    <p class="mt-1 text-xs">{{ auth()->user()->role === 'admin_akademik' ? 'Admin Akademik' : 'Manajemen · Akses baca' }}</p>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST" class="mt-4">
                @csrf
                <button type="submit" class="sidebar-logout">
                    <x-icon name="logout" /> Keluar dari akun
                </button>
            </form>
        </div>
    </aside>

    <div class="workspace-content" data-workspace-content>
        <header class="workspace-topbar">
            <div class="topbar-breadcrumb"><span>Advokat Propindo</span><span aria-hidden="true">/</span><strong>{{ $section }}</strong></div>
            <div class="topbar-cohort">
                <x-icon name="calendar" />
                <div class="min-w-0">
                    <p class="text-[10px] text-slate-500">Angkatan berjalan terbaru</p>
                    <p class="max-w-64 truncate text-xs font-semibold" title="{{ $angkatanAktif?->nama_angkatan }}">{{ $angkatanAktif?->nama_angkatan ?? 'Belum ada angkatan berjalan' }}</p>
                    @if ($angkatanAktif)
                        <p class="text-xs text-slate-500 tabular-nums">{{ $angkatanAktif->tanggal_mulai->format('d/m/Y') }} – {{ $angkatanAktif->tanggal_selesai->format('d/m/Y') }}</p>
                    @endif
                </div>
            </div>
        </header>

        <main id="main-content" tabindex="-1" class="workspace-main">
            <div class="page-header">
                <div class="min-w-0">
                    <p class="eyebrow mb-2">{{ $section }}</p>
                    <h1 class="page-title">{{ $title }}</h1>
                    <p class="page-description">{{ $description }}</p>
                </div>
                @isset($actions)<div class="page-actions">{{ $actions }}</div>@endisset
            </div>
            @if (session('success'))
                <div role="status" class="context-note mb-6">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div role="alert" class="mb-6 border-l-2 border-rose-700 bg-rose-50 px-5 py-4 text-sm text-rose-900">
                    <p class="font-bold">Permintaan belum dapat diproses</p>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif
            <p data-pagination-status role="status" aria-live="polite" aria-atomic="true" class="sr-only"></p>
            {{ $slot }}
            <footer class="workspace-footer"><span>© {{ date('Y') }} Advokat Propindo · SIPKPA</span><span>Ruang kerja akademik <span aria-hidden="true">/</span> Data simulasi penelitian</span></footer>
        </main>
    </div>
</body>
</html>
