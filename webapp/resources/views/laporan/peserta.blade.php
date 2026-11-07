<x-layout :title="$title" :description="$kode.' · Data penilaian terkini dan prediksi terbaru per peserta.'">
    <x-slot:actions>
        <div class="flex flex-wrap gap-3">
            <a class="btn-secondary" href="{{ route($risiko ? 'laporan.berisiko.export' : 'laporan.rekap.export', ['format' => 'pdf', ...$filters]) }}">↓ Export PDF</a>
            <a class="btn" href="{{ route($risiko ? 'laporan.berisiko.export' : 'laporan.rekap.export', ['format' => 'excel', ...$filters]) }}">↓ Export Excel</a>
        </div>
    </x-slot:actions>
    @include('laporan.tabs')
    <form method="GET" action="{{ route($risiko ? 'laporan.berisiko' : 'laporan.rekap') }}" class="panel mb-6 flex flex-wrap items-end gap-4 p-5">
        <div class="min-w-56"><label for="angkatan_id" class="label">Angkatan</label><select class="input" name="angkatan_id" id="angkatan_id">
            @if ($risiko)<option value="">Semua angkatan</option>@endif
            @foreach ($angkatan as $item)<option value="{{ $item->id }}" @selected($selected?->id === $item->id)>{{ $item->nama_angkatan }}</option>@endforeach
        </select></div>
        <div class="min-w-56 flex-1"><label for="q" class="label">Nama / nomor peserta</label><input class="input" id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Cari peserta"></div>
        @if ($risiko)<div><label class="label" for="min_gagal">Peluang gagal minimum (%)</label><input class="input w-48" id="min_gagal" name="min_gagal" type="number" min="0" max="100" step="0.01" value="{{ $filters['min_gagal'] ?? '' }}"></div>@endif
        <button class="btn" type="submit">Terapkan filter</button><a class="btn-secondary" href="{{ route($risiko ? 'laporan.berisiko' : 'laporan.rekap') }}">Reset</a>
    </form>
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3 text-sm"><p class="text-slate-500"><strong class="text-slate-900">{{ $total }}</strong> peserta sesuai filter · {{ $selected?->nama_angkatan ?? 'Semua angkatan' }}</p><p class="text-xs text-slate-400">Merah: kehadiran &lt;80% atau komponen nilai &lt;70.</p></div>
    <div class="panel overflow-hidden"><div class="overflow-x-auto"><table class="w-full"><thead class="bg-slate-50"><tr>@foreach ($columns as $label)<th>{{ $label }}</th>@endforeach</tr></thead><tbody>
        @forelse ($rows as $row)
            <tr>
                @foreach ($columns as $key => $label)
                    <td @class(['text-sm', 'min-w-72' => $key === 'rekomendasi', 'min-w-44' => in_array($key, ['nama_lengkap', 'model']), 'bg-rose-50 font-bold text-rose-700' => isset($standards[$key]) && $row[$key] !== null && $row[$key] < $standards[$key]])>
                        @if ($key === 'nama_lengkap')
                            <a class="link" href="{{ route('peserta.show', $row['id']) }}">{{ $row[$key] }}</a>
                        @elseif (in_array($key, ['status_aktual', 'status_prediksi']))
                            <x-status :value="$row[$key]" />
                        @elseif (is_float($row[$key]))
                            {{ number_format($row[$key], 2, ',', '.') }}{{ in_array($key, ['persen_kehadiran', 'probabilitas_lulus', 'probabilitas_gagal']) ? '%' : '' }}
                        @else
                            {{ $row[$key] ?? '—' }}
                        @endif
                        @if ($key === 'rekomendasi' && $row['nilai_diperbarui'])<p class="mt-2 text-xs font-semibold text-amber-700">Nilai diperbarui setelah prediksi. Jalankan ulang prediksi untuk evaluasi terkini.</p>@endif
                    </td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($columns) }}" class="empty">{{ $risiko ? 'Tidak ada peserta dengan prediksi terbaru Berisiko Tidak Lulus sesuai filter.' : 'Belum ada peserta pada angkatan atau pencarian ini.' }}</td></tr>
        @endforelse
    </tbody></table></div><div class="border-t border-slate-100 p-5">{{ $pagination->links() }}</div></div>
    <p class="mt-5 text-xs leading-relaxed text-slate-400">Ekspor mencakup seluruh hasil filter, bukan hanya halaman yang sedang dibuka. Setiap peserta dihitung satu kali menggunakan prediksi terbaru berdasarkan tanggal dan ID. Nilai merupakan penilaian terkini; rekomendasi dicatat saat prediksi dijalankan.</p>
</x-layout>
