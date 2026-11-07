<x-layout :title="$peserta->nama_lengkap" :description="$peserta->nomor_peserta.' · '.$peserta->angkatan->nama_angkatan">
    @if(auth()->user()->role === 'admin_akademik')
        <x-slot:actions><div class="flex flex-wrap gap-3"><a class="btn-secondary" href="{{ route('peserta.edit', $peserta) }}">Ubah data</a><form method="POST" action="{{ route('prediksi.individual', $peserta) }}" data-busy>@csrf<button type="submit" class="btn">Jalankan prediksi</button></form><form method="POST" action="{{ route('peserta.destroy', $peserta) }}" data-confirm="Hapus peserta beserta penilaiannya?">@csrf @method('DELETE')<button class="btn-danger" type="submit">Hapus peserta</button></form></div></x-slot:actions>
    @endif
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-slate-500">LUA-07 · Kartu Hasil Studi & Evaluasi Individual</p>
        @if ($peserta->penilaian)<a class="btn-secondary" href="{{ route('peserta.khs', $peserta) }}">Cetak KHS &amp; Evaluasi (PDF)</a>@endif
    </div>
    <div class="mb-8 grid gap-6 lg:grid-cols-3"><section class="panel p-6"><h2 class="mb-5 font-bold">Profil peserta</h2><dl class="grid gap-4 text-sm">@foreach(['Email' => $peserta->email, 'Nomor telepon' => $peserta->no_hp, 'Jenis kelamin' => $peserta->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan', 'Asal kampus S1' => $peserta->asal_kampus_s1, 'IPK S1' => $peserta->ipk_s1] as $label => $value)<div><dt class="text-xs text-slate-400">{{ $label }}</dt><dd class="mt-1 break-words font-medium">{{ $value }}</dd></div>@endforeach</dl></section><section class="panel p-6 lg:col-span-2"><div class="mb-6 flex items-center justify-between"><h2 class="font-bold">Komponen penilaian</h2><x-status :value="$peserta->penilaian?->status_aktual ?? 'Belum dinilai'" /></div><div class="grid gap-5 sm:grid-cols-3">@foreach($features as $name => $label)<div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">X{{ $loop->index + 1 }} · {{ $label }}</p><p class="mt-2 text-2xl font-bold">{{ $peserta->penilaian?->{$name} ?? '—' }}</p></div>@endforeach</div><div class="mt-5 rounded-xl bg-teal-50 p-4 text-sm text-teal-900">Nilai akhir rata-rata: <strong>{{ $peserta->penilaian?->nilai_akhir_rata ?? '—' }}</strong><span class="ml-2 text-xs text-teal-700">Lima komponen akademik, tanpa kehadiran.</span></div></section></div>
    <section class="panel overflow-hidden"><div class="p-6"><h2 class="font-bold">Riwayat prediksi individual</h2><p class="mt-2 text-xs text-slate-400">Hasil mencerminkan penilaian saat eksekusi. Nilai terkini dapat berbeda jika telah diperbarui.</p></div><div class="overflow-x-auto"><table class="w-full"><thead class="bg-slate-50"><tr><th>Waktu / Model</th><th>Prediksi</th><th>Peluang lulus</th><th>Rekomendasi tindak lanjut</th></tr></thead><tbody>@forelse($riwayat as $item)<tr><td>{{ $item->tanggal_prediksi->format('d/m/Y H:i') }}<p class="mt-1 text-xs text-slate-400">{{ $item->modelMl->nama_versi }} · {{ $item->prediktor->nama_lengkap }}</p></td><td><x-status :value="$item->status_prediksi" /></td><td>{{ $item->probabilitas_lulus }}%</td><td class="min-w-72 leading-relaxed text-slate-600">{{ $item->rekomendasi_tindak_lanjut }}</td></tr>@empty<tr><td colspan="4" class="empty">Belum ada prediksi untuk peserta ini.</td></tr>@endforelse</tbody></table></div><div class="p-5">{{ $riwayat->links() }}</div></section>
    <section class="panel mt-6 p-6" aria-labelledby="radar-title">
        <h2 id="radar-title" class="text-lg font-bold">Penguasaan enam komponen PKPA</h2>
        <p class="mt-2 text-xs text-slate-500">Nilai peserta dibandingkan standar minimum: kehadiran 80%, nilai akademik 70.</p>
        @if ($radarData)
            <div class="mx-auto mt-5 h-96 max-w-2xl"><canvas id="participant-radar" role="img" aria-label="Radar enam komponen penilaian. Nilai lengkap tersedia pada kartu komponen penilaian peserta."></canvas></div>
            <script id="participant-radar-data" type="application/json">{!! \Illuminate\Support\Js::encode($radarData) !!}</script>
        @else
            <p class="empty">Penilaian belum tersedia. Lengkapi keenam komponen untuk melihat radar penguasaan.</p>
        @endif
        @if ($peserta->prediksiTerbaru)
            <div class="mt-5 rounded-xl bg-teal-50 p-5 text-sm text-teal-900"><p class="font-semibold">Rekomendasi evaluasi terbaru</p><p class="mt-2 leading-relaxed">{{ $peserta->prediksiTerbaru->rekomendasi_tindak_lanjut }}</p><p class="mt-3 text-xs text-teal-700">{{ $peserta->prediksiTerbaru->modelMl->nama_versi }} · {{ $peserta->prediksiTerbaru->tanggal_prediksi->format('d/m/Y H:i') }}</p></div>
        @endif
    </section>
</x-layout>
