<dialog id="prediksi-detail-{{ $item->id }}" class="prediksi-dialog" aria-labelledby="prediksi-title-{{ $item->id }}" aria-describedby="prediksi-description-{{ $item->id }}">
    <div class="prediksi-dialog-header">
        <div class="min-w-0">
            <p class="prediksi-eyebrow">Detail prediksi <span aria-hidden="true">·</span> Data simulasi</p>
            <h2 id="prediksi-title-{{ $item->id }}" class="mt-1 text-lg font-semibold">{{ $item->peserta->nama_lengkap }}</h2>
            <p class="mt-1 text-xs text-slate-600">{{ $item->peserta->nomor_peserta }} <span aria-hidden="true">·</span> {{ $item->peserta->angkatan->nama_angkatan }}</p>
        </div>
        <form method="dialog"><button type="submit" class="btn-secondary" autofocus>Tutup</button></form>
    </div>
    <div class="prediksi-dialog-body">
        <p id="prediksi-description-{{ $item->id }}" class="text-sm leading-6 text-slate-600">Hasil klasifikasi model untuk evaluasi penelitian, bukan keputusan kelulusan resmi.</p>

        <section aria-labelledby="prediksi-result-{{ $item->id }}">
            <h3 id="prediksi-result-{{ $item->id }}" class="prediksi-detail-heading">Hasil klasifikasi</h3>
            <dl class="prediksi-detail-results">
                <div><dt>Klasifikasi model</dt><dd><x-status :value="$item->status_prediksi" :compact="true" /></dd></div>
                <div><dt>Estimasi kelas Lulus</dt><dd class="font-semibold tabular-nums"><x-score :value="$item->probabilitas_lulus" :percentage="true" /><div class="metric-meter" aria-hidden="true"><span style="width: {{ max(0, min(100, (float) $item->probabilitas_lulus)) }}%"></span></div></dd></div>
                <div><dt>Estimasi kelas Tidak Lulus</dt><dd class="font-semibold tabular-nums"><x-score :value="$item->probabilitas_gagal" :percentage="true" /><div class="metric-meter probability-risk" aria-hidden="true"><span style="width: {{ max(0, min(100, (float) $item->probabilitas_gagal)) }}%"></span></div></dd></div>
            </dl>
            <p class="mt-3 text-xs leading-5 text-slate-600">Persentase merupakan estimasi klasifikasi model berdasarkan data simulasi, bukan kepastian kelulusan.</p>
            <dl class="prediksi-detail-metadata">
                <div><dt>Model yang digunakan</dt><dd>{{ $item->modelMl->nama_versi }}</dd></div>
                <div><dt>Waktu prediksi</dt><dd class="tabular-nums">{{ $item->tanggal_prediksi->format('d/m/Y H:i:s') }}</dd></div>
                <div><dt>Dijalankan oleh</dt><dd>{{ $item->prediktor->nama_lengkap }}</dd></div>
            </dl>
        </section>

        <section aria-labelledby="prediksi-grades-{{ $item->id }}">
            <h3 id="prediksi-grades-{{ $item->id }}" class="prediksi-detail-heading">Penilaian terhubung · Nilai terkini</h3>
            <p class="mt-2 text-xs leading-5 text-slate-600">Riwayat tidak menyimpan salinan nilai saat prediksi. Nilai berikut dapat berbeda dari nilai ketika hasil ini dibuat.</p>
            <dl class="prediksi-detail-features">
                @foreach ($features as $name => $label)
                    <div><dt>{{ $label }}</dt><dd class="font-semibold tabular-nums"><x-score :value="$item->penilaian?->{$name}" :percentage="$name === 'persen_kehadiran'" /></dd></div>
                @endforeach
            </dl>
            <p class="mt-4 flex flex-wrap items-center gap-2 text-sm"><span class="text-slate-600">Status kelulusan aktual saat ini:</span> <x-status :value="$item->penilaian?->status_aktual ?? 'Belum dinilai'" :compact="true" /></p>
        </section>

        <section aria-labelledby="prediksi-recommendation-{{ $item->id }}">
            <h3 id="prediksi-recommendation-{{ $item->id }}" class="prediksi-detail-heading">Rekomendasi model · Simulasi</h3>
            @if ($recommendation['focus'])
                <p class="mt-3 text-xs leading-5 text-slate-600">Fokus dalam rekomendasi: {{ implode(' · ', $recommendation['focus']) }}.</p>
            @endif
            <p class="prediksi-recommendation">{{ $recommendation['text'] }}</p>
            <p class="mt-3 text-xs leading-5 text-slate-600">Angka ambang dalam rekomendasi merupakan asumsi simulasi pemodelan, bukan standar resmi Advokat Propindo. Nilai 70 adalah asumsi pemodelan; kehadiran 80% belum diverifikasi sebagai aturan internal resmi.</p>
        </section>
    </div>
    <div class="prediksi-dialog-footer">
        @if ($isAdmin)
            <p class="text-xs leading-5 text-slate-600">{{ $modelAktif ? 'Prediksi ulang menggunakan model aktif '.$modelAktif->nama_versi.' dan nilai terkini.' : 'Prediksi ulang belum tersedia karena tidak ada model aktif.' }}</p>
        @endif
        <div class="flex flex-wrap justify-end gap-3">
            <a class="btn-secondary" href="{{ route('peserta.show', $item->peserta) }}">Profil &amp; penilaian</a>
            @if ($isAdmin)
                <form action="{{ route('prediksi.individual', $item->peserta) }}" method="POST" data-busy>
                    @csrf
                    <button type="submit" class="btn" @disabled(! $modelAktif || ! $item->penilaian)>Prediksi ulang</button>
                </form>
            @endif
        </div>
    </div>
</dialog>
