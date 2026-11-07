@props(['title', 'description' => 'Sistem Prediksi Kelulusan PKPA · Advokat Propindo'])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $title }} · SIPKPA Propindo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900">
    <header class="sticky top-0 z-30 flex h-18 items-center justify-between border-b border-slate-200 bg-white px-5 lg:hidden">
        <a href="{{ route('dashboard') }}" class="font-bold tracking-tight">SIPKPA<span class="text-teal-700"> PROPINDO</span></a>
        <button type="button" data-menu-toggle aria-controls="sidebar" aria-expanded="false" class="btn-secondary">Menu</button>
    </header>
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-20 hidden w-68 flex-col overflow-y-auto border-r border-slate-200 bg-white px-5 pt-24 pb-6 lg:flex lg:pt-8">
        <a href="{{ route('dashboard') }}" class="px-3 text-xl font-extrabold tracking-tight">SIPKPA<span class="text-teal-700"> PROPINDO</span></a>
        <p class="mt-2 px-3 text-xs text-slate-500">Pendidikan Khusus Profesi Advokat</p>
        <p class="mt-12 px-3 text-[10px] font-bold tracking-[.18em] text-slate-400 uppercase">Ruang akademik</p>
        <nav class="mt-4 grid gap-2" aria-label="Navigasi utama">
            @foreach (['dashboard' => ['Dasbor', '01'], 'angkatan.index' => ['Angkatan PKPA', '02'], 'peserta.index' => ['Peserta & Penilaian', '03'], 'model.index' => ['Eksperimen Model', '04'], 'prediksi.index' => ['Prediksi Kelulusan', '05']] as $route => [$label, $number])
                <a href="{{ route($route) }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition', 'bg-teal-50 text-teal-800' => request()->routeIs($route === 'dashboard' ? $route : explode('.', $route)[0].'.*'), 'text-slate-600 hover:bg-slate-50' => !request()->routeIs($route === 'dashboard' ? $route : explode('.', $route)[0].'.*')])><span class="text-xs opacity-50">{{ $number }}</span>{{ $label }}</a>
            @endforeach
        </nav>
        <p class="mt-6 px-3 text-[10px] font-bold tracking-[.18em] text-slate-400 uppercase">Pelaporan</p>
        <nav class="mt-3 grid gap-1" aria-label="Navigasi laporan">
            @foreach (['laporan.berisiko' => 'Peringatan Dini', 'laporan.rekap' => 'Rekap Angkatan', 'laporan.model' => 'Riwayat Pengujian'] as $name => $label)
                <a href="{{ route($name) }}" @class(['rounded-xl px-3 py-2.5 text-sm font-medium', 'bg-teal-50 text-teal-800' => request()->routeIs($name), 'text-slate-600 hover:bg-slate-50' => !request()->routeIs($name)])>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="mt-auto rounded-xl bg-slate-50 p-4">
            <p class="text-sm font-bold">{{ auth()->user()->nama_lengkap }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ auth()->user()->role === 'admin_akademik' ? 'Admin Akademik' : 'Manajemen · Akses baca' }}</p>
            <form action="{{ route('logout') }}" method="POST" class="mt-4">@csrf<button type="submit" class="text-xs font-semibold text-slate-600 hover:text-rose-700">Keluar dari akun →</button></form>
        </div>
    </aside>
    <div class="lg:pl-68">
        <header class="hidden h-20 items-center justify-between border-b border-slate-200 bg-white px-10 lg:flex"><p class="text-xs font-semibold tracking-widest text-slate-500 uppercase">Advokat Propindo / Akademik</p><span class="rounded-full bg-teal-50 px-3 py-1.5 text-xs font-semibold text-teal-800">Random Forest · Sistem Pendukung Keputusan</span></header>
        <main class="mx-auto max-w-400 px-5 py-8 sm:px-8 lg:p-10">
            <div class="mb-8 flex flex-wrap items-start justify-between gap-4"><div><h1 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ $title }}</h1><p class="mt-2 text-sm text-slate-500">{{ $description }}</p></div>{{ $actions ?? '' }}</div>
            @if (session('success'))<div role="status" class="mb-6 rounded-xl border border-teal-200 bg-teal-50 px-5 py-4 text-sm text-teal-900">{{ session('success') }}</div>@endif
            @if ($errors->any())<div role="alert" class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-900"><p class="font-bold">Permintaan belum dapat diproses</p><ul class="mt-2 list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            {{ $slot }}
            <footer class="mt-12 border-t border-slate-200 pt-6 text-xs text-slate-400">© {{ date('Y') }} Advokat Propindo · SIPKPA · Kelulusan yang terukur, pendampingan yang terarah.</footer>
        </main>
    </div>
</body>
</html>
