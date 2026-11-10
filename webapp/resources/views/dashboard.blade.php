<x-layout title="Dashboard" description="" page="dashboard">
    @php($scope = $angkatanTerpilih ? ['angkatan_id' => $angkatanTerpilih->id] : [])
    <x-slot:actions>
        <a class="btn" href="{{ route('peserta.index', $angkatanTerpilih ? ['angkatan_id' => $angkatanTerpilih->id] : []) }}">Peserta &amp; Penilaian <x-icon name="arrow-right" /></a>
    </x-slot:actions>

    <form action="{{ route('dashboard') }}" method="GET" class="dashboard-filter">
        <div class="dashboard-filter-control">
            <label for="angkatan_id" class="label">Angkatan</label>
            <div class="flex flex-wrap gap-3">
                <select id="angkatan_id" name="angkatan_id" class="input" data-dashboard-filter>
                    <option value="">Semua angkatan</option>
                    @foreach ($angkatan as $item)
                        <option value="{{ $item->id }}" @selected($angkatanTerpilih?->id === $item->id)>{{ $item->nama_angkatan }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-secondary">Terapkan</button>
                @if ($angkatanTerpilih)<a class="peserta-reset" href="{{ route('dashboard') }}">Reset filter</a>@endif
            </div>
        </div>
        @if ($angkatanTerpilih)<p class="dashboard-scope tabular-nums">Periode: {{ $angkatanTerpilih->tanggal_mulai->format('d/m/Y') }} – {{ $angkatanTerpilih->tanggal_selesai->format('d/m/Y') }}</p>@endif
    </form>

    <dl class="peserta-workspace dashboard-summary" aria-label="Ringkasan akademik angkatan yang dipilih">
        <div>
            <dt>Peserta terdaftar</dt>
            <dd><span class="dashboard-stat-number">{{ number_format($ringkasan['peserta'], 0, ',', '.') }}</span>
            </dd>
        </div>
        <div>
            <dt>Penilaian tersedia</dt>
            <dd><span class="dashboard-stat-number">{{ number_format($akademik['dinilai'], 0, ',', '.') }}<span> / {{ number_format($ringkasan['peserta'], 0, ',', '.') }}</span></span>
                @if ($akademik['belum_dinilai'])<a href="{{ route('peserta.index', [...$scope, 'status_aktual' => 'belum_dinilai']) }}">{{ number_format($akademik['belum_dinilai'], 0, ',', '.') }} belum dinilai</a>@endif
            </dd>
        </div>
        <div>
            <dt>Status aktual ditetapkan</dt>
            <dd><span class="dashboard-stat-number">{{ number_format($akademik['status_ditentukan'], 0, ',', '.') }}<span> / {{ number_format($ringkasan['peserta'], 0, ',', '.') }}</span></span>
                <p>Lulus atau Tidak Lulus</p>
            </dd>
        </div>
        <div>
            <dt>Prediksi berisiko</dt>
            <dd><span class="dashboard-stat-number">{{ number_format($ringkasan['berisiko'], 0, ',', '.') }}</span>
                <a href="{{ route('laporan.berisiko', $scope) }}">Peringatan Dini</a>
            </dd>
        </div>
    </dl>

    <p class="dashboard-research-note">Data simulasi penelitian. Prediksi bukan keputusan kelulusan.</p>

    <div class="dashboard-visuals">
        <section class="peserta-workspace dashboard-chart-section" data-dashboard-chart="akademik" aria-labelledby="akademik-title">
            <div class="dashboard-section-heading">
                <h2 id="akademik-title">Rata-rata Nilai per Angkatan</h2>
                <p>{{ number_format($akademik['dinilai'], 0, ',', '.') }} penilaian · Skala 0–100</p>
            </div>
            @if ($akademik['dinilai'] > 0)
                <div class="dashboard-chart-scroll" role="region" aria-label="Perbandingan nilai akhir per angkatan" tabindex="0">
                    <div class="dashboard-bar-frame" style="height: {{ max(200, count($tren) * 44) }}px">
                        <p hidden data-chart-loading="akademik" class="chart-loading" role="status">Memuat visualisasi nilai…</p>
                        <canvas id="akademik-chart" role="img" aria-label="Rata-rata nilai akhir per angkatan, skala nol sampai seratus. Angka lengkap tersedia pada rincian akademik."></canvas>
                    </div>
                </div>
                <p hidden data-chart-error="akademik" role="status" class="dashboard-chart-error">Grafik tidak dapat dimuat. Buka rincian akademik untuk melihat angkanya.</p>
            @else
                <div class="dashboard-chart-empty">Belum ada penilaian. <a class="link" href="{{ route('peserta.index', $scope) }}">Peserta &amp; Penilaian</a></div>
            @endif
            <p class="dashboard-chart-caption">Rata-rata lima nilai akademik, tanpa kehadiran.</p>
            <details class="dashboard-data-details" data-chart-fallback="akademik">
                <summary>Rincian akademik</summary>
                <div class="peserta-table-scroll" role="region" aria-label="Rincian nilai dan status aktual per angkatan" tabindex="0">
                    <table class="dashboard-detail-table">
                        <caption class="sr-only">Nilai terkini peserta yang memiliki penilaian. Persentase kelulusan aktual hanya menghitung status yang sudah ditentukan.</caption>
                        <thead><tr><th scope="col">Angkatan</th><th scope="col">Penilaian</th>@foreach ($features as $label)<th scope="col" class="text-right">{{ $label }}</th>@endforeach<th scope="col" class="text-right">Nilai akhir</th><th scope="col" class="text-right">Kelulusan aktual</th></tr></thead>
                        <tbody>
                            @forelse ($tren as $item)
                                <tr>
                                    <th scope="row">{{ $item['angkatan'] }}</th>
                                    <td class="whitespace-nowrap tabular-nums">{{ $item['jumlah_penilaian'] }} / {{ $item['peserta'] }}</td>
                                    @foreach ($item['rata_rata'] as $value)<td class="text-right tabular-nums"><x-score :value="$value" /></td>@endforeach
                                    <td class="text-right font-semibold tabular-nums"><x-score :value="$item['nilai_akhir_rata']" /></td>
                                    <td class="text-right tabular-nums">{{ $item['persentase_kelulusan'] !== null ? number_format($item['persentase_kelulusan'], 2, ',', '.').'%' : 'Belum ditentukan' }}<p class="mt-1 whitespace-nowrap text-xs text-slate-600">{{ $item['lulus_aktual'] }} lulus / {{ $item['status_ditentukan'] }} status ditentukan</p></td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="empty">Belum ada angkatan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </details>
        </section>

        <section class="peserta-workspace dashboard-chart-section" data-dashboard-chart="aktual" aria-labelledby="aktual-title">
            <div class="dashboard-section-heading">
                <h2 id="aktual-title">Status Kelulusan Aktual</h2>
                <p>{{ $angkatanTerpilih?->nama_angkatan ?? 'Semua angkatan' }} · {{ number_format($ringkasan['peserta'], 0, ',', '.') }} peserta terdaftar</p>
            </div>
            @if ($ringkasan['peserta'] > 0)
                <div class="dashboard-status-frame"><p hidden data-chart-loading="aktual" class="chart-loading" role="status">Memuat distribusi status…</p><canvas id="aktual-chart" role="img" aria-label="Status aktual: {{ $akademik['lulus'] }} Lulus, {{ $akademik['tidak_lulus'] }} Tidak Lulus, {{ $akademik['belum_ditentukan'] }} Belum Ditentukan, dan {{ $akademik['belum_dinilai'] }} belum dinilai."></canvas></div>
                <p hidden data-chart-error="aktual" role="status" class="dashboard-chart-error">Grafik tidak dapat dimuat. Buka rincian status untuk melihat angkanya.</p>
            @else
                <div class="dashboard-chart-empty">Belum ada peserta.</div>
            @endif
            <details class="dashboard-data-details" data-chart-fallback="aktual">
                <summary>Rincian status aktual</summary>
                <dl class="dashboard-actual-legend">
                    @foreach (['lulus' => 'Lulus', 'tidak_lulus' => 'Tidak Lulus', 'belum_ditentukan' => 'Belum Ditentukan', 'belum_dinilai' => 'Belum dinilai'] as $key => $label)
                        <div><dt><span aria-hidden="true"></span>{{ $label }}</dt><dd>{{ number_format($akademik[$key], 0, ',', '.') }} <span class="font-normal text-slate-600">peserta · {{ $ringkasan['peserta'] > 0 ? number_format($akademik[$key] / $ringkasan['peserta'] * 100, 1, ',', '.') : '—' }}{{ $ringkasan['peserta'] > 0 ? '%' : '' }}</span></dd></div>
                    @endforeach
                </dl>
                <p class="dashboard-chart-caption">Belum dinilai: belum ada penilaian. Belum Ditentukan: nilai tersedia, status belum ditetapkan.</p>
            </details>
        </section>
    </div>
    <noscript><p class="dashboard-chart-error">JavaScript diperlukan untuk grafik. Angka tersedia pada rincian akademik, status aktual, dan legenda prediksi.</p></noscript>

    <section class="peserta-workspace dashboard-predictions" aria-labelledby="dashboard-predictions-title" data-dashboard-chart="prediksi">
        <div class="dashboard-section-heading dashboard-predictions-heading">
            <div>
                <h2 id="dashboard-predictions-title">Hasil Prediksi Tersimpan</h2>
                <p>{{ $angkatanTerpilih?->nama_angkatan ?? 'Semua angkatan' }} · Prediksi terbaru per peserta, dari semua model</p>
            </div>
            <a class="peserta-import" href="{{ route('prediksi.index', $scope) }}">Semua hasil <x-icon name="arrow-right" /></a>
        </div>
        <div @class(['dashboard-prediction-layout', 'dashboard-prediction-layout-empty' => $ringkasan['terprediksi'] === 0])>
            @if ($ringkasan['terprediksi'] > 0)
                <div class="dashboard-donut-frame">
                    <p hidden data-chart-loading="prediksi" class="chart-loading" role="status">Memuat hasil prediksi…</p>
                    <canvas id="prediksi-chart" role="img" aria-label="{{ $ringkasan['terprediksi'] }} peserta sudah diprediksi: {{ $ringkasan['lulus'] }} Lulus dan {{ $ringkasan['berisiko'] }} Berisiko Tidak Lulus. Persentase setiap kategori tersedia pada legenda."></canvas>
                </div>
            @endif
            <div class="dashboard-prediction-overview">
                <dl class="dashboard-prediction-summary">
                    <div><dt>Sudah diprediksi</dt><dd>{{ number_format($ringkasan['terprediksi'], 0, ',', '.') }} <span>peserta</span></dd></div>
                    <div><dt>Belum diprediksi</dt><dd>{{ number_format($ringkasan['belum_prediksi'], 0, ',', '.') }} <span>peserta</span></dd></div>
                </dl>
                @if ($ringkasan['terprediksi'] === 0)<p class="dashboard-prediction-empty">Belum ada hasil prediksi tersimpan.</p>@endif
                <dl class="dashboard-prediction-legend">
                    @foreach (['lulus' => 'Lulus', 'berisiko' => 'Berisiko Tidak Lulus'] as $key => $label)
                        <div>
                            <dt><span @class(['chart-swatch', 'bg-teal-700' => $key === 'lulus', 'bg-[#a67529]' => $key === 'berisiko']) aria-hidden="true"></span>{{ $label }}</dt>
                            <dd><strong>{{ number_format($ringkasan[$key], 0, ',', '.') }}</strong> peserta ({{ $ringkasan['terprediksi'] > 0 ? number_format($ringkasan[$key] / $ringkasan['terprediksi'] * 100, 1, ',', '.').'%' : '—' }})</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
        <p hidden data-chart-error="prediksi" role="status" class="dashboard-chart-error">Grafik tidak dapat dimuat. Jumlah dan persentase tersedia pada legenda.</p>
        @if ($riwayat->isNotEmpty())
            <details class="dashboard-data-details">
                <summary>Terakhir diprediksi</summary>
                <div class="dashboard-recent">
                    @foreach ($riwayat as $item)
                        <div>
                            <div class="min-w-0">
                                <a class="link" href="{{ route('peserta.show', $item->peserta) }}">{{ $item->peserta->nama_lengkap }}</a>
                                <p>{{ $item->peserta->angkatan->nama_angkatan }} · {{ $item->modelMl->nama_versi }} · <time datetime="{{ $item->tanggal_prediksi->toIso8601String() }}">{{ $item->tanggal_prediksi->format('d/m/Y H:i') }}</time></p>
                            </div>
                            <div><span class="sr-only">Prediksi model: </span><x-status :value="$item->status_prediksi" :compact="true" /></div>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif
    </section>

    <details class="peserta-workspace dashboard-model-details">
        <summary>Evaluasi model aktif <span>{{ $modelAktif?->nama_versi ?? 'Belum ada model aktif' }}</span></summary>
        <div class="dashboard-model-body">
            @if ($modelAktif)
                <p class="chart-note">{{ $modelAktif->jumlah_data_uji }} data uji · Kelas positif: Lulus · Tidak mengikuti filter angkatan</p>
                <dl class="metric-strip my-5">
                    @foreach ($evaluasi as $item)
                        <div><dt>{{ $item['label'] }}{{ $item['label'] === 'Akurasi' ? ' uji' : '' }}</dt><dd><x-score :value="$item['nilai']" :percentage="true" /></dd></div>
                    @endforeach
                </dl>
                <div class="dashboard-model-grid">
                    <div class="peserta-table-scroll" role="region" aria-label="Matriks klasifikasi model aktif" tabindex="0">
                        <table class="dashboard-confusion-table matrix-table">
                            <caption>Matriks klasifikasi</caption>
                            <thead><tr><th scope="col">Aktual ↓ / Prediksi →</th><th scope="col">Lulus (+)</th><th scope="col">Tidak Lulus (−)</th></tr></thead>
                            <tbody>
                                <tr><th scope="row">Lulus (+)</th><td class="matrix-correct"><strong data-confusion="TP">{{ $confusionMatrix['TP'] ?? '—' }}</strong><span>TP · Lulus dikenali benar</span></td><td class="matrix-error"><strong data-confusion="FN">{{ $confusionMatrix['FN'] ?? '—' }}</strong><span>FN · Lulus diprediksi tidak lulus</span></td></tr>
                                <tr><th scope="row">Tidak Lulus (−)</th><td class="matrix-error"><strong data-confusion="FP">{{ $confusionMatrix['FP'] ?? '—' }}</strong><span>FP · Tidak lulus diprediksi lulus</span></td><td class="matrix-correct"><strong data-confusion="TN">{{ $confusionMatrix['TN'] ?? '—' }}</strong><span>TN · Tidak lulus dikenali benar</span></td></tr>
                            </tbody>
                        </table>
                        <p class="matrix-key">TP/TN: benar · FP/FN: keliru · Satuan: kasus</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold">Kontribusi fitur</h3>
                        <p class="mt-2 text-xs leading-5 text-slate-600">Bobot relatif, bukan sebab-akibat.</p>
                        <dl class="feature-bars mt-4">
                            @foreach ($featureImportance as $item)
                                <div><dt>{{ $item['label'] }}</dt><dd><x-score :value="$item['persentase']" :percentage="true" /></dd>@if ($item['persentase'] !== null)<dd class="feature-track" aria-hidden="true"><span style="width: {{ max(0, min(100, $item['persentase'])) }}%"></span></dd>@endif</div>
                            @endforeach
                        </dl>
                    </div>
                </div>
            @else
                <p class="chart-note">Latih dan aktifkan model untuk melihat evaluasi.</p>
            @endif
            <a class="peserta-import mt-4" href="{{ route('model.index') }}">Eksperimen Model <x-icon name="arrow-right" /></a>
        </div>
    </details>
    <script id="dashboard-chart-data" type="application/json">{!! \Illuminate\Support\Js::encode($chartData) !!}</script>
</x-layout>
