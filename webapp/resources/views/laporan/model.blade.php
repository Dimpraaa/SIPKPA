<x-layout :title="$title" description="LUA-08 · Log perbandingan parameter, pembagian data, dan evaluasi eksperimen Random Forest.">
    <x-slot:actions><a class="btn" href="{{ route('laporan.model.pdf', $filters) }}">Cetak PDF Lampiran Pengujian</a></x-slot:actions>
    @include('laporan.tabs')
    <form action="{{ route('laporan.model') }}" method="GET" class="panel mb-6 flex flex-wrap items-end gap-4 p-5"><div><label class="label" for="criterion">Kriteria pemisahan</label><select id="criterion" name="criterion" class="input min-w-56"><option value="">Semua kriteria</option><option value="gini" @selected(($filters['criterion'] ?? '') === 'gini')>Gini</option><option value="entropy" @selected(($filters['criterion'] ?? '') === 'entropy')>Entropy</option></select></div><button class="btn" type="submit">Terapkan</button></form>
    <div class="panel overflow-hidden"><div class="overflow-x-auto"><table class="w-full"><thead class="bg-slate-50"><tr><th>Versi / Waktu</th><th>Pembuat</th><th>Pohon</th><th>Kedalaman</th><th>Kriteria</th><th>Latih / Uji</th><th>Akurasi</th><th>Presisi</th><th>Recall</th><th>F1-Score</th><th>Status</th></tr></thead><tbody>
        @forelse ($models as $model)
            <tr><td class="min-w-48 font-semibold">{{ $model->nama_versi }}<p class="mt-1 text-xs font-normal text-slate-400">{{ $model->created_at->format('d/m/Y H:i') }}</p></td><td>{{ $model->pembuat->nama_lengkap }}</td><td>{{ $model->n_estimators }}</td><td>{{ $model->max_depth ?? 'Tanpa batas' }}</td><td>{{ $model->criterion }}</td><td>{{ $model->jumlah_data_latih }} / {{ $model->jumlah_data_uji }}</td>@foreach (['akurasi', 'presisi', 'recall', 'f1_score'] as $metric)<td class="font-semibold text-teal-800">{{ $model->{$metric} }}%</td>@endforeach<td><x-status :value="$model->is_active ? 'Aktif' : 'Tidak aktif'" /></td></tr>
        @empty
            <tr><td colspan="11" class="empty">Belum ada eksperimen model yang sesuai filter.</td></tr>
        @endforelse
    </tbody></table></div><div class="p-5">{{ $models->links() }}</div></div>
</x-layout>
