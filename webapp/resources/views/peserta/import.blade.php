<x-layout title="Impor Penilaian" description="Perbarui nilai peserta yang sudah terdaftar melalui berkas CSV atau Excel.">
    <x-slot:actions><a class="btn-secondary" href="{{ route('peserta.index') }}"><x-icon name="chevron-left" /> Daftar peserta</a></x-slot:actions>
    <div class="grid gap-6 lg:grid-cols-5">
        <section class="import-guide p-6 lg:col-span-2">
            <p class="eyebrow">Persiapan data</p>
            <h2 class="section-title mt-2">Petunjuk pengisian berkas</h2>
            <ol class="mt-6 list-decimal space-y-4 pl-5 text-sm leading-relaxed text-slate-600">
                <li>Unduh templat CSV, lalu isi nomor peserta, enam komponen penilaian, dan status aktual.</li>
                <li>Gunakan nomor peserta yang terdaftar pada angkatan pilihan. Nilai harus 0–100, dengan titik sebagai pemisah desimal.</li>
                <li>Isi status aktual dengan Lulus, Tidak Lulus, atau Belum Ditentukan. Rata-rata dihitung otomatis.</li>
                <li>Berkas dapat berupa CSV, XLSX, atau XLS, maksimal 5 MB dan 5.000 baris. Seluruh data diperiksa sebelum disimpan.</li>
            </ol>
            <a class="btn-secondary mt-6" href="{{ route('penilaian.template') }}"><x-icon name="download" /> Unduh templat CSV</a>
        </section>

        <form action="{{ route('penilaian.import.store') }}" method="POST" enctype="multipart/form-data" data-busy class="panel form-section grid content-start gap-6 p-6 lg:col-span-3">
            @csrf
            <div><p class="eyebrow">Impor penilaian</p><h2 class="section-title mt-2">Pilih tujuan dan berkas</h2></div>
            <div>
                <label class="label" for="angkatan_id">Angkatan tujuan</label>
                <select class="input" id="angkatan_id" name="angkatan_id" required>
                    <option value="">Pilih angkatan</option>
                    @foreach ($angkatan as $item)
                        <option value="{{ $item->id }}" @selected(old('angkatan_id') == $item->id)>{{ $item->nama_angkatan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="rounded-md border border-dashed border-slate-300 bg-slate-50 p-5">
                <label class="label" for="file">Berkas penilaian</label>
                <input class="input" type="file" name="file" id="file" accept=".csv,.xlsx,.xls" required aria-describedby="file-help">
                <p id="file-help" class="mt-3 text-xs text-slate-500">Gunakan judul kolom sesuai templat. Nomor peserta tidak boleh berulang.</p>
            </div>
            <button type="submit" class="btn justify-self-start">Periksa dan impor nilai</button>
        </form>
    </div>
</x-layout>
