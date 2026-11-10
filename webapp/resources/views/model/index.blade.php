<x-layout title="Eksperimen Model" description="Latih dan bandingkan model Random Forest, lalu pilih model untuk prediksi kelulusan.">
    <x-slot:actions><a href="{{ route('laporan.model') }}" class="btn-secondary"><x-icon name="clipboard" /> Riwayat evaluasi</a></x-slot:actions>
    <section class="model-active-banner mb-6" aria-label="Model untuk prediksi">
        <div><p class="eyebrow">Random Forest / Model operasional</p><h2>{{ $modelAktif?->nama_versi ?? 'Belum ada model aktif' }}</h2><p>{{ $modelAktif ? 'Model ini digunakan untuk prediksi baru. Riwayat tetap merujuk model saat hasil dibuat.' : 'Aktifkan salah satu hasil eksperimen untuk menjalankan prediksi baru.' }}</p></div>
        @if ($modelAktif)<div class="model-active-score"><span>Akurasi uji · Simulasi</span><strong><x-score :value="$modelAktif->akurasi" :percentage="true" /></strong><span>{{ $modelAktif->jumlah_data_uji }} data uji</span></div>@endif
    </section>
    @if (auth()->user()->role === 'admin_akademik')
        <form action="{{ route('model.store') }}" method="POST" data-busy class="panel form-section mb-8 p-6">
            @csrf
            <div class="form-section-heading">
                <div><p class="eyebrow">Konfigurasi eksperimen</p><h2 class="mt-1">Pelatihan model baru</h2>
                <p class="mt-2 text-sm text-slate-500"><span class="tabular-nums">{{ $jumlahHistoris }}</span> peserta dengan status aktual tersedia. Data dibagi menjadi 80% data latih dan 20% data uji dengan proporsi kelas yang dipertahankan.</p>
                </div>
            </div>
            <div class="grid gap-5 md:grid-cols-3">
                <x-field name="nama_versi" label="Nama versi model" :value="'RF-'.now()->format('Ymd-His')" maxlength="255" />
                <x-field name="n_estimators" label="Jumlah pohon" type="number" value="100" min="1" max="2000" />
                <x-field name="max_depth" label="Kedalaman maksimum" type="number" :required="false" min="1" max="100" placeholder="Tanpa batas jika dikosongkan" />
            </div>
            <div class="mt-6 flex flex-wrap items-center gap-4">
                <button type="submit" class="btn">Latih model</button>
                <p class="text-xs text-slate-500">Hanya peserta berstatus Lulus atau Tidak Lulus yang digunakan.</p>
            </div>
        </form>
    @endif

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3"><div><p class="eyebrow">Hasil pengujian</p><h2 class="section-title mt-1">Eksperimen tersimpan</h2></div><span class="text-xs text-slate-600 tabular-nums">{{ $models->total() }} eksperimen · Terbaru dahulu</span></div>
    <p class="chart-note mb-5">Evaluasi pada data simulasi penelitian. Metrik pengujian mengukur kinerja klasifikasi, bukan tingkat kelulusan peserta atau jaminan hasil.</p>
    <section data-pagination-region="eksperimen-model">
    <div data-pagination-content class="grid gap-6">
        @forelse ($models as $model)
            <article @class(['panel overflow-hidden', 'model-record-active' => $model->is_active])>
                <div class="flex flex-wrap items-start justify-between gap-4 p-6">
                    <div class="flex flex-wrap items-start gap-3">
                        <div>
                            <h2 class="text-lg font-bold">{{ $model->nama_versi }}</h2>
                            <p class="mt-1 text-xs text-slate-500 tabular-nums">{{ $model->created_at->format('d M Y H:i') }} · {{ $model->pembuat->nama_lengkap }}</p>
                        </div>
                        @if ($model->is_active)<x-status value="Aktif" />@endif
                    </div>
                    @if (! $model->is_active && auth()->user()->role === 'admin_akademik')
                        <form action="{{ route('model.activate', $model) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button class="btn-secondary" type="submit">Aktifkan model</button>
                        </form>
                    @endif
                </div>

                <dl class="model-parameters">
                    <div class="flex gap-1"><dt>Pohon:</dt><dd class="font-semibold tabular-nums">{{ $model->n_estimators }}</dd></div>
                    <div class="flex gap-1"><dt>Kedalaman:</dt><dd class="font-semibold tabular-nums">{{ $model->max_depth ?? 'Tanpa batas' }}</dd></div>
                    <div class="flex gap-1"><dt>Kriteria:</dt><dd class="font-semibold">{{ $model->criterion }}</dd></div>
                    <div class="flex gap-1"><dt>Data latih / uji:</dt><dd class="font-semibold tabular-nums">{{ $model->jumlah_data_latih }} / {{ $model->jumlah_data_uji }}</dd></div>
                </dl>

                <dl class="metric-strip">
                    @foreach (['akurasi' => 'Akurasi', 'presisi' => 'Presisi', 'recall' => 'Recall', 'f1_score' => 'F1-Score'] as $key => $label)
                        <div>
                            <dt>{{ $label }}</dt>
                            <dd><x-score :value="$model->{$key}" :percentage="true" />
                            <div class="metric-meter" aria-hidden="true"><span style="width: {{ max(0, min(100, (float) $model->{$key})) }}%"></span></div>
                            <p>{{ ['akurasi' => 'Seluruh klasifikasi yang benar', 'presisi' => 'Ketepatan prediksi kelas Lulus', 'recall' => 'Kelas Lulus yang berhasil dikenali', 'f1_score' => 'Keseimbangan presisi dan recall'][$key] }}</p>
                            </dd>
                        </div>
                    @endforeach
                </dl>

                <details class="model-diagnostics" @if ($model->is_active) open @endif>
                    <summary>Matriks klasifikasi dan kontribusi komponen</summary>
                    <div class="analytics-grid p-6">
                        <div class="min-w-0">
                            <h3 class="section-title">Confusion matrix</h3>
                            <p class="chart-note mt-1 mb-4">{{ $model->jumlah_data_uji }} data uji · Satuan: kasus · Kelas positif: Lulus</p>
                            <div class="overflow-x-auto" role="region" aria-label="Confusion matrix {{ $model->nama_versi }}" tabindex="0">
                            <table class="matrix-table">
                                <caption class="sr-only">Baris menunjukkan kelas aktual; kolom menunjukkan kelas prediksi.</caption>
                                <thead><tr><th scope="col">Aktual ↓ / Prediksi →</th><th scope="col">Lulus (+)</th><th scope="col">Tidak Lulus (−)</th></tr></thead>
                                <tbody>
                                    <tr><th scope="row">Lulus (+)</th><td class="matrix-correct"><strong>{{ $model->confusion_matrix_json['TP'] ?? '—' }}</strong><span>TP · Lulus dikenali benar</span></td><td class="matrix-error"><strong>{{ $model->confusion_matrix_json['FN'] ?? '—' }}</strong><span>FN · Lulus diprediksi tidak lulus</span></td></tr>
                                    <tr><th scope="row">Tidak Lulus (−)</th><td class="matrix-error"><strong>{{ $model->confusion_matrix_json['FP'] ?? '—' }}</strong><span>FP · Tidak lulus diprediksi lulus</span></td><td class="matrix-correct"><strong>{{ $model->confusion_matrix_json['TN'] ?? '—' }}</strong><span>TN · Tidak lulus dikenali benar</span></td></tr>
                                </tbody>
                            </table>
                            </div>
                            <p class="matrix-key">FP dan FN adalah kesalahan klasifikasi. TP dan TN adalah klasifikasi yang benar pada data uji.</p>
                        </div>
                        <div class="min-w-0">
                        <h3 class="section-title">Kontribusi relatif fitur</h3>
                        <p class="chart-note mt-1 mb-4">Feature importance · Persentase bobot model, bukan hubungan sebab-akibat.</p>
                        <dl class="feature-bars">
                            @foreach ($features as $key => $label)
                                @php($importance = isset($model->feature_importance_json[$key]) && is_numeric($model->feature_importance_json[$key]) ? $model->feature_importance_json[$key] * 100 : null)
                                <div><dt>{{ $label }}</dt><dd><x-score :value="$importance" :percentage="true" /></dd>@if ($importance !== null)<dd class="feature-track" aria-hidden="true"><span style="width: {{ max(0, min(100, $importance)) }}%"></span></dd>@endif</div>
                            @endforeach
                        </dl>
                        </div>
                    </div>
                </details>
            </article>
        @empty
            <div class="panel empty">Belum ada model. Admin Akademik dapat memulai pelatihan menggunakan data historis.</div>
        @endforelse
    </div>
    <div class="mt-6 border-t border-slate-200 pt-5">{{ $models->onEachSide(1)->links() }}</div>
    </section>
</x-layout>
