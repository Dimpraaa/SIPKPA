@props(['title', 'description' => 'Pengelolaan akademik Pendidikan Khusus Profesi Advokat.', 'page' => null])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · SIPKPA Propindo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @class(['text-slate-900', 'bg-slate-50' => $page !== 'peserta', 'peserta-page' => $page === 'peserta'])>
    <header class="sticky top-0 z-30 flex h-18 items-center justify-between border-b border-slate-200 bg-white px-5 lg:hidden">
        <a href="{{ route('dashboard') }}" class="font-bold tracking-tight">SIPKPA<span class="text-teal-700"> PROPINDO</span></a>
        <button type="button" data-menu-toggle aria-controls="sidebar" aria-expanded="false" class="btn-secondary">
            <x-icon name="menu" /> Menu
        </button>
    </header>

    <aside id="sidebar" class="fixed inset-y-0 left-0 z-20 hidden w-68 flex-col overflow-y-auto border-r border-slate-200 bg-white px-5 pt-24 pb-6 lg:flex lg:pt-8">
        <a href="{{ route('dashboard') }}" class="px-3 text-xl font-extrabold tracking-tight">SIPKPA<span class="text-teal-700"> PROPINDO</span></a>
        <p class="mt-2 px-3 text-xs text-slate-500">Pendidikan Khusus Profesi Advokat</p>

        <p class="mt-10 px-3 text-[10px] font-bold tracking-widest text-slate-400 uppercase">Ruang Akademik</p>
        <nav class="mt-3 grid gap-1" aria-label="Navigasi utama">
            @foreach ([
                'dashboard' => ['Dasbor', 'dashboard'],
                'angkatan.index' => ['Angkatan PKPA', 'calendar'],
                'peserta.index' => ['Peserta & Penilaian', 'users'],
                'model.index' => ['Eksperimen Model', 'model'],
                'prediksi.index' => ['Prediksi Kelulusan', 'prediction'],
            ] as $route => [$label, $icon])
                @php($active = request()->routeIs($route === 'dashboard' ? $route : explode('.', $route)[0].'.*'))
                <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
                    @class(['flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition', 'bg-teal-50 text-teal-800' => $active, 'text-slate-600 hover:bg-slate-50' => ! $active])>
                    <x-icon :name="$icon" />
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </nav>

        <p class="mt-6 px-3 text-[10px] font-bold tracking-widest text-slate-400 uppercase">Pelaporan</p>
        <nav class="mt-3 mb-6 grid gap-1" aria-label="Navigasi laporan">
            @foreach ([
                'laporan.berisiko' => ['Peringatan Dini', 'warning'],
                'laporan.rekap' => ['Rekapitulasi Angkatan', 'report'],
                'laporan.model' => ['Riwayat Evaluasi Model', 'clipboard'],
            ] as $name => [$label, $icon])
                @php($active = request()->routeIs($name))
                <a href="{{ route($name) }}" @if ($active) aria-current="page" @endif
                    @class(['flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition', 'bg-teal-50 text-teal-800' => $active, 'text-slate-600 hover:bg-slate-50' => ! $active])>
                    <x-icon :name="$icon" />
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </nav>

        <div class="mt-auto rounded-xl bg-slate-50 p-4">
            <p class="text-sm font-bold">{{ auth()->user()->nama_lengkap }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ auth()->user()->role === 'admin_akademik' ? 'Admin Akademik' : 'Manajemen · Akses baca' }}</p>
            <form action="{{ route('logout') }}" method="POST" class="mt-4">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-rose-700">
                    <x-icon name="logout" /> Keluar dari akun
                </button>
            </form>
        </div>
    </aside>

    <div class="min-w-0 lg:pl-68">
        <header class="hidden h-20 items-center justify-between gap-6 border-b border-slate-200 bg-white px-10 lg:flex">
            <p class="text-xs font-semibold tracking-widest text-slate-500 uppercase">Advokat Propindo / Akademik</p>
            <div class="flex min-w-0 items-center gap-3 text-right">
                <x-icon name="calendar" class="text-teal-700" />
                <div class="min-w-0">
                    <p class="text-xs text-slate-500">Angkatan berjalan terbaru</p>
                    <p class="max-w-sm truncate text-sm font-semibold" title="{{ $angkatanAktif?->nama_angkatan }}">{{ $angkatanAktif?->nama_angkatan ?? 'Belum ada angkatan berjalan' }}</p>
                    @if ($angkatanAktif)
                        <p class="text-xs text-slate-500 tabular-nums">{{ $angkatanAktif->tanggal_mulai->format('d/m/Y') }} – {{ $angkatanAktif->tanggal_selesai->format('d/m/Y') }}</p>
                    @endif
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-400 px-5 py-8 sm:px-8 lg:p-10">
            <div class="page-header mb-8 flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <h1 @class(['tracking-tight', 'text-2xl font-bold sm:text-3xl' => $page !== 'peserta', 'peserta-page-title' => $page === 'peserta'])>{{ $title }}</h1>
                    <p class="mt-2 text-sm text-slate-500">{{ $description }}</p>
                </div>
                {{ $actions ?? '' }}
            </div>
            @if (session('success'))
                <div role="status" class="mb-6 rounded-xl border border-teal-200 bg-teal-50 px-5 py-4 text-sm text-teal-900">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div role="alert" class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-900">
                    <p class="font-bold">Permintaan belum dapat diproses</p>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif
            <p data-pagination-status role="status" aria-live="polite" aria-atomic="true" class="sr-only"></p>
            {{ $slot }}
            <footer class="mt-12 border-t border-slate-200 pt-6 text-xs text-slate-400">© {{ date('Y') }} Advokat Propindo · Sistem Akademik PKPA</footer>
        </main>
    </div>
</body>
</html>
