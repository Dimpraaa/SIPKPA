<x-layout title="Dasbor Analitik PKPA" description="Pantau kesiapan peserta, perkembangan nilai, dan kinerja model Random Forest dari data akademik terkini.">
    <form action="{{ route('dashboard') }}" method="GET" class="panel mb-6 flex flex-wrap items-end justify-between gap-5 p-5">
        <div>
            <label for="angkatan_id" class="label">Filter angkatan</label>
            <div class="flex flex-wrap gap-3">
                <select id="angkatan_id" name="angkatan_id" class="input min-w-60" data-dashboard-filter>
                    <option value="">Semua angkatan</option>
                    @foreach ($angkatan as $item)
                        <option value="{{ $item->id }}" @selected($angkatanTerpilih?->id === $item->id)>{{ $item->nama_angkatan }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn">Terapkan</button>
                @if ($angkatanTerpilih)<a class="btn-secondary" href="{{ route('dashboard') }}">Reset</a>@endif
            </div>
        </div>
        <p class="max-w-md text-xs leading-relaxed text-slate-500">Filter berlaku untuk ringkasan, proporsi, dan prediksi terbaru. Tren antar-angkatan dan evaluasi model aktif tetap ditampilkan secara menyeluruh.</p>
    </form>

    <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="panel p-6">
            <p class="text-xs font-semibold text-slate-500">Peserta terdaftar</p>
            <p class="mt-3 text-3xl font-bold tracking-tight">{{ number_format($ringkasan['peserta'], 0, ',', '.') }}</p>
            <p class="mt-3 text-xs text-slate-400">{{ $angkatanTerpilih?->nama_angkatan ?? 'Seluruh angkatan PKPA' }}</p>
        </article>
        <article class="panel border-t-4 border-t-teal-600 p-6">
            <p class="text-xs font-semibold text-slate-500">Prediksi Lulus</p>
            <p class="mt-3 text-3xl font-bold tracking-tight text-teal-800">{{ $ringkasan['lulus'] }}</p>
            <p class="mt-3 text-xs text-slate-400">Berdasarkan prediksi terbaru setiap peserta</p>
        </article>
        <article class="panel border-t-4 border-t-rose-500 p-6">
            <p class="text-xs font-semibold text-slate-500">Berisiko Tidak Lulus</p>
            <p class="mt-3 text-3xl font-bold tracking-tight text-rose-700">{{ $ringkasan['berisiko'] }}</p>
            <p class="mt-3 text-xs text-slate-400">Memerlukan perhatian dan pendampingan</p>
        </article>
        <article class="panel p-6">
            <p class="text-xs font-semibold text-slate-500">Cakupan prediksi</p>
            <p class="mt-3 text-3xl font-bold tracking-tight">{{ $ringkasan['cakupan'] !== null ? number_format($ringkasan['cakupan'], 2, ',', '.').'%' : '—' }}</p>
            <p class="mt-3 text-xs text-slate-400">{{ $ringkasan['terprediksi'] }} sudah diprediksi · {{ $ringkasan['belum_prediksi'] }} belum diprediksi</p>
        </article>
    </div>

    <div class="grid gap-6 xl:grid-cols-12">
        <section class="panel p-6 xl:col-span-4" aria-labelledby="proporsi-title">
            <p class="text-[10px] font-bold tracking-[.18em] text-teal-700">LUA-01 · PROPORSI KELULUSAN</p>
            <h2 id="proporsi-title" class="mt-2 text-lg font-bold">Prediksi kelulusan peserta</h2>
            <p class="mt-2 text-xs text-slate-500">{{ $angkatanTerpilih?->nama_angkatan ?? 'Semua angkatan' }} · {{ $ringkasan['terprediksi'] }} peserta terprediksi</p>
            @if ($ringkasan['terprediksi'] > 0)
                <div class="relative mt-6 h-72">
                    <canvas id="proporsi-chart" role="img" aria-label="Proporsi prediksi: {{ $ringkasan['lulus'] }} Lulus dan {{ $ringkasan['berisiko'] }} Berisiko Tidak Lulus."></canvas>
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center" aria-hidden="true">
                        <span class="text-4xl font-bold tracking-tight">{{ $ringkasan['terprediksi'] }}</span>
                        <span class="mt-1 text-xs text-slate-400">peserta terprediksi</span>
                    </div>
                </div>
            @else
                <div class="mt-6 flex h-72 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm leading-relaxed text-slate-500">Belum ada prediksi untuk angkatan ini. Jalankan prediksi melalui menu Prediksi Kelulusan.</div>
            @endif
            <dl class="mt-6 grid gap-3 text-sm">
                <div class="flex items-center justify-between"><dt class="flex items-center gap-2 text-slate-600"><span class="size-2.5 rounded-full bg-teal-600" aria-hidden="true"></span>Lulus</dt><dd class="font-bold">{{ $ringkasan['lulus'] }}</dd></div>
                <div class="flex items-center justify-between"><dt class="flex items-center gap-2 text-slate-600"><span class="size-2.5 rounded-full bg-rose-500" aria-hidden="true"></span>Berisiko Tidak Lulus</dt><dd class="font-bold">{{ $ringkasan['berisiko'] }}</dd></div>
            </dl>
            <p class="mt-5 border-t border-slate-100 pt-4 text-xs leading-relaxed text-slate-400">Satu prediksi terbaru per peserta, berdasarkan tanggal prediksi. Peserta yang belum diprediksi tidak dimasukkan ke dalam donut.</p>
        </section>

        <section class="panel min-w-0 p-6 xl:col-span-8" aria-labelledby="tren-title">
            <p class="text-[10px] font-bold tracking-[.18em] text-teal-700">LUA-02 · TREN ANTAR-ANGKATAN</p>
            <h2 id="tren-title" class="mt-2 text-lg font-bold">Perkembangan nilai & kelulusan aktual</h2>
            <p class="mt-2 text-xs leading-relaxed text-slate-500">Batang: rata-rata X1–X6. Garis: persentase Lulus dari peserta yang status aktualnya sudah ditentukan.</p>
            @if (array_sum(array_column($tren, 'jumlah_penilaian')) > 0)
                <div class="mt-6 h-88 sm:h-96"><canvas id="tren-chart" role="img" aria-label="Tren rata-rata enam komponen penilaian dan kelulusan aktual antar-angkatan. Data lengkap tersedia pada tabel di bawah."></canvas></div>
            @else
                <div class="mt-6 flex h-88 items-center justify-center rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">Belum ada penilaian untuk menampilkan tren antar-angkatan.</div>
            @endif
            <p class="mt-4 text-xs leading-relaxed text-slate-400">X1 berupa persentase kehadiran; X2–X6 berupa nilai 0–100. Kelulusan aktual yang belum tersedia ditampilkan sebagai jeda, bukan 0%.</p>
        </section>

        <section class="panel min-w-0 p-6 xl:col-span-5" aria-labelledby="importance-title">
            <p class="text-[10px] font-bold tracking-[.18em] text-teal-700">LUA-03 · FEATURE IMPORTANCE</p>
            <h2 id="importance-title" class="mt-2 text-lg font-bold">Kontribusi fitur Random Forest</h2>
            <p class="mt-2 text-xs text-slate-500">Model aktif: {{ $modelAktif?->nama_versi ?? 'Belum tersedia' }}</p>
            @if ($modelAktif && collect($featureImportance)->contains(fn (array $item): bool => $item['persentase'] !== null))
                <div class="mt-6 h-80"><canvas id="importance-chart" role="img" aria-label="Kontribusi masing-masing fitur pada model Random Forest aktif. Nilai lengkap tersedia pada tabel kontribusi fitur."></canvas></div>
                <details class="mt-5 rounded-xl bg-slate-50 p-4">
                    <summary class="cursor-pointer text-xs font-semibold text-slate-600">Lihat kontribusi setiap fitur</summary>
                    <dl class="mt-4 grid gap-3 text-xs">
                        @foreach ($featureImportance as $item)
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ $item['label'] }}</dt><dd class="font-semibold">{{ $item['persentase'] !== null ? number_format($item['persentase'], 2, ',', '.').'%' : '—' }}</dd></div>
                        @endforeach
                    </dl>
                </details>
            @else
                <div class="mt-6 flex h-80 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm leading-relaxed text-slate-500">{{ $modelAktif ? 'Data kontribusi fitur model aktif belum tersedia.' : 'Belum ada model aktif. Latih dan aktifkan model pada menu Eksperimen Model.' }}</div>
            @endif
            <p class="mt-5 text-xs leading-relaxed text-slate-400">Proporsi dari feature_importance_json dikonversi menjadi persen. Nilai lebih besar menunjukkan kontribusi relatif lebih tinggi pada model.</p>
        </section>

        <section class="panel min-w-0 p-6 xl:col-span-7" aria-labelledby="evaluasi-title">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><p class="text-[10px] font-bold tracking-[.18em] text-teal-700">LUA-04 · EVALUASI MODEL</p><h2 id="evaluasi-title" class="mt-2 text-lg font-bold">Confusion Matrix & metrik evaluasi</h2></div>
                @if ($modelAktif)
                    <x-status value="Aktif" />
                @endif
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ $modelAktif ? $modelAktif->nama_versi.' · '.$modelAktif->jumlah_data_uji.' data uji · Kelas positif: Lulus' : 'Evaluasi akan tersedia setelah model dilatih dan diaktifkan.' }}</p>
            @if ($modelAktif)
                <div class="mt-6 overflow-x-auto">
                    <table class="w-full border-separate border-spacing-2 text-center" aria-label="Confusion matrix model aktif; baris adalah aktual dan kolom adalah prediksi.">
                        <caption class="mb-2 text-left text-xs text-slate-400">Baris = aktual · Kolom = prediksi · Negatif = Tidak Lulus</caption>
                        <thead><tr><th scope="col" class="p-2 text-left">Aktual ↓ / Prediksi →</th><th scope="col" class="p-2 text-center normal-case">Lulus (+)</th><th scope="col" class="p-2 text-center normal-case">Tidak Lulus (−)</th></tr></thead>
                        <tbody>
                            <tr class="border-0">
                                <th scope="row" class="p-2 normal-case">Lulus (+)</th>
                                <td class="rounded-xl bg-teal-50 p-4"><span class="block text-xs font-semibold text-teal-700">TP · Benar positif</span><strong class="mt-1 block text-2xl text-teal-900" data-confusion="TP">{{ $confusionMatrix['TP'] ?? '—' }}</strong></td>
                                <td class="rounded-xl bg-rose-50 p-4"><span class="block text-xs font-semibold text-rose-700">FN · Salah negatif</span><strong class="mt-1 block text-2xl text-rose-900" data-confusion="FN">{{ $confusionMatrix['FN'] ?? '—' }}</strong></td>
                            </tr>
                            <tr class="border-0">
                                <th scope="row" class="p-2 normal-case">Tidak Lulus (−)</th>
                                <td class="rounded-xl bg-amber-50 p-4"><span class="block text-xs font-semibold text-amber-700">FP · Salah positif</span><strong class="mt-1 block text-2xl text-amber-900" data-confusion="FP">{{ $confusionMatrix['FP'] ?? '—' }}</strong></td>
                                <td class="rounded-xl bg-teal-50 p-4"><span class="block text-xs font-semibold text-teal-700">TN · Benar negatif</span><strong class="mt-1 block text-2xl text-teal-900" data-confusion="TN">{{ $confusionMatrix['TN'] ?? '—' }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-6 grid gap-x-6 gap-y-5 sm:grid-cols-2">
                    @foreach ($evaluasi as $item)
                        <div>
                            <div class="mb-2 flex justify-between gap-3 text-xs"><label for="metric-{{ $loop->index }}" class="font-semibold text-slate-600">{{ $item['label'] }}</label><span class="font-bold text-teal-800">{{ number_format($item['nilai'], 2, ',', '.') }}%</span></div>
                            <progress id="metric-{{ $loop->index }}" value="{{ $item['nilai'] }}" max="100" class="h-2.5 w-full overflow-hidden rounded-full accent-teal-600 [&::-moz-progress-bar]:rounded-full [&::-moz-progress-bar]:bg-teal-600 [&::-webkit-progress-bar]:rounded-full [&::-webkit-progress-bar]:bg-slate-100 [&::-webkit-progress-value]:rounded-full [&::-webkit-progress-value]:bg-teal-600">{{ $item['nilai'] }}%</progress>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mt-6 flex h-80 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm text-slate-500">Belum ada model aktif untuk menampilkan matriks dan metrik evaluasi.</div>
            @endif
            <a href="{{ route('model.index') }}" class="link mt-6 inline-block text-xs">Lihat eksperimen model →</a>
        </section>
    </div>

    <details class="panel mt-6 p-6">
        <summary class="cursor-pointer text-sm font-bold">Data lengkap tren antar-angkatan</summary>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full">
                <caption class="pb-3 text-left text-xs text-slate-400">Rata-rata dihitung dari peserta yang memiliki penilaian. Kelulusan aktual mengecualikan status Belum Ditentukan.</caption>
                <thead class="bg-slate-50"><tr><th>Angkatan</th>@foreach ($features as $label)<th>{{ $label }}</th>@endforeach<th>Kelulusan aktual</th></tr></thead>
                <tbody>
                    @forelse ($tren as $item)
                        <tr>
                            <td class="whitespace-nowrap font-semibold">{{ $item['angkatan'] }}<p class="mt-1 text-xs font-normal text-slate-400">{{ $item['jumlah_penilaian'] }} / {{ $item['peserta'] }} peserta dinilai</p></td>
                            @foreach ($item['rata_rata'] as $value)<td>{{ $value !== null ? number_format($value, 2, ',', '.') : '—' }}</td>@endforeach
                            <td class="whitespace-nowrap">{{ $item['persentase_kelulusan'] !== null ? number_format($item['persentase_kelulusan'], 2, ',', '.').'%' : 'Belum tersedia' }}<p class="mt-1 text-xs text-slate-400">{{ $item['lulus_aktual'] }} Lulus / {{ $item['status_ditentukan'] }} status ditentukan</p></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty">Belum ada angkatan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </details>

    <section class="panel mt-6" aria-labelledby="riwayat-title">
        <div class="flex flex-wrap items-center justify-between gap-3 p-6"><h2 id="riwayat-title" class="font-bold">Prediksi terbaru per peserta</h2><a href="{{ route('prediksi.index', ['angkatan_id' => $angkatanTerpilih?->id]) }}" class="link text-xs">Lihat riwayat →</a></div>
        <div class="grid divide-y divide-slate-100">
            @forelse ($riwayat as $item)
                <div class="flex flex-wrap items-start justify-between gap-4 px-6 py-4">
                    <div><a href="{{ route('peserta.show', $item->peserta) }}" class="link text-sm">{{ $item->peserta->nama_lengkap }}</a><p class="mt-1 text-xs text-slate-400">{{ $item->peserta->angkatan->nama_angkatan }} · {{ $item->modelMl->nama_versi }} · {{ $item->tanggal_prediksi->format('d/m/Y H:i') }}</p></div>
                    <x-status :value="$item->status_prediksi" />
                </div>
            @empty
                <p class="empty">Belum ada prediksi untuk filter yang dipilih.</p>
            @endforelse
        </div>
    </section>

    <script id="dashboard-chart-data" type="application/json">{!! \Illuminate\Support\Js::encode($chartData) !!}</script>
</x-layout>
