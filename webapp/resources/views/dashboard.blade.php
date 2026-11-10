<x-layout title="Dashboard" description="Ringkasan akademik dan tindak lanjut peserta PKPA berdasarkan data yang tercatat." page="dashboard">
    @php($scope = $angkatanTerpilih ? ['angkatan_id' => $angkatanTerpilih->id] : [])
    <x-slot:actions>
        <a class="btn" href="{{ route('peserta.index', $angkatanTerpilih ? ['angkatan_id' => $angkatanTerpilih->id] : []) }}">Peserta &amp; Penilaian <x-icon name="arrow-right" /></a>
    </x-slot:actions>

    <form action="{{ route('dashboard') }}" method="GET" class="peserta-workspace dashboard-filter">
        <div class="dashboard-filter-control">
            <label for="angkatan_id" class="label">Konteks angkatan</label>
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
        <p class="dashboard-scope">Statistik, grafik akademik, dan hasil prediksi mengikuti pilihan angkatan.
            @if ($angkatanTerpilih)<span class="block tabular-nums">Pelaksanaan: {{ $angkatanTerpilih->tanggal_mulai->format('d/m/Y') }} – {{ $angkatanTerpilih->tanggal_selesai->format('d/m/Y') }}</span>@endif
        </p>
    </form>

    <dl class="peserta-workspace dashboard-summary" aria-label="Ringkasan akademik angkatan yang dipilih">
        <div>
            <dt><span class="dashboard-stat-index" aria-hidden="true">01</span> Peserta terdaftar</dt>
            <dd><span class="dashboard-stat-number">{{ number_format($ringkasan['peserta'], 0, ',', '.') }}</span>
                <p>{{ $angkatanTerpilih?->nama_angkatan ?? 'Seluruh angkatan PKPA' }}</p>
            </dd>
        </div>
        <div>
            <dt><span class="dashboard-stat-index" aria-hidden="true">02</span> Penilaian tersedia</dt>
            <dd><span class="dashboard-stat-number">{{ number_format($akademik['dinilai'], 0, ',', '.') }}<span> / {{ number_format($ringkasan['peserta'], 0, ',', '.') }}</span></span>
                <p>{{ number_format($akademik['belum_dinilai'], 0, ',', '.') }} peserta belum memiliki penilaian.</p>
                @if ($akademik['belum_dinilai'])<a href="{{ route('peserta.index', [...$scope, 'status_aktual' => 'belum_dinilai']) }}">Tinjau penilaian</a>@endif
            </dd>
        </div>
        <div>
            <dt><span class="dashboard-stat-index" aria-hidden="true">03</span> Status aktual ditentukan</dt>
            <dd><span class="dashboard-stat-number">{{ number_format($akademik['status_ditentukan'], 0, ',', '.') }}<span> / {{ number_format($ringkasan['peserta'], 0, ',', '.') }}</span></span>
                <p>Status Lulus atau Tidak Lulus telah tercatat pada penilaian.</p>
            </dd>
        </div>
        <div>
            <dt><span class="dashboard-stat-index" aria-hidden="true">04</span> Perlu ditinjau · Model</dt>
            <dd><span class="dashboard-stat-number">{{ number_format($ringkasan['berisiko'], 0, ',', '.') }}</span>
                <p>Prediksi terbaru Berisiko Tidak Lulus, bukan keputusan aktual.</p>
                <a href="{{ route('laporan.berisiko', $scope) }}">Buka Peringatan Dini</a>
            </dd>
        </div>
    </dl>

    <p class="dashboard-research-note">Data simulasi penelitian. Status aktual mengikuti catatan penilaian; hasil model merupakan estimasi, bukan keputusan kelulusan resmi.</p>

    <div class="dashboard-visuals">
        <section class="peserta-workspace dashboard-chart-section" data-dashboard-chart="akademik" aria-labelledby="akademik-title">
            <div class="dashboard-section-heading">
                <p class="eyebrow">Capaian akademik</p>
                <h2 id="akademik-title">Rata-rata nilai akhir per angkatan</h2>
                <p>{{ number_format($akademik['dinilai'], 0, ',', '.') }} penilaian · {{ $angkatanTerpilih?->nama_angkatan ?? 'Seluruh angkatan' }} · Skala 0–100</p>
            </div>
            @if ($akademik['dinilai'] > 0)
                <div class="dashboard-chart-scroll" role="region" aria-label="Perbandingan nilai akhir per angkatan" tabindex="0">
                    <div class="dashboard-bar-frame" style="height: {{ max(240, count($tren) * 44) }}px">
                        <p hidden data-chart-loading="akademik" class="chart-loading" role="status">Memuat visualisasi nilai…</p>
                        <canvas id="akademik-chart" role="img" aria-label="Rata-rata nilai akhir per angkatan, skala nol sampai seratus. Angka lengkap tersedia pada rincian akademik."></canvas>
                    </div>
                </div>
                <p hidden data-chart-error="akademik" role="status" class="dashboard-chart-error">Grafik belum dapat ditampilkan. Data angka tetap tersedia pada rincian akademik di bawah.</p>
            @else
                <div class="dashboard-chart-empty">Belum ada penilaian untuk menampilkan rata-rata nilai akhir. <a class="link" href="{{ route('peserta.index', $scope) }}">Tinjau peserta dan penilaian</a></div>
            @endif
            <p class="dashboard-chart-caption">Nilai akhir menggunakan rata-rata lima nilai akademik, tanpa kehadiran. Ini perbandingan angkatan, bukan tren waktu.</p>
            <details class="dashboard-data-details" data-chart-fallback="akademik">
                <summary>Rincian akademik per angkatan</summary>
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
                <p class="eyebrow">Keputusan akademik tercatat</p>
                <h2 id="aktual-title">Distribusi status kelulusan aktual</h2>
                <p>{{ $angkatanTerpilih?->nama_angkatan ?? 'Semua angkatan' }} · {{ number_format($ringkasan['peserta'], 0, ',', '.') }} peserta terdaftar</p>
            </div>
            @if ($ringkasan['peserta'] > 0)
                <div class="dashboard-status-frame"><p hidden data-chart-loading="aktual" class="chart-loading" role="status">Memuat distribusi status…</p><canvas id="aktual-chart" role="img" aria-label="Status aktual: {{ $akademik['lulus'] }} Lulus, {{ $akademik['tidak_lulus'] }} Tidak Lulus, {{ $akademik['belum_ditentukan'] }} Belum Ditentukan, dan {{ $akademik['belum_dinilai'] }} belum dinilai."></canvas></div>
                <p hidden data-chart-error="aktual" role="status" class="dashboard-chart-error">Grafik belum dapat ditampilkan. Jumlah setiap status tetap tersedia di bawah.</p>
            @else
                <div class="dashboard-chart-empty">Belum ada peserta untuk menampilkan distribusi status aktual.</div>
            @endif
            <dl class="dashboard-actual-legend">
                @foreach (['lulus' => 'Lulus', 'tidak_lulus' => 'Tidak Lulus', 'belum_ditentukan' => 'Belum Ditentukan', 'belum_dinilai' => 'Belum dinilai'] as $key => $label)
                    <div><dt><span aria-hidden="true"></span>{{ $label }}</dt><dd>{{ number_format($akademik[$key], 0, ',', '.') }} <span class="font-normal text-slate-600">peserta · {{ $ringkasan['peserta'] > 0 ? number_format($akademik[$key] / $ringkasan['peserta'] * 100, 1, ',', '.') : '—' }}{{ $ringkasan['peserta'] > 0 ? '%' : '' }}</span></dd></div>
                @endforeach
            </dl>
            <p class="dashboard-chart-caption">Belum dinilai berarti belum ada penilaian. Belum Ditentukan berarti penilaian tersedia, tetapi status aktual belum ditetapkan. Grafik ini tidak menggunakan hasil prediksi model.</p>
        </section>
    </div>
    <noscript><p class="dashboard-chart-error">JavaScript diperlukan untuk grafik. Data angka tetap tersedia pada rincian akademik dan daftar status aktual.</p></noscript>

    <section class="peserta-workspace dashboard-predictions" aria-labelledby="dashboard-predictions-title" data-dashboard-chart="prediksi">
        <div class="peserta-section-heading">
            <h2 id="dashboard-predictions-title">Prediksi model tersimpan</h2>
            <a class="peserta-import" href="{{ route('prediksi.index', $scope) }}">Buka Prediksi Kelulusan <x-icon name="arrow-right" /></a>
        </div>
        <div class="dashboard-prediction-layout">
        <div class="dashboard-prediction-overview">
            <p class="eyebrow">Komposisi hasil model</p>
            @if ($ringkasan['terprediksi'] > 0)
                <div class="dashboard-donut-frame"><p hidden data-chart-loading="prediksi" class="chart-loading" role="status">Memuat komposisi prediksi…</p><canvas id="prediksi-chart" role="img" aria-label="Prediksi terbaru: {{ $ringkasan['lulus'] }} Lulus dan {{ $ringkasan['berisiko'] }} Berisiko Tidak Lulus dari {{ $ringkasan['terprediksi'] }} peserta terprediksi."></canvas></div>
                <p hidden data-chart-error="prediksi" role="status" class="chart-error mt-3">Grafik belum dapat ditampilkan. Jumlah hasil model tetap tersedia di bawah.</p>
            @else
                <p class="chart-empty">Belum ada hasil prediksi untuk konteks angkatan ini.</p>
            @endif
            <dl class="dashboard-prediction-legend">
                <div><dt><span class="chart-swatch bg-teal-700" aria-hidden="true"></span>Prediksi Lulus</dt><dd>{{ number_format($ringkasan['lulus'], 0, ',', '.') }}</dd></div>
                <div><dt><span class="chart-swatch bg-[#a67529]" aria-hidden="true"></span>Berisiko Tidak Lulus</dt><dd>{{ number_format($ringkasan['berisiko'], 0, ',', '.') }}</dd></div>
            </dl>
            <p class="chart-note mt-4">Penyebut grafik: {{ $ringkasan['terprediksi'] }} peserta yang sudah diprediksi. {{ $ringkasan['belum_prediksi'] }} peserta belum diprediksi.</p>
        </div>
        <div class="min-w-0">
        <div class="dashboard-prediction-context">
            <p><strong>{{ $ringkasan['terprediksi'] }} dari {{ $ringkasan['peserta'] }} peserta</strong> memiliki prediksi terbaru{{ $ringkasan['cakupan'] !== null ? ' ('.number_format($ringkasan['cakupan'], 2, ',', '.').'%)' : '' }}. {{ $angkatanTerpilih?->nama_angkatan ?? 'Cakupan seluruh angkatan' }}.</p>
            <p>Hasil tersimpan pada data simulasi; Dashboard tidak menjalankan prediksi. Daftar berikut menampilkan maksimal lima hasil terbaru, satu per peserta.</p>
        </div>
        <div class="dashboard-recent">
            @forelse ($riwayat as $item)
                <div>
                    <div class="min-w-0">
                        <a class="link" href="{{ route('peserta.show', $item->peserta) }}">{{ $item->peserta->nama_lengkap }}</a>
                        <p>{{ $item->peserta->angkatan->nama_angkatan }} · {{ $item->modelMl->nama_versi }} · <time datetime="{{ $item->tanggal_prediksi->toIso8601String() }}">{{ $item->tanggal_prediksi->format('d/m/Y H:i') }}</time></p>
                    </div>
                    <div><span class="sr-only">Prediksi model: </span><x-status :value="$item->status_prediksi" :compact="true" /></div>
                </div>
            @empty
                <p class="dashboard-list-empty">Belum ada prediksi tersimpan untuk angkatan yang dipilih. <a class="link" href="{{ route('prediksi.index', $scope) }}">Tinjau halaman Prediksi Kelulusan</a></p>
            @endforelse
        </div>
        </div>
        </div>
    </section>

    <details class="peserta-workspace dashboard-model-details" open>
        <summary>Evaluasi model aktif · Data simulasi <span>{{ $modelAktif?->nama_versi ?? 'Belum ada model aktif' }}</span></summary>
        <div class="dashboard-model-body">
            <p class="text-sm leading-6 text-slate-600">Evaluasi berasal dari pengujian data simulasi model aktif, bukan tingkat kelulusan peserta. Informasi model bersifat global dan tidak mengikuti filter angkatan.</p>
            @if ($modelAktif)
                <p class="mt-2 text-xs text-slate-600">{{ $modelAktif->jumlah_data_uji }} data uji · Kelas positif: Lulus</p>
                <dl class="metric-strip my-5">
                    @foreach ($evaluasi as $item)
                        <div><dt>{{ $item['label'] }}{{ $item['label'] === 'Akurasi' ? ' uji' : '' }}</dt><dd><x-score :value="$item['nilai']" :percentage="true" /></dd></div>
                    @endforeach
                </dl>
                <div class="dashboard-model-grid">
                    <div class="peserta-table-scroll" role="region" aria-label="Matriks klasifikasi model aktif" tabindex="0">
                        <table class="dashboard-confusion-table matrix-table">
                            <caption>Confusion matrix · Baris = aktual data uji, kolom = prediksi</caption>
                            <thead><tr><th scope="col">Aktual / Prediksi</th><th scope="col">Lulus (+)</th><th scope="col">Tidak Lulus (−)</th></tr></thead>
                            <tbody>
                                <tr><th scope="row">Lulus (+)</th><td class="matrix-correct"><strong data-confusion="TP">{{ $confusionMatrix['TP'] ?? '—' }}</strong><span>TP · Lulus dikenali benar</span></td><td class="matrix-error"><strong data-confusion="FN">{{ $confusionMatrix['FN'] ?? '—' }}</strong><span>FN · Lulus diprediksi tidak lulus</span></td></tr>
                                <tr><th scope="row">Tidak Lulus (−)</th><td class="matrix-error"><strong data-confusion="FP">{{ $confusionMatrix['FP'] ?? '—' }}</strong><span>FP · Tidak lulus diprediksi lulus</span></td><td class="matrix-correct"><strong data-confusion="TN">{{ $confusionMatrix['TN'] ?? '—' }}</strong><span>TN · Tidak lulus dikenali benar</span></td></tr>
                            </tbody>
                        </table>
                        <p class="matrix-key">Satuan: kasus pada data uji. Sel bertanda FP/FN merupakan kesalahan klasifikasi; kelas positif adalah Lulus.</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold">Kontribusi relatif fitur model</h3>
                        <p class="mt-2 text-xs leading-5 text-slate-600">Bukan penjelasan sebab-akibat untuk setiap peserta.</p>
                        <dl class="feature-bars mt-4">
                            @foreach ($featureImportance as $item)
                                <div><dt>{{ $item['label'] }}</dt><dd><x-score :value="$item['persentase']" :percentage="true" /></dd>@if ($item['persentase'] !== null)<dd class="feature-track" aria-hidden="true"><span style="width: {{ max(0, min(100, $item['persentase'])) }}%"></span></dd>@endif</div>
                            @endforeach
                        </dl>
                    </div>
                </div>
            @else
                <p class="mt-4 text-sm text-slate-600">Belum ada model aktif untuk menampilkan metrik evaluasi dan kontribusi fitur.</p>
            @endif
            <a class="peserta-import mt-4" href="{{ route('model.index') }}">Lihat eksperimen model <x-icon name="arrow-right" /></a>
        </div>
    </details>
    <script id="dashboard-chart-data" type="application/json">{!! \Illuminate\Support\Js::encode($chartData) !!}</script>
</x-layout>
