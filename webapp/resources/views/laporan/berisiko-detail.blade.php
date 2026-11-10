<dialog id="peringatan-detail-{{ $row['id'] }}" class="prediksi-dialog" aria-labelledby="peringatan-detail-title-{{ $row['id'] }}" aria-describedby="peringatan-detail-description-{{ $row['id'] }}">
    <div class="prediksi-dialog-header">
        <div class="min-w-0">
            <p class="prediksi-eyebrow">Detail pemantauan · Data simulasi</p>
            <h2 id="peringatan-detail-title-{{ $row['id'] }}" class="mt-1 text-lg font-semibold">{{ $row['nama_lengkap'] }}</h2>
            <p class="mt-1 text-xs text-slate-600">{{ $row['nomor_peserta'] }} · {{ $row['angkatan'] }}</p>
        </div>
        <form method="dialog"><button type="submit" class="btn-secondary" autofocus>Tutup</button></form>
    </div>
    <div class="prediksi-dialog-body">
        <p id="peringatan-detail-description-{{ $row['id'] }}" class="text-sm leading-6 text-slate-600">Indikator penilaian, prediksi model, dan status kelulusan aktual merupakan tiga informasi yang berbeda.</p>
        <section aria-labelledby="peringatan-grades-{{ $row['id'] }}">
            <h3 id="peringatan-grades-{{ $row['id'] }}" class="prediksi-detail-heading">Indikator penilaian terkini</h3>
            <p class="mt-2 text-xs leading-5 text-slate-600">Ambang evaluasi simulasi digunakan untuk meninjau nilai, bukan menetapkan peserta pasti gagal.</p>
            <dl class="prediksi-detail-features">
                @foreach ($features as $key => $label)
                    <div>
                        <dt>{{ $label }}</dt>
                        <dd>
                            <span class="font-semibold tabular-nums"><x-score :value="$row[$key]" :percentage="$key === 'persen_kehadiran'" /></span>
                            <span class="peringatan-threshold">Ambang simulasi: <x-score :value="$standards[$key]" :percentage="$key === 'persen_kehadiran'" /></span>
                            @if ($row[$key] === null)
                                <span class="peringatan-condition">Nilai belum tersedia</span>
                            @elseif (in_array($key, $row['indikator'], true))
                                <span class="peringatan-condition peringatan-below">Di bawah ambang</span>
                            @else
                                <span class="peringatan-condition">Tidak di bawah ambang</span>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-4 text-xs leading-5 text-slate-600">Nilai akademik {{ $standards['nilai_kode_etik'] }} adalah asumsi pemodelan. Kehadiran {{ $standards['persen_kehadiran'] }}% memiliki rujukan pada ketentuan sertifikat PKPA PERADI; penerapannya sebagai aturan internal Advokat Propindo belum diverifikasi.</p>
        </section>
        <section aria-labelledby="peringatan-model-{{ $row['id'] }}">
            <h3 id="peringatan-model-{{ $row['id'] }}" class="prediksi-detail-heading">Prediksi Random Forest terbaru</h3>
            <dl class="prediksi-detail-results">
                <div><dt>Klasifikasi model</dt><dd><x-status :value="$row['status_prediksi']" :compact="true" /></dd></div>
                <div><dt>Estimasi kelas Lulus</dt><dd class="font-semibold tabular-nums"><x-score :value="$row['probabilitas_lulus']" :percentage="true" /></dd></div>
                <div><dt>Estimasi kelas Tidak Lulus</dt><dd class="font-semibold tabular-nums"><x-score :value="$row['probabilitas_gagal']" :percentage="true" />@if ($row['probabilitas_gagal'] !== null)<div class="metric-meter probability-risk" aria-hidden="true"><span style="width: {{ max(0, min(100, (float) $row['probabilitas_gagal'])) }}%"></span></div>@endif</dd></div>
            </dl>
            <p class="mt-3 text-xs leading-5 text-slate-600">Estimasi klasifikasi berdasarkan data simulasi, bukan kepastian kelulusan. Prediksi tidak dihitung ulang dari indikator di atas.</p>
            <dl class="prediksi-detail-metadata">
                <div><dt>Model</dt><dd>{{ $row['model'] ?? 'Belum tersedia' }}</dd></div>
                <div><dt>Waktu prediksi</dt><dd class="tabular-nums">{{ $row['tanggal_prediksi'] ?? 'Belum tersedia' }}</dd></div>
            </dl>
            @if ($row['nilai_diperbarui'])<p class="peringatan-stale mt-3">Nilai telah diperbarui. Jalankan ulang prediksi untuk memperbarui rekomendasi.</p>@endif
            <h4 class="mt-5 text-sm font-medium">Rekomendasi pembinaan tersimpan</h4>
            <p class="prediksi-recommendation">{{ $row['rekomendasi_tampilan'] ?? 'Belum tersedia rekomendasi model.' }}</p>
            <p class="mt-3 text-xs leading-5 text-slate-600">Riwayat tidak menyimpan salinan nilai saat prediksi. Rekomendasi berasal dari hasil prediksi terakhir; angka ambangnya merupakan asumsi simulasi, bukan standar resmi Advokat Propindo.</p>
        </section>
        <section aria-labelledby="peringatan-actual-{{ $row['id'] }}">
            <h3 id="peringatan-actual-{{ $row['id'] }}" class="prediksi-detail-heading">Status kelulusan aktual</h3>
            <p class="mt-3"><x-status :value="$row['status_aktual']" :compact="true" /></p>
            <p class="mt-2 text-xs leading-5 text-slate-600">Status tercatat pada penilaian dan tidak ditetapkan oleh indikator peringatan maupun prediksi model.</p>
        </section>
    </div>
    <div class="prediksi-dialog-footer">
        <div class="flex flex-wrap justify-end gap-3">
            <a class="btn-secondary" href="{{ route('prediksi.index', ['q' => $row['nomor_peserta']]) }}">Riwayat prediksi</a>
            <a class="btn" href="{{ route('peserta.show', $row['id']) }}">Profil &amp; penilaian</a>
        </div>
    </div>
</dialog>
