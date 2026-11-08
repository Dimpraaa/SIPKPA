<x-layout title="Peserta PKPA" description="Kelola data peserta, penilaian akademik, dan dokumen hasil belajar." page="peserta">
    @php
        $isAdmin = auth()->user()->role === 'admin_akademik';
        $hasFilters = request()->filled('q') || request()->filled('angkatan_id') || request()->filled('status_aktual');
    @endphp

    @if (auth()->user()->role === 'admin_akademik')
        <x-slot:actions>
            <div class="peserta-header-actions">
                <a class="btn" href="{{ route('peserta.create') }}"><x-icon name="plus" /> Tambah peserta</a>
            </div>
        </x-slot:actions>
    @endif

    <section class="peserta-workspace" aria-labelledby="peserta-list-title">
        <div class="peserta-section-heading">
            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <h2 id="peserta-list-title" class="font-semibold">Daftar peserta</h2>
                <span class="text-sm text-slate-500 tabular-nums">{{ number_format($peserta->total(), 0, ',', '.') }} peserta{{ $hasFilters ? ' sesuai filter' : ' terdaftar' }}</span>
            </div>
            @if ($isAdmin)
                <a class="peserta-import" href="{{ route('penilaian.import') }}">Impor nilai <x-icon name="arrow-right" class="size-4" /></a>
            @endif
        </div>

        <form method="GET" action="{{ route('peserta.index') }}" class="peserta-filters" role="search" aria-label="Pencarian dan filter peserta">
            <div class="peserta-search">
                <label class="label" for="q">Cari peserta</label>
                <input class="input" type="search" name="q" id="q" value="{{ request('q') }}" placeholder="Cari nama atau nomor peserta" maxlength="100">
            </div>
            <div>
                <label class="label" for="angkatan_id">Angkatan</label>
                <select class="input" name="angkatan_id" id="angkatan_id">
                    <option value="">Semua angkatan</option>
                    @foreach ($angkatan as $item)
                        <option value="{{ $item->id }}" @selected(request('angkatan_id') == $item->id)>{{ $item->nama_angkatan }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="status_aktual">Status aktual</label>
                <select class="input" name="status_aktual" id="status_aktual">
                    <option value="">Semua status</option>
                    @foreach (['Lulus' => 'Lulus', 'Tidak Lulus' => 'Tidak Lulus', 'Belum Ditentukan' => 'Belum Ditentukan', 'belum_dinilai' => 'Belum dinilai'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status_aktual') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="peserta-filter-actions">
                <button class="btn-secondary" type="submit">Terapkan</button>
                @if ($hasFilters)
                    <a class="peserta-reset" href="{{ route('peserta.index') }}">Reset filter</a>
                @endif
            </div>
        </form>

        <div data-pagination-region="peserta" aria-busy="false">
            <div data-pagination-error hidden role="alert" class="peserta-load-error">
                <p class="font-semibold">Daftar peserta belum dapat dimuat.</p>
                <p class="mt-1">Data sebelumnya tetap ditampilkan. Periksa koneksi Anda, lalu coba lagi.</p>
                <span data-pagination-controls><a data-pagination-retry class="peserta-reset" href="{{ request()->fullUrl() }}">Coba lagi</a></span>
            </div>
            <div class="peserta-table-context">
                <p id="peserta-status-help">Status aktual adalah keputusan akademik, bukan hasil prediksi.</p>
                <p>Nama A–Z <span aria-hidden="true">·</span> 20 per halaman</p>
            </div>
            <p id="peserta-scroll-help" @class(['peserta-scroll-help', 'sr-only' => $peserta->isEmpty()])>Geser tabel ke samping untuk melihat seluruh kolom dan tindakan.</p>
            <div class="peserta-table-scroll" role="region" aria-label="Tabel peserta PKPA" aria-describedby="peserta-status-help peserta-scroll-help" tabindex="0">
                <table @class(['peserta-table', 'peserta-table-is-empty' => $peserta->isEmpty()])>
                    <caption class="sr-only">Daftar peserta PKPA beserta angkatan, kehadiran, nilai rata-rata, dan status kelulusan aktual.</caption>
                    <thead @class(['sr-only' => $peserta->isEmpty()])>
                        <tr><th scope="col">Peserta</th><th scope="col">Angkatan</th><th scope="col" class="text-right">Kehadiran</th><th scope="col" class="text-right">Rata-rata</th><th scope="col">Status aktual</th><th scope="col" class="text-right">Tindakan</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($peserta as $item)
                            <tr>
                                <th scope="row" class="peserta-identity">
                                    <a href="{{ route('peserta.show', $item) }}">{{ $item->nama_lengkap }}</a>
                                    <span class="peserta-number">{{ $item->nomor_peserta }}</span>
                                </th>
                                <td class="peserta-cohort">{{ $item->angkatan->nama_angkatan }}</td>
                                <td class="text-right tabular-nums">
                                    <x-score :value="$item->penilaian?->persen_kehadiran" :minimum="$standards['persen_kehadiran']" :percentage="true" />
                                    @if ($item->penilaian && $item->penilaian->persen_kehadiran < $standards['persen_kehadiran'])
                                        <span class="peserta-below-standard">Di bawah {{ $standards['persen_kehadiran'] }}%</span>
                                    @endif
                                </td>
                                <td class="text-right font-semibold tabular-nums"><x-score :value="$item->penilaian?->nilai_akhir_rata" /></td>
                                <td class="peserta-status"><x-status :value="$item->penilaian?->status_aktual ?? 'Belum dinilai'" :compact="true" /></td>
                                <td>
                                    <div class="peserta-row-actions">
                                        <a class="peserta-detail" href="{{ route('peserta.show', $item) }}" aria-label="Lihat detail {{ $item->nama_lengkap }}">Lihat detail</a>
                                        @if ($item->penilaian || $isAdmin)
                                            <x-action-menu :label="'Tindakan lainnya untuk '.$item->nama_lengkap">
                                                @if ($item->penilaian)
                                                    <a href="{{ route('peserta.khs', $item) }}"><x-icon name="download" /> Unduh KHS (PDF)</a>
                                                @endif
                                                @if ($isAdmin)
                                                    <a href="{{ route('peserta.edit', $item) }}"><x-icon name="edit" /> Ubah data &amp; nilai</a>
                                                @endif
                                            </x-action-menu>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="peserta-empty">
                                    @if ($peserta->total() > 0)
                                        <h3>Halaman ini tidak memiliki peserta</h3>
                                        <p>Kembali ke halaman pertama untuk melihat data peserta.</p>
                                        <a class="btn-secondary" href="{{ $peserta->url(1) }}">Ke halaman pertama</a>
                                    @elseif ($hasFilters)
                                        <h3>Peserta tidak ditemukan</h3>
                                        <p>Coba nama atau nomor lain, atau sesuaikan filter angkatan dan status.</p>
                                        <a class="btn-secondary" href="{{ route('peserta.index') }}">Reset filter</a>
                                    @else
                                        <h3>Belum ada peserta PKPA</h3>
                                        <p>{{ $isAdmin ? 'Tambahkan peserta untuk mulai mengelola data dan penilaian akademik.' : 'Data peserta akan tampil setelah didaftarkan oleh admin akademik.' }}</p>
                                        @if ($isAdmin)
                                            <a class="btn-secondary" href="{{ route('peserta.create') }}">Tambah peserta</a>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="peserta-table-footer">{{ $peserta->onEachSide(1)->links() }}</div>
        </div>
    </section>
</x-layout>
