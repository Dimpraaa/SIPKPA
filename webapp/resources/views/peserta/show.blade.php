<x-layout :title="$peserta->nama_lengkap" :description="$peserta->nomor_peserta.' · '.$peserta->angkatan->nama_angkatan">
    @if (auth()->user()->role === 'admin_akademik')
        <x-slot:actions>
            <div class="flex flex-wrap gap-3">
                <a class="btn-secondary" href="{{ route('peserta.edit', $peserta) }}">Ubah data</a>
                <form method="POST" action="{{ route('prediksi.individual', $peserta) }}" data-busy>
                    @csrf
                    <button type="submit" class="btn">Jalankan prediksi</button>
                </form>
                <form method="POST" action="{{ route('peserta.destroy', $peserta) }}" data-confirm="Hapus peserta beserta penilaiannya?">
                    @csrf
                    @method('DELETE')
                    <button class="btn-danger" type="submit">Hapus peserta</button>
                </form>
            </div>
        </x-slot:actions>
    @endif

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a class="link inline-flex min-h-11 items-center gap-1 text-xs" href="{{ route('peserta.index', ['angkatan_id' => $peserta->angkatan_id]) }}"><x-icon name="chevron-left" class="size-4" /> Peserta {{ $peserta->angkatan->nama_angkatan }}</a>
        @if ($peserta->penilaian)
            <a class="btn-secondary" href="{{ route('peserta.khs', $peserta) }}"><x-icon name="download" /> Cetak KHS &amp; Evaluasi (PDF)</a>
        @endif
    </div>

    <div class="mb-8 grid gap-6 lg:grid-cols-3">
        <section class="panel profile-panel p-6">
            <p class="eyebrow">Identitas akademik</p>
            <h2 class="section-title mt-2 mb-5">Profil peserta</h2>
            <dl class="grid gap-4 text-sm">
                @foreach ([
                    'Email' => $peserta->email,
                    'Nomor telepon' => $peserta->no_hp,
                    'Jenis kelamin' => $peserta->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                    'Asal kampus S1' => $peserta->asal_kampus_s1,
                    'IPK S1' => number_format((float) $peserta->ipk_s1, 2, ',', '.'),
                ] as $label => $value)
                    <div class="border-b border-slate-200 pb-3 last:border-0"><dt class="text-xs text-slate-500">{{ $label }}</dt><dd class="mt-1 break-words font-medium">{{ $value }}</dd></div>
                @endforeach
            </dl>
        </section>
        <section class="panel p-6 lg:col-span-2">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div><p class="eyebrow">Nilai terkini</p><h2 class="section-title mt-2">Komponen penilaian</h2></div>
                <div><p class="mb-1 text-[10px] text-slate-500">Status kelulusan aktual</p><x-status :value="$peserta->penilaian?->status_aktual ?? 'Belum dinilai'" /></div>
            </div>
            <div class="detail-score-grid">
                @foreach ($features as $name => $label)
                    <div>
                        <p class="text-xs text-slate-500">{{ $label }}</p>
                        <p class="mt-2 text-2xl font-semibold tabular-nums"><x-score :value="$peserta->penilaian?->{$name}" :minimum="$standards[$name]" :percentage="$name === 'persen_kehadiran'" /><span class="ml-1 text-xs font-normal text-slate-500">{{ $name === 'persen_kehadiran' ? '' : '/ 100' }}</span></p>
                    </div>
                @endforeach
            </div>
            <div class="mt-5 border-l-2 border-teal-700 bg-teal-50 p-4 text-sm text-teal-900">
                Nilai akhir rata-rata: <strong class="tabular-nums"><x-score :value="$peserta->penilaian?->nilai_akhir_rata" /></strong>
                <p class="mt-1 text-xs text-teal-700">Dihitung dari lima nilai akademik, tanpa kehadiran.</p>
            </div>
        </section>
    </div>

    <section data-pagination-region="riwayat-peserta" class="panel overflow-hidden" aria-labelledby="riwayat-title">
        <div class="p-6">
            <h2 id="riwayat-title" class="font-bold">Riwayat prediksi individual</h2>
            <p class="mt-2 text-xs leading-6 text-slate-600">Hasil model tersimpan dari saat prediksi dijalankan. Riwayat tidak menyimpan salinan nilai; penilaian di atas menampilkan nilai terkini. Probabilitas model bukan jaminan kelulusan.</p>
        </div>
        <div class="overflow-x-auto" role="region" aria-label="Riwayat prediksi peserta" tabindex="0">
            <table class="w-full">
                <thead class="bg-slate-50"><tr><th scope="col">Waktu / Model</th><th scope="col">Prediksi</th><th scope="col" class="text-right">Peluang lulus</th><th scope="col">Rekomendasi tindak lanjut</th></tr></thead>
                <tbody>
                    @forelse ($riwayat as $item)
                        <tr>
                            <td class="whitespace-nowrap tabular-nums">{{ $item->tanggal_prediksi->format('d/m/Y H:i') }}<p class="mt-1 text-xs text-slate-500">{{ $item->modelMl->nama_versi }} · {{ $item->prediktor->nama_lengkap }}</p></td>
                            <td class="whitespace-nowrap"><x-status :value="$item->status_prediksi" /></td>
                            <td class="whitespace-nowrap text-right tabular-nums"><x-score :value="$item->probabilitas_lulus" :percentage="true" /></td>
                            <td class="w-80 min-w-80 leading-relaxed text-slate-600">{{ $item->rekomendasi_tindak_lanjut }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">Belum ada prediksi untuk peserta ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-5">{{ $riwayat->onEachSide(1)->links() }}</div>
    </section>

    <section class="panel mt-6 p-6" aria-labelledby="radar-title">
        <h2 id="radar-title" class="text-lg font-bold">Penguasaan komponen PKPA</h2>
        <p class="mt-2 text-xs leading-6 text-slate-600">Perbandingan enam komponen pada rentang 0–100. Kehadiran menggunakan persen; komponen akademik menggunakan nilai. Garis putus-putus menunjukkan ambang evaluasi simulasi.</p>
        <p class="chart-note mt-2">Nilai {{ $standards['nilai_kode_etik'] }} adalah asumsi pemodelan. Kehadiran {{ $standards['persen_kehadiran'] }}% memiliki rujukan PERADI; penerapannya sebagai aturan internal Advokat Propindo belum diverifikasi.</p>
        @if ($radarData)
            <div class="chart-frame mx-auto mt-5 h-80 max-w-2xl sm:h-96"><p hidden data-chart-loading="radar" class="chart-loading" role="status">Memuat visualisasi komponen…</p><canvas id="participant-radar" role="img" aria-label="Radar enam komponen penilaian. Nilai lengkap tersedia pada komponen penilaian peserta di atas."></canvas></div>
            <p data-radar-error hidden role="status" class="chart-error mt-4">Grafik belum dapat ditampilkan. Nilai lengkap tetap tersedia pada komponen penilaian di atas.</p>
            <script id="participant-radar-data" type="application/json">{!! \Illuminate\Support\Js::encode($radarData) !!}</script>
        @else
            <p class="empty">Penilaian belum tersedia. Lengkapi nilai peserta untuk menampilkan grafik.</p>
        @endif
        @if ($peserta->prediksiTerbaru)
            <div class="context-note mt-5 p-5 text-sm">
                <p class="font-semibold">Rekomendasi terbaru</p>
                <p class="mt-2 leading-relaxed">{{ $peserta->prediksiTerbaru->rekomendasi_tindak_lanjut }}</p>
                <p class="mt-3 text-xs text-slate-500 tabular-nums">{{ $peserta->prediksiTerbaru->modelMl->nama_versi }} · {{ $peserta->prediksiTerbaru->tanggal_prediksi->format('d/m/Y H:i') }}</p>
            </div>
        @endif
    </section>
</x-layout>
