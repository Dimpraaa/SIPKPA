<x-layout title="Prediksi Kelulusan" description="Tinjau hasil klasifikasi model dan rekomendasi pembinaan peserta PKPA." page="prediksi">
    @php
        $isAdmin = auth()->user()->role === 'admin_akademik';
        $hasFilters = request()->filled('q') || request()->filled('angkatan_id');
    @endphp

    <p class="prediksi-research-note">Data simulasi penelitian. Prediksi bukan keputusan kelulusan resmi; ambang pembinaan bukan standar resmi Advokat Propindo.</p>

    <section class="peserta-workspace prediksi-operations" aria-label="Model dan pelaksanaan prediksi">
        <div class="prediksi-model">
            <div class="min-w-0">
                <p class="prediksi-eyebrow">Random Forest / Model aktif</p>
                <h2 class="mt-1 font-semibold">{{ $modelAktif?->nama_versi ?? 'Belum ada model aktif' }}</h2>
                <p class="mt-1 text-xs leading-5 text-slate-600 tabular-nums">
                    @if ($modelAktif)
                        Akurasi uji (simulasi): {{ number_format((float) $modelAktif->akurasi, 2, ',', '.') }}% <span aria-hidden="true">·</span> {{ $modelAktif->n_estimators }} pohon
                    @else
                        {{ $isAdmin ? 'Latih dan aktifkan model melalui Eksperimen Model. Riwayat tetap dapat ditinjau.' : 'Prediksi baru memerlukan model aktif. Riwayat tetap dapat ditinjau.' }}
                    @endif
                </p>
            </div>
            <a href="{{ route('model.index') }}" class="peserta-import">Eksperimen model <x-icon name="arrow-right" /></a>
        </div>

        @if ($isAdmin)
            <div class="prediksi-batch-heading">
                <h2 class="text-sm font-semibold" id="prediksi-batch-title">Prediksi per angkatan</h2>
                <p class="mt-1 text-xs leading-5 text-slate-600" id="prediksi-batch-help">Untuk angkatan berjalan. Kesiapan dihitung dari penilaian yang tersimpan; data diperiksa kembali saat prediksi dijalankan.</p>
            </div>
            <ul class="prediksi-batch-list" aria-labelledby="prediksi-batch-title">
                @forelse ($angkatan->where('status_angkatan', 'berjalan') as $item)
                    <li>
                        <div class="min-w-0">
                            <h3 class="text-sm font-medium">{{ $item->nama_angkatan }}</h3>
                            <p class="mt-1 text-xs leading-5 text-slate-600 tabular-nums" id="prediksi-ready-{{ $item->id }}">{{ number_format($item->peserta_siap_count, 0, ',', '.') }} dari {{ number_format($item->peserta_count, 0, ',', '.') }} peserta siap diprediksi.</p>
                            @if ($item->peserta_count > 0)<div class="batch-readiness" aria-hidden="true"><span style="width: {{ $item->peserta_siap_count / $item->peserta_count * 100 }}%"></span></div>@endif
                            @if (! $item->peserta_count)
                                <p class="mt-1 text-xs leading-5 text-slate-600">Tambahkan peserta sebelum menjalankan prediksi.</p>
                            @elseif ($item->peserta_siap_count < $item->peserta_count)
                                <p class="mt-1 text-xs leading-5 text-slate-600">{{ number_format($item->peserta_count - $item->peserta_siap_count, 0, ',', '.') }} peserta belum memiliki penilaian. Lengkapi enam atribut sebelum memproses seluruh angkatan.</p>
                            @endif
                        </div>
                        <form action="{{ route('prediksi.angkatan', $item) }}" method="POST" data-busy>
                            @csrf
                            <button type="submit" class="btn" aria-label="Prediksi seluruh peserta {{ $item->nama_angkatan }}" aria-describedby="prediksi-batch-help prediksi-ready-{{ $item->id }}" @disabled(! $modelAktif || ! $item->peserta_count)>Prediksi seluruh peserta</button>
                        </form>
                    </li>
                @empty
                    <li><p class="text-sm text-slate-600">Belum ada angkatan berjalan untuk prediksi massal.</p></li>
                @endforelse
            </ul>
        @endif
    </section>

    <section class="peserta-workspace" aria-labelledby="prediksi-list-title">
        <div class="peserta-section-heading">
            <h2 id="prediksi-list-title">Riwayat prediksi</h2>
            <span class="text-xs leading-5 text-slate-600">Setiap baris adalah satu hasil prediksi tersimpan. Peserta dapat muncul beberapa kali.</span>
        </div>
        <form action="{{ route('prediksi.index') }}" method="GET" class="peserta-filters prediksi-filters" role="search" aria-label="Pencarian dan filter prediksi">
            <div class="peserta-search">
                <label class="label" for="q">Cari peserta</label>
                <input class="input" type="search" id="q" name="q" value="{{ request('q') }}" placeholder="Cari nama atau nomor peserta" maxlength="100">
            </div>
            <div>
                <label class="label" for="angkatan_id">Angkatan</label>
                <select class="input" id="angkatan_id" name="angkatan_id">
                    <option value="">Semua angkatan</option>
                    @foreach ($angkatan as $item)
                        <option value="{{ $item->id }}" @selected(request('angkatan_id') == $item->id)>{{ $item->nama_angkatan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="peserta-filter-actions">
                <button type="submit" class="btn-secondary">Terapkan</button>
                @if ($hasFilters)
                    <a class="peserta-reset" href="{{ route('prediksi.index') }}">Reset filter</a>
                @endif
            </div>
        </form>

        <div data-pagination-region="prediksi" aria-busy="false">
            <div data-pagination-error data-pagination-error-message="Riwayat prediksi belum dapat dimuat. Silakan coba lagi." hidden role="alert" class="peserta-load-error">
                <p class="font-semibold">Riwayat prediksi belum dapat dimuat.</p>
                <p class="mt-1">Data sebelumnya tetap ditampilkan. Periksa koneksi Anda, lalu coba lagi.</p>
                <span data-pagination-controls><a data-pagination-retry class="peserta-reset" href="{{ request()->fullUrl() }}">Coba lagi</a></span>
            </div>
            <div class="peserta-table-context">
                <p>{{ number_format($riwayat->total(), 0, ',', '.') }} hasil{{ $hasFilters ? ' sesuai filter' : ' tercatat' }}</p>
                <p>Terbaru dahulu <span aria-hidden="true">·</span> 20 per halaman</p>
            </div>
            <p id="prediksi-estimate-help" class="prediksi-estimate-help">Persentase merupakan estimasi klasifikasi model berdasarkan data simulasi, bukan kepastian kelulusan.</p>
            <p id="prediksi-scroll-help" @class(['peserta-scroll-help', 'sr-only' => $riwayat->isEmpty()])>Geser tabel ke samping untuk melihat seluruh kolom dan tindakan.</p>
            <div class="peserta-table-scroll" role="region" aria-label="Tabel riwayat prediksi" aria-describedby="prediksi-scroll-help" tabindex="0">
                <table @class(['peserta-table prediksi-table', 'peserta-table-is-empty' => $riwayat->isEmpty()])>
                    <caption class="sr-only">Hasil klasifikasi model pada data simulasi. Estimasi kelas Lulus dan Tidak Lulus bukan keputusan kelulusan aktual.</caption>
                    <thead @class(['sr-only' => $riwayat->isEmpty()])>
                        <tr><th scope="col">Peserta</th><th scope="col">Angkatan</th><th scope="col">Hasil model</th><th scope="col" id="prediksi-estimate-column" class="text-right">Estimasi model</th><th scope="col">Pembinaan</th><th scope="col" class="text-right">Tindakan</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayat as $item)
                            <tr>
                                <th scope="row" class="peserta-identity">
                                    <a href="{{ route('peserta.show', $item->peserta) }}">{{ $item->peserta->nama_lengkap }}</a>
                                    <span class="peserta-number">{{ $item->peserta->nomor_peserta }}</span>
                                </th>
                                <td class="prediksi-cohort">{{ $item->peserta->angkatan->nama_angkatan }}</td>
                                <td class="prediksi-result">
                                    <x-status :value="$item->status_prediksi" :compact="true" />
                                    <p class="prediksi-meta"><span class="sr-only">Model: </span>{{ $item->modelMl->nama_versi }}</p>
                                    <time class="prediksi-meta block tabular-nums" datetime="{{ $item->tanggal_prediksi->toIso8601String() }}"><span class="sr-only">Waktu prediksi: </span>{{ $item->tanggal_prediksi->format('d/m/Y H:i:s') }}</time>
                                </td>
                                <td>
                                    <dl class="prediksi-probabilities" aria-labelledby="prediksi-estimate-column" aria-describedby="prediksi-estimate-help">
                                        <div><dt>Lulus</dt><dd><x-score :value="$item->probabilitas_lulus" :percentage="true" /></dd><dd class="probability-track" aria-hidden="true"><span style="width: {{ max(0, min(100, (float) $item->probabilitas_lulus)) }}%"></span></dd></div>
                                        <div><dt>Tidak Lulus</dt><dd><x-score :value="$item->probabilitas_gagal" :percentage="true" /></dd></div>
                                    </dl>
                                </td>
                                <td class="prediksi-summary">
                                    @if ($recommendations[$item->id]['focus'])
                                        <p>{{ implode(' · ', array_slice($recommendations[$item->id]['focus'], 0, 2)) }}</p>
                                        @if (count($recommendations[$item->id]['focus']) > 2)
                                            <p class="mt-1 text-[11px]">+{{ count($recommendations[$item->id]['focus']) - 2 }} fokus di detail</p>
                                        @endif
                                    @else
                                        <p>Fokus tidak dirinci</p>
                                    @endif
                                </td>
                                <td>
                                    <a class="peserta-detail" href="{{ route('peserta.show', $item->peserta) }}" data-prediction-detail="prediksi-detail-{{ $item->id }}" aria-haspopup="dialog" aria-label="Lihat detail prediksi {{ $item->peserta->nama_lengkap }}, {{ $item->tanggal_prediksi->format('d/m/Y H:i:s') }}">Lihat detail</a>
                                    @include('prediksi.detail', ['item' => $item, 'isAdmin' => $isAdmin, 'modelAktif' => $modelAktif, 'features' => $features, 'recommendation' => $recommendations[$item->id]])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="peserta-empty">
                                    @if ($riwayat->total() > 0)
                                        <h3>Halaman ini tidak memiliki hasil</h3>
                                        <p>Kembali ke halaman pertama untuk meninjau riwayat prediksi.</p>
                                        <a class="btn-secondary" href="{{ $riwayat->url(1) }}">Ke halaman pertama</a>
                                    @elseif ($hasFilters)
                                        <h3>Hasil prediksi tidak ditemukan</h3>
                                        <p>Coba nama atau nomor lain, atau sesuaikan filter angkatan.</p>
                                        <a class="btn-secondary" href="{{ route('prediksi.index') }}">Reset filter</a>
                                    @else
                                        <h3>Belum ada riwayat prediksi</h3>
                                        <p>{{ $isAdmin ? 'Jalankan prediksi untuk angkatan berjalan atau melalui detail peserta yang telah memiliki penilaian.' : 'Hasil akan tampil setelah admin akademik menjalankan prediksi.' }}</p>
                                        <a class="btn-secondary" href="{{ route('peserta.index') }}">Lihat peserta</a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="peserta-table-footer">{{ $riwayat->onEachSide(1)->links() }}</div>
        </div>
    </section>
</x-layout>
