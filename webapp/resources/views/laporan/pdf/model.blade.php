@extends('laporan.pdf.layout')
@section('content')
    <p class="meta">LUA-08 · Lampiran pengujian parameter algoritma Random Forest · Kriteria: {{ $filters['criterion'] ?? 'Semua' }} · {{ $models->count() }} eksperimen.</p>
    <table><thead><tr><th style="width:20px">No.</th><th style="width:115px">Versi / Waktu</th><th>Pembuat</th><th>n_estimators</th><th>max_depth</th><th>criterion</th><th>Data latih</th><th>Data uji</th><th>Akurasi (%)</th><th>Presisi (%)</th><th>Recall (%)</th><th>F1-Score (%)</th><th>Status</th></tr></thead><tbody>
        @forelse ($models as $model)
            <tr><td>{{ $loop->iteration }}</td><td>{{ $model->nama_versi }}<br><span class="muted">{{ $model->created_at->format('d/m/Y H:i') }}</span></td><td>{{ $model->pembuat->nama_lengkap }}</td><td>{{ $model->n_estimators }}</td><td>{{ $model->max_depth ?? 'Tanpa batas' }}</td><td>{{ $model->criterion }}</td><td>{{ $model->jumlah_data_latih }}</td><td>{{ $model->jumlah_data_uji }}</td>@foreach (['akurasi', 'presisi', 'recall', 'f1_score'] as $metric)<td>{{ number_format((float) $model->{$metric}, 2, ',', '.') }}</td>@endforeach<td>{{ $model->is_active ? 'Aktif' : 'Tidak aktif' }}</td></tr>
        @empty
            <tr><td colspan="13" style="text-align:center;padding:20px">Belum ada eksperimen yang sesuai filter.</td></tr>
        @endforelse
    </tbody></table>
    <p class="meta">Metrik ditampilkan dalam persen, dengan Lulus sebagai kelas positif. Data bersumber dari log model_ml; setiap baris mewakili satu eksperimen yang telah disimpan.</p>
    <div class="signature keep">Disusun oleh,<br>Bagian Akademik Advokat Propindo<br><br>{{ $printedBy }}</div>
@endsection
