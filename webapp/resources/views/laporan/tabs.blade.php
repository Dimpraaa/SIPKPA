<nav aria-label="Jenis laporan" class="report-tabs">
    @foreach (['laporan.berisiko' => 'Peringatan Dini', 'laporan.rekap' => 'Rekapitulasi Angkatan', 'laporan.model' => 'Riwayat Evaluasi Model'] as $name => $label)
        <a href="{{ route($name) }}" @if (request()->routeIs($name)) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
