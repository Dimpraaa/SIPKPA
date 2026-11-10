<x-layout :title="$title" :description="$description">
    <x-slot:actions>
        <div class="flex flex-wrap gap-3">
            <a class="btn-secondary" href="{{ route($risiko ? 'laporan.berisiko.export' : 'laporan.rekap.export', ['format' => 'pdf', ...$filters]) }}">
                <x-icon name="download" /> Unduh PDF
            </a>
            <a class="btn" href="{{ route($risiko ? 'laporan.berisiko.export' : 'laporan.rekap.export', ['format' => 'excel', ...$filters]) }}">
                <x-icon name="download" /> Unduh Excel
            </a>
        </div>
    </x-slot:actions>

    @include('laporan.tabs')

    @if (! $risiko)
        <section class="panel mb-7 overflow-hidden" aria-labelledby="comparison-title">
            <div class="section-heading"><div><p class="eyebrow">Perbandingan akademik</p><h2 id="comparison-title" class="section-title mt-1">Capaian nilai akhir antarangkatan</h2><p>Seluruh angkatan · {{ array_sum(array_column($comparison, 'dinilai')) }} penilaian tersedia · Skala 0–100</p></div><span class="text-[10px] font-semibold text-slate-600">Data simulasi penelitian</span></div>
            <div class="p-5">
                <p class="chart-note mb-4">Grafik membandingkan seluruh angkatan, terlepas dari filter tabel peserta di bawah. Rata-rata lima nilai akademik tanpa kehadiran; peserta tanpa penilaian tidak dihitung.</p>
                @if (array_sum(array_column($comparison, 'dinilai')) > 0)
                    <div class="comparison-chart-scroll" role="region" aria-label="Perbandingan rata-rata nilai akhir seluruh angkatan" tabindex="0"><div class="chart-frame" style="height: {{ max(220, count($comparison) * 48) }}px"><p hidden data-chart-loading="comparison" class="chart-loading" role="status">Memuat perbandingan angkatan…</p><canvas id="cohort-comparison-chart" role="img" aria-label="Rata-rata nilai akhir antarangkatan, skala nol sampai seratus. Angka dan jumlah penilaian tersedia pada rincian perbandingan."></canvas></div></div>
                    <div class="chart-legend mt-3"><span><i class="chart-swatch bg-teal-700" aria-hidden="true"></i>Angkatan pilihan tabel</span><span><i class="chart-swatch bg-[#466d91]" aria-hidden="true"></i>Angkatan lain</span></div>
                @else
                    <p class="chart-empty">Belum ada penilaian untuk membandingkan capaian antarangkatan.</p>
                @endif
                <p hidden data-comparison-error role="status" class="chart-error mt-4">Grafik belum dapat ditampilkan. Nilai tetap tersedia pada rincian perbandingan.</p>
                <noscript><p class="chart-note mt-3">JavaScript diperlukan untuk grafik. Buka rincian perbandingan untuk melihat angka lengkap.</p></noscript>
            </div>
            <details data-comparison-fallback class="comparison-details">
                <summary>Rincian nilai dan kelulusan aktual antarangkatan</summary>
                <div class="overflow-x-auto" role="region" aria-label="Rincian perbandingan angkatan" tabindex="0">
                    <table class="w-full"><thead class="bg-slate-50"><tr><th scope="col">Angkatan</th><th scope="col" class="text-right">Dinilai / Peserta</th><th scope="col" class="text-right">Nilai akhir / 100</th><th scope="col" class="text-right">Lulus / Status ditentukan</th><th scope="col" class="text-right">Kelulusan aktual (%)</th></tr></thead><tbody>
                        @forelse ($comparison as $item)
                            <tr @class(['model-comparison-active' => $selected?->id === $item['id']])><td><a class="link" href="{{ route('laporan.rekap', ['angkatan_id' => $item['id']]) }}">{{ $item['nama'] }}</a>@if ($selected?->id === $item['id'])<span class="mt-1 block text-[10px] text-slate-500">Angkatan pilihan tabel</span>@endif</td><td class="whitespace-nowrap text-right tabular-nums">{{ $item['dinilai'] }} / {{ $item['peserta'] }}</td><td class="text-right font-semibold tabular-nums"><x-score :value="$item['rata_rata']" /></td><td class="text-right tabular-nums">{{ $item['lulus'] }} / {{ $item['ditentukan'] }}</td><td class="text-right tabular-nums"><x-score :value="$item['ditentukan'] > 0 ? $item['lulus'] / $item['ditentukan'] * 100 : null" :percentage="true" /></td></tr>
                        @empty
                            <tr><td colspan="5" class="empty">Belum ada angkatan untuk dibandingkan.</td></tr>
                        @endforelse
                    </tbody></table>
                </div>
                <p class="chart-note px-5 py-4">Persentase kelulusan aktual = peserta berstatus Lulus dibagi peserta dengan status Lulus atau Tidak Lulus. Belum Ditentukan dan belum dinilai tidak menjadi penyebut.</p>
            </details>
            <script id="cohort-comparison-data" type="application/json">{!! \Illuminate\Support\Js::encode($comparisonChart) !!}</script>
        </section>
    @endif

    <form method="GET" action="{{ route($risiko ? 'laporan.berisiko' : 'laporan.rekap') }}" class="filter-bar mb-6">
        <div class="min-w-56">
            <label for="angkatan_id" class="label">Angkatan</label>
            <select class="input" name="angkatan_id" id="angkatan_id">
                @if ($risiko)<option value="">Semua angkatan</option>@endif
                @foreach ($angkatan as $item)
                    <option value="{{ $item->id }}" @selected($selected?->id === $item->id)>{{ $item->nama_angkatan }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-56 flex-1">
            <label for="q" class="label">Nama atau nomor peserta</label>
            <input class="input" id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Cari peserta">
        </div>
        @if ($risiko)
            <div>
                <label class="label" for="min_gagal">Ambang Risiko Minimum (%)</label>
                <input class="input w-56" id="min_gagal" name="min_gagal" type="number" min="0" max="100" step="0.01" value="{{ $filters['min_gagal'] ?? '' }}">
            </div>
        @endif
        <button class="btn" type="submit">Terapkan filter</button>
        <a class="btn-secondary" href="{{ route($risiko ? 'laporan.berisiko' : 'laporan.rekap') }}">Reset</a>
    </form>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500"><strong class="text-slate-900 tabular-nums">{{ $total }}</strong> peserta · {{ $selected?->nama_angkatan ?? 'Semua angkatan' }}</p>
        <p class="inline-flex items-center gap-2 text-xs text-slate-500">
            <span class="h-2 w-2 shrink-0 rounded-full bg-red-600" aria-hidden="true"></span>
            Di bawah ambang simulasi: kehadiran &lt;{{ $standards['persen_kehadiran'] }}% atau nilai akademik &lt;{{ $standards['nilai_kode_etik'] }}
        </p>
    </div>

    @if (! $risiko)
        @php
            $columnWidths = array_map(fn (string $key): int => match ($key) {
                'no' => 64,
                'nomor_peserta' => 208,
                'nama_lengkap' => 280,
                'angkatan', 'status_aktual' => 184,
                'status_prediksi' => 240,
                'probabilitas_lulus', 'probabilitas_gagal', 'tanggal_prediksi' => 216,
                'model' => 224,
                'rekomendasi' => 384,
                default => 176,
            }, array_keys($columns));
        @endphp
    @endif

    <div data-pagination-region="laporan-peserta" class="panel overflow-hidden">
        <div class="table-heading"><div><p class="eyebrow">Rekap peserta</p><h2 class="section-title mt-1">{{ $selected?->nama_angkatan ?? 'Belum ada angkatan' }}</h2><p>Nilai terkini, status aktual, dan prediksi terbaru ditampilkan terpisah.</p></div><span class="text-xs text-slate-600">{{ $total }} peserta sesuai filter</span></div>
        <div @class(['overflow-x-auto' => $risiko, 'recap-table-scroll' => ! $risiko]) role="region" aria-label="Tabel laporan peserta" tabindex="0">
            <table @class(['w-full' => $risiko, 'table-fixed' => ! $risiko]) @if (! $risiko) style="width: {{ array_sum($columnWidths) }}px" @endif>
                @if (! $risiko)
                    <colgroup>
                        @foreach ($columnWidths as $width)<col style="width: {{ $width }}px">@endforeach
                    </colgroup>
                @endif
                <thead class="bg-slate-50">
                    <tr>
                        @foreach ($columns as $key => $label)
                            <th scope="col" @class(['text-right tabular-nums' => isset($standards[$key]) || in_array($key, ['no', 'nilai_akhir_rata', 'probabilitas_lulus', 'probabilitas_gagal'])])>{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="hover:bg-slate-50">
                            @foreach ($columns as $key => $label)
                                <td @class([
                                    'text-sm',
                                    'w-80 min-w-80 whitespace-normal leading-relaxed' => $key === 'rekomendasi',
                                    'whitespace-nowrap' => $key !== 'rekomendasi',
                                    'overflow-hidden text-ellipsis' => ! $risiko && in_array($key, ['nomor_peserta', 'nama_lengkap', 'angkatan', 'model']),
                                    'text-right tabular-nums' => isset($standards[$key]) || in_array($key, ['no', 'nilai_akhir_rata', 'probabilitas_lulus', 'probabilitas_gagal']),
                                ])>
                                    @if ($key === 'nama_lengkap')
                                        <a class="link" href="{{ route('peserta.show', $row['id']) }}">{{ $row[$key] }}</a>
                                    @elseif (in_array($key, ['status_aktual', 'status_prediksi']))
                                        <x-status :value="$row[$key]" />
                                    @elseif (isset($standards[$key]) || in_array($key, ['nilai_akhir_rata', 'probabilitas_lulus', 'probabilitas_gagal']))
                                        <x-score :value="$row[$key]" :minimum="$standards[$key] ?? null" :percentage="in_array($key, ['persen_kehadiran', 'probabilitas_lulus', 'probabilitas_gagal'])" />
                                    @else
                                        {{ $row[$key] ?? '—' }}
                                    @endif
                                    @if ($key === 'rekomendasi' && $row['nilai_diperbarui'])
                                        <p class="mt-2 text-xs font-medium text-amber-700">Nilai telah diperbarui. Jalankan ulang prediksi untuk memperbarui rekomendasi.</p>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($columns) }}" class="empty">{{ $risiko ? 'Tidak ada peserta berisiko yang sesuai dengan filter ini.' : 'Belum ada peserta pada angkatan atau pencarian ini.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">{{ $pagination->onEachSide(1)->links() }}</div>
    </div>
    <p class="mt-5 text-xs leading-relaxed text-slate-500">Unduhan mencakup seluruh peserta sesuai filter. Laporan menggunakan nilai terkini dan prediksi terakhir setiap peserta.</p>
    <p class="chart-note mt-2">Nilai {{ $standards['nilai_kode_etik'] }} adalah asumsi pemodelan. Kehadiran {{ $standards['persen_kehadiran'] }}% memiliki rujukan PERADI; penerapannya sebagai aturan internal Advokat Propindo belum diverifikasi. Estimasi model bukan jaminan kelulusan.</p>
</x-layout>
