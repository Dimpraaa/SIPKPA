<nav aria-label="Jenis laporan" class="mb-6 flex flex-wrap gap-2">
    @foreach (['laporan.berisiko' => 'LUA-05 · Peringatan Dini', 'laporan.rekap' => 'LUA-06 · Rekap Angkatan', 'laporan.model' => 'LUA-08 · Pengujian Model'] as $name => $label)
        <a href="{{ route($name) }}" @class(['rounded-xl border px-4 py-2.5 text-xs font-semibold', 'border-teal-200 bg-teal-50 text-teal-900' => request()->routeIs($name), 'border-slate-200 bg-white text-slate-500 hover:bg-slate-50' => !request()->routeIs($name)])>{{ $label }}</a>
    @endforeach
    <a href="{{ route('peserta.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-500 hover:bg-slate-50">LUA-07 · KHS pada Detail Peserta</a>
</nav>
