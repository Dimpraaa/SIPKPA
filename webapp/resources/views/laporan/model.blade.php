<x-layout :title="$title" description="Bandingkan parameter pelatihan, pembagian data, dan hasil evaluasi model Random Forest.">
    <x-slot:actions>
        <a class="btn" href="{{ route('laporan.model.pdf', $filters) }}"><x-icon name="download" /> Unduh PDF</a>
    </x-slot:actions>
    @include('laporan.tabs')

    <p class="context-note mb-6">Metrik berasal dari pengujian data simulasi. Bandingkan parameter bersama jumlah data latih/uji; hasil eksperimen dengan cakupan data berbeda tidak langsung setara. Kelas positif evaluasi: Lulus.</p>
    <form action="{{ route('laporan.model') }}" method="GET" class="filter-bar mb-6">
        <div>
            <label class="label" for="criterion">Kriteria pemisahan</label>
            <select id="criterion" name="criterion" class="input min-w-56">
                <option value="">Semua kriteria</option>
                <option value="gini" @selected(($filters['criterion'] ?? '') === 'gini')>Gini</option>
                <option value="entropy" @selected(($filters['criterion'] ?? '') === 'entropy')>Entropy</option>
            </select>
        </div>
        <button class="btn" type="submit">Terapkan</button>
        <a class="btn-secondary" href="{{ route('laporan.model') }}">Reset</a>
    </form>

    <div data-pagination-region="laporan-model" class="panel overflow-hidden">
        <div class="table-heading"><div><p class="eyebrow">Perbandingan eksperimen</p><h2 class="section-title mt-1">Parameter &amp; metrik pengujian</h2><p>Metrik dalam persen · Terbaru dahulu</p></div><span class="text-xs text-slate-600 tabular-nums">{{ $models->total() }} model{{ ! empty($filters['criterion']) ? ' sesuai filter' : ' tercatat' }}</span></div>
        <div class="overflow-x-auto" role="region" aria-label="Riwayat evaluasi model" tabindex="0">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col">Versi / Waktu</th><th scope="col">Pembuat</th><th scope="col" class="text-right">Pohon</th><th scope="col" class="text-right">Kedalaman</th><th scope="col">Kriteria</th><th scope="col" class="text-right">Latih / Uji</th>
                        @foreach (['Akurasi', 'Presisi', 'Recall', 'F1-Score'] as $metric)<th scope="col" class="text-right">{{ $metric }}</th>@endforeach
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($models as $model)
                        <tr @class(['hover:bg-slate-50', 'model-comparison-active' => $model->is_active])>
                            <td class="whitespace-nowrap font-semibold">{{ $model->nama_versi }}<p class="mt-1 text-xs font-normal text-slate-500 tabular-nums">{{ $model->created_at->format('d/m/Y H:i') }}</p></td>
                            <td class="whitespace-nowrap">{{ $model->pembuat->nama_lengkap }}</td>
                            <td class="whitespace-nowrap text-right tabular-nums">{{ $model->n_estimators }}</td>
                            <td class="whitespace-nowrap text-right tabular-nums">{{ $model->max_depth ?? 'Tanpa batas' }}</td>
                            <td class="whitespace-nowrap">{{ $model->criterion }}</td>
                            <td class="whitespace-nowrap text-right tabular-nums">{{ $model->jumlah_data_latih }} / {{ $model->jumlah_data_uji }}</td>
                            @foreach (['akurasi', 'presisi', 'recall', 'f1_score'] as $metric)
                                <td class="min-w-28 whitespace-nowrap text-right font-semibold text-teal-800 tabular-nums"><x-score :value="$model->{$metric}" :percentage="true" /><div class="metric-meter" aria-hidden="true"><span style="width: {{ max(0, min(100, (float) $model->{$metric})) }}%"></span></div></td>
                            @endforeach
                            <td class="whitespace-nowrap"><x-status :value="$model->is_active ? 'Aktif' : 'Tidak aktif'" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="empty">Belum ada eksperimen model yang sesuai filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">{{ $models->onEachSide(1)->links() }}</div>
    </div>
</x-layout>
