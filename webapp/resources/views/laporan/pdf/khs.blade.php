@extends('laporan.pdf.layout')
@section('content')
    <p class="meta" style="text-align:center">LUA-07 · Kartu evaluasi akademik peserta PKPA</p>
    <table class="no-border"><tr><td style="width:18%">Nomor peserta</td><td style="width:32%">: {{ $peserta->nomor_peserta }}</td><td style="width:17%">Angkatan</td><td>: {{ $peserta->angkatan->nama_angkatan }}</td></tr><tr><td>Nama lengkap</td><td>: {{ $peserta->nama_lengkap }}</td><td>Periode</td><td>: {{ $peserta->angkatan->tanggal_mulai->format('d/m/Y') }}–{{ $peserta->angkatan->tanggal_selesai->format('d/m/Y') }}</td></tr><tr><td>Asal kampus S1</td><td>: {{ $peserta->asal_kampus_s1 }}</td><td>IPK S1</td><td>: {{ $peserta->ipk_s1 }}</td></tr></table>
    <h3>A. Penilaian dan penguasaan komponen PKPA</h3>
    <table class="no-border"><tr><td style="width:52%;padding:0"><img src="{{ $radarImage }}" alt="Radar penilaian peserta" style="width:290px;height:215px"></td><td style="padding:0">
        <table><thead><tr><th>Komponen</th><th>Nilai</th><th>Standar</th></tr></thead><tbody>@foreach ($features as $key => $label)<tr><td>X{{ $loop->iteration }} · {{ $label }}</td><td @if ($row[$key] < $standards[$key]) class="low" @endif>{{ number_format($row[$key], 2, ',', '.') }}</td><td>{{ $standards[$key] }}</td></tr>@endforeach</tbody></table>
        <p><strong>Nilai akhir rata-rata: {{ $peserta->penilaian->nilai_akhir_rata }}</strong></p><p class="muted">Rata-rata lima nilai akademik, tanpa kehadiran.</p>
    </td></tr></table>
    <p><strong>Status aktual: {{ $peserta->penilaian->status_aktual }}</strong></p>
    <h3>B. Evaluasi prediksi kelulusan</h3>
    @if ($peserta->prediksiTerbaru)
        <table><tr><th>Prediksi terbaru</th><th>Peluang lulus</th><th>Peluang gagal</th></tr><tr><td>{{ $row['status_prediksi'] }}</td><td>{{ $row['probabilitas_lulus'] }}%</td><td>{{ $row['probabilitas_gagal'] }}%</td></tr></table>
        <p class="meta">Model: {{ $row['model'] }} · Waktu prediksi: {{ $row['tanggal_prediksi'] }}</p>
        <p><strong>Rekomendasi tindak lanjut:</strong><br>{{ $row['rekomendasi'] }}</p>
        @if ($row['nilai_diperbarui'])<p class="low">Nilai telah diperbarui setelah prediksi. Jalankan ulang prediksi untuk evaluasi terkini.</p>@endif
    @else
        <p>Belum ada prediksi kelulusan. Jalankan prediksi individual untuk memperoleh evaluasi dan rekomendasi model.</p>
    @endif
    <p class="meta">Nilai pada kartu ini adalah penilaian yang tercatat saat dicetak. Prediksi bukan penetapan kelulusan aktual.</p>
    <div class="signature keep">Bagian Akademik Advokat Propindo<br>Dicetak {{ $printedAt->format('d F Y') }}<br><br><br>{{ $printedBy }}</div>
@endsection
