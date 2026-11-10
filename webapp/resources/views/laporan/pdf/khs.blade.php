@extends('laporan.pdf.layout')

@section('content')
    <p class="meta" style="text-align:center">Ringkasan penilaian akademik dan evaluasi kelulusan peserta PKPA</p>
    <table class="no-border">
        <tr>
            <td style="width:18%">Nomor peserta</td><td class="identifier" style="width:32%">: {{ $peserta->nomor_peserta }}</td>
            <td style="width:17%">Angkatan</td><td>: {{ $peserta->angkatan->nama_angkatan }}</td>
        </tr>
        <tr>
            <td>Nama lengkap</td><td>: {{ $peserta->nama_lengkap }}</td>
            <td>Periode</td><td>: {{ $peserta->angkatan->tanggal_mulai->format('d/m/Y') }}–{{ $peserta->angkatan->tanggal_selesai->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>Asal kampus S1</td><td>: {{ $peserta->asal_kampus_s1 }}</td>
            <td>IPK S1</td><td>: {{ number_format((float) $peserta->ipk_s1, 2, ',', '.') }}</td>
        </tr>
    </table>

    <h3>Penilaian akademik</h3>
    <table class="no-border">
        <tr>
            <td style="width:52%;padding:0"><img src="{{ $radarImage }}" alt="Radar penilaian peserta" style="width:290px;height:215px"></td>
            <td style="padding:0">
                <table>
                    <thead><tr><th>Komponen</th><th class="number">Nilai</th><th class="number">Standar</th></tr></thead>
                    <tbody>
                        @foreach ($features as $key => $label)
                            <tr>
                                <td>{{ $label }}</td>
                                <td class="number"><span @class(['low' => $row[$key] < $standards[$key]])>{{ number_format($row[$key], 2, ',', '.') }}</span></td>
                                <td class="number">{{ $standards[$key] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p><strong>Nilai akhir rata-rata: {{ number_format((float) $peserta->penilaian->nilai_akhir_rata, 2, ',', '.') }}</strong></p>
                <p class="muted">Rata-rata lima nilai akademik, tanpa kehadiran.</p>
            </td>
        </tr>
    </table>
    <p><span class="dot"></span>Nilai berwarna merah berada di bawah ambang evaluasi simulasi.</p>
    <p><strong>Status aktual: {{ $peserta->penilaian->status_aktual }}</strong></p>

    <h3>Evaluasi prediksi kelulusan</h3>
    @if ($peserta->prediksiTerbaru)
        <table>
            <thead><tr><th>Prediksi terbaru</th><th class="number">Peluang lulus</th><th class="number">Peluang gagal</th></tr></thead>
            <tbody>
                <tr>
                    <td>{{ $row['status_prediksi'] }}</td>
                    <td class="number">{{ number_format($row['probabilitas_lulus'], 2, ',', '.') }}%</td>
                    <td class="number">{{ number_format($row['probabilitas_gagal'], 2, ',', '.') }}%</td>
                </tr>
            </tbody>
        </table>
        <p class="meta">Model: {{ $row['model'] }} · Waktu prediksi: {{ $row['tanggal_prediksi'] }}</p>
        <p><strong>Rekomendasi tindak lanjut:</strong><br>{{ $row['rekomendasi'] }}</p>
        @if ($row['nilai_diperbarui'])
            <p class="low">Nilai telah diperbarui. Jalankan ulang prediksi untuk memperbarui rekomendasi.</p>
        @endif
    @else
        <p>Belum ada prediksi kelulusan. Jalankan prediksi individual untuk memperoleh evaluasi dan rekomendasi.</p>
    @endif
    <p class="meta">Kartu ini menggunakan penilaian yang tercatat saat dicetak. Hasil prediksi dan penetapan kelulusan aktual dicatat secara terpisah.</p>
    <div class="signature keep">Bagian Akademik Advokat Propindo<br>Dicetak {{ $printedAt->format('d F Y') }}<br><br><br>{{ $printedBy }}</div>
@endsection
