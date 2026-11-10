<x-layout title="Peringatan Dini" description="Pantau indikator akademik dan hasil prediksi peserta yang memerlukan perhatian." page="peringatan">
    <x-slot:actions>
        <div class="peringatan-export-actions">
            <a class="btn-secondary" href="{{ route('laporan.berisiko.export', ['format' => 'pdf', ...$filters]) }}"><x-icon name="download" /> Unduh PDF</a>
            <a class="btn" href="{{ route('laporan.berisiko.export', ['format' => 'excel', ...$filters]) }}"><x-icon name="download" /> Unduh Excel</a>
        </div>
    </x-slot:actions>

    @php($hasFilters = request()->filled('q') || request()->filled('angkatan_id') || request()->filled('min_gagal'))

    @include('laporan.tabs')

    <div class="peringatan-context">
        <p class="eyebrow mb-2">Dasar tindak lanjut</p>
        <p>Daftar mengikuti prediksi terbaru <strong>Berisiko Tidak Lulus</strong>. Indikator menandai nilai terkini di bawah ambang evaluasi simulasi; keduanya bukan keputusan kelulusan resmi.</p>
        <details>
            <summary>Dasar pemantauan dan ambang simulasi</summary>
            <p>Nilai akademik {{ $standards['nilai_kode_etik'] }} merupakan asumsi pemodelan. Kehadiran {{ $standards['persen_kehadiran'] }}% memiliki rujukan pada ketentuan sertifikat PKPA PERADI, tetapi penerapannya sebagai aturan internal Advokat Propindo belum diverifikasi. Indikator dihitung dari nilai terkini, sedangkan prediksi dan rekomendasi berasal dari hasil terakhir yang tersimpan.</p>
        </details>
    </div>

    <section class="peserta-workspace" aria-labelledby="peringatan-title">
        <div class="peserta-section-heading">
            <div><h2 id="peringatan-title">Pemantauan peserta</h2><p class="mt-1 text-xs leading-5 text-slate-600">Tinjau indikator, prioritaskan pemeriksaan, dan buka detail pembinaan.</p></div>
            <span class="text-xs text-slate-600">Satu baris per peserta · Prediksi terbaru</span>
        </div>
        <form method="GET" action="{{ route('laporan.berisiko') }}" class="peserta-filters peringatan-filters" role="search" aria-label="Pencarian dan filter pemantauan">
            <div class="peserta-search">
                <label class="label" for="q">Cari peserta</label>
                <input class="input" type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Cari nama atau nomor peserta">
            </div>
            <div>
                <label class="label" for="angkatan_id">Angkatan</label>
                <select class="input" name="angkatan_id" id="angkatan_id">
                    <option value="">Semua angkatan</option>
                    @foreach ($angkatan as $item)
                        <option value="{{ $item->id }}" @selected($selected?->id === $item->id)>{{ $item->nama_angkatan }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="min_gagal">Estimasi Tidak Lulus min. (%)</label>
                <input class="input" id="min_gagal" name="min_gagal" type="number" min="0" max="100" step="0.01" value="{{ $filters['min_gagal'] ?? '' }}" placeholder="Tanpa batas minimum" aria-describedby="peringatan-estimate-help">
            </div>
            <div>
                <label class="label" for="urutan">Urutkan</label>
                <select class="input" name="urutan" id="urutan">
                    <option value="nama" @selected(($filters['urutan'] ?? 'nama') === 'nama')>Nama peserta A–Z</option>
                    <option value="estimasi_tertinggi" @selected(($filters['urutan'] ?? '') === 'estimasi_tertinggi')>Estimasi Tidak Lulus tertinggi</option>
                </select>
            </div>
            <div class="peserta-filter-actions">
                <button class="btn-secondary" type="submit">Terapkan</button>
                @if ($hasFilters || request()->filled('urutan'))<a class="peserta-reset" href="{{ route('laporan.berisiko') }}">Reset filter</a>@endif
            </div>
        </form>

        <div data-pagination-region="peringatan-dini" aria-busy="false">
            <div data-pagination-error data-pagination-error-message="Pemantauan peserta belum dapat dimuat. Silakan coba lagi." hidden role="alert" class="peserta-load-error">
                <p class="font-semibold">Pemantauan peserta belum dapat dimuat.</p>
                <p class="mt-1">Data sebelumnya tetap ditampilkan. Periksa koneksi Anda, lalu coba lagi.</p>
                <span data-pagination-controls><a data-pagination-retry class="peserta-reset" href="{{ request()->fullUrl() }}">Coba lagi</a></span>
            </div>
            <div class="peserta-table-context">
                <p><strong class="font-semibold tabular-nums">{{ number_format($total, 0, ',', '.') }}</strong> peserta untuk ditinjau{{ $hasFilters ? ' sesuai filter' : '' }}</p>
                <p>{{ ($filters['urutan'] ?? 'nama') === 'estimasi_tertinggi' ? 'Estimasi Tidak Lulus tertinggi' : 'Nama A–Z' }} · 20 per halaman</p>
            </div>
            <p id="peringatan-estimate-help" class="peringatan-table-help">Persentase adalah estimasi klasifikasi model pada data simulasi, bukan kepastian kelulusan. Indikator nilai ditampilkan terpisah.</p>
            <p id="peringatan-scroll-help" @class(['peserta-scroll-help', 'sr-only' => ! count($rows)])>Geser tabel ke samping untuk melihat seluruh kolom dan tindakan.</p>
            <div class="peserta-table-scroll" role="region" aria-label="Tabel pemantauan peserta" aria-describedby="peringatan-estimate-help peringatan-scroll-help" tabindex="0">
                <table @class(['peserta-table peringatan-table', 'peserta-table-is-empty' => ! count($rows)])>
                    <caption class="sr-only">Peserta dengan prediksi terbaru Berisiko Tidak Lulus, indikator nilai terkini, dan akses detail akademik.</caption>
                    <thead @class(['sr-only' => ! count($rows)])>
                        <tr><th scope="col">Peserta</th><th scope="col">Angkatan</th><th scope="col">Indikator nilai terkini</th><th scope="col" class="text-right">Rata-rata</th><th scope="col">Prediksi model terbaru</th><th scope="col" class="text-right">Tindakan</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <th scope="row" class="peserta-identity">
                                    <a href="{{ route('peserta.show', $row['id']) }}">{{ $row['nama_lengkap'] }}</a>
                                    <span class="peserta-number">{{ $row['nomor_peserta'] }}</span>
                                </th>
                                <td class="peringatan-cohort">{{ $row['angkatan'] }}</td>
                                <td class="peringatan-indicators">
                                    @if ($row['indikator'])
                                        <p class="mb-2 text-[10px] font-semibold text-amber-900">{{ count($row['indikator']) }} komponen di bawah ambang</p>
                                        <ul aria-label="Nilai di bawah ambang evaluasi simulasi">
                                            @foreach (array_slice($row['indikator'], 0, 2) as $key)
                                                <li><span>{{ $features[$key] }}</span><span class="font-medium tabular-nums"><x-score :value="$row[$key]" :percentage="$key === 'persen_kehadiran'" /></span></li>
                                            @endforeach
                                        </ul>
                                        @if (count($row['indikator']) > 2)<p class="mt-1 text-xs text-slate-600">+{{ count($row['indikator']) - 2 }} indikator lainnya di detail</p>@endif
                                    @else
                                        <p class="text-xs leading-5 text-slate-600">{{ $row['persen_kehadiran'] === null ? 'Penilaian belum tersedia' : 'Tidak ada nilai di bawah ambang' }}</p>
                                    @endif
                                </td>
                                <td class="text-right font-medium tabular-nums"><x-score :value="$row['nilai_akhir_rata']" /></td>
                                <td class="peringatan-model">
                                    <x-status :value="$row['status_prediksi']" :compact="true" />
                                    <p class="mt-1 text-xs leading-5 text-slate-600">Estimasi Tidak Lulus: <span class="font-medium tabular-nums"><x-score :value="$row['probabilitas_gagal']" :percentage="true" /></span></p>
                                    @if ($row['nilai_diperbarui'])<p class="peringatan-stale">Nilai telah diperbarui</p>@endif
                                </td>
                                <td>
                                    <a class="peserta-detail" href="{{ route('peserta.show', $row['id']) }}" data-prediction-detail="peringatan-detail-{{ $row['id'] }}" aria-haspopup="dialog" aria-label="Lihat detail pemantauan {{ $row['nama_lengkap'] }}">Lihat detail</a>
                                    @include('laporan.berisiko-detail', ['row' => $row, 'features' => $features, 'standards' => $standards])
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="peserta-empty">
                                @if ($total > 0)
                                    <h3>Halaman ini tidak memiliki peserta</h3>
                                    <p>Kembali ke halaman pertama untuk melihat hasil pemantauan.</p>
                                    <a class="btn-secondary" href="{{ $pagination->url(1) }}">Ke halaman pertama</a>
                                @elseif ($hasFilters)
                                    <h3>Tidak ada peserta yang sesuai filter</h3>
                                    <p>Coba pencarian lain atau sesuaikan angkatan dan estimasi minimum.</p>
                                    <a class="btn-secondary" href="{{ route('laporan.berisiko') }}">Reset filter</a>
                                @else
                                    <h3>Belum ada peserta dalam daftar pemantauan</h3>
                                    <p>Daftar ini hanya memuat peserta dengan prediksi terbaru Berisiko Tidak Lulus. Indikator akademik peserta lain tetap dapat ditinjau pada halaman Peserta.</p>
                                    <a class="btn-secondary" href="{{ route('peserta.index') }}">Lihat peserta</a>
                                @endif
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="peserta-table-footer">{{ $pagination->onEachSide(1)->links() }}</div>
        </div>
    </section>
    <p class="mt-4 text-xs leading-5 text-slate-600">Unduhan mencakup seluruh peserta sesuai filter, bukan hanya halaman ini. Enam atribut lengkap tersedia di detail dan laporan.</p>
</x-layout>
