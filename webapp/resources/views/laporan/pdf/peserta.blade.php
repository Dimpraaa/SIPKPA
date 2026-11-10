@extends('laporan.pdf.layout')

@section('content')
    <p class="meta">Angkatan: {{ $selected?->nama_angkatan ?? 'Semua angkatan' }} · Jumlah peserta: {{ $total }} · Pencarian: {{ $filters['q'] ?? 'Semua' }}@if ($risiko) · Ambang Risiko Minimum: {{ number_format((float) ($filters['min_gagal'] ?? 0), 2, ',', '.') }}%@endif</p>
    <p class="meta"><span class="dot"></span>Di bawah standar: kehadiran &lt;{{ $standards['persen_kehadiran'] }}% atau nilai akademik &lt;{{ $standards['nilai_kode_etik'] }}. Prediksi menggunakan hasil terakhir setiap peserta.</p>
    <table>
        <thead>
            <tr>
                @foreach ($pdfColumns as $key => $label)
                    <th @if ($key === 'no') style="width:18px" @elseif ($key === 'nama_lengkap') style="width:85px" @elseif ($key === 'nomor_peserta') style="width:83px" @endif>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($pdfColumns as $key => $label)
                        <td @class(['number' => isset($standards[$key]) || in_array($key, ['no', 'nilai_akhir_rata', 'probabilitas_lulus', 'probabilitas_gagal']), 'identifier' => $key === 'nomor_peserta'])>
                            <span @class(['low' => isset($standards[$key]) && $row[$key] !== null && $row[$key] < $standards[$key]])>{{ is_float($row[$key]) ? number_format($row[$key], 2, ',', '.') : ($row[$key] ?? '—') }}</span>
                        </td>
                    @endforeach
                </tr>
                @if ($row['model'])
                    <tr>
                        <td colspan="{{ count($pdfColumns) }}" class="note">
                            <strong>{{ $row['nomor_peserta'] }} · Model: {{ $row['model'] }} · {{ $row['tanggal_prediksi'] }}</strong><br>
                            Rekomendasi: {{ $row['rekomendasi'] }}
                            @if ($row['nilai_diperbarui'])<br>Nilai telah diperbarui. Jalankan ulang prediksi untuk memperbarui rekomendasi.@endif
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="{{ count($pdfColumns) }}" style="text-align:center;padding:20px">Tidak ada peserta yang sesuai filter laporan.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="meta">Nilai akhir rata-rata dihitung dari lima komponen akademik tanpa kehadiran. Hasil prediksi merupakan dukungan evaluasi; status kelulusan aktual dicatat secara terpisah.</p>
    <div class="signature keep">Disusun oleh,<br>Bagian Akademik Advokat Propindo<br><br>{{ $printedBy }}</div>
@endsection
