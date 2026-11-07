<x-layout :title="$peserta->exists ? 'Ubah Peserta & Penilaian' : 'Tambah Peserta & Penilaian'" description="Rata-rata dihitung otomatis dari lima komponen akademik, tanpa persentase kehadiran.">
    <form action="{{ $peserta->exists ? route('peserta.update', $peserta) : route('peserta.store') }}" method="POST" class="grid gap-6">@csrf @if($peserta->exists) @method('PUT') @endif
        <section class="panel p-6 sm:p-8"><h2 class="mb-6 text-lg font-bold">01 · Identitas peserta</h2><div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            <div><label class="label" for="angkatan_id">Angkatan *</label><select name="angkatan_id" id="angkatan_id" class="input" required><option value="">Pilih angkatan</option>@foreach($angkatan as $item)<option value="{{ $item->id }}" @selected(old('angkatan_id', $peserta->angkatan_id) == $item->id)>{{ $item->nama_angkatan }}</option>@endforeach</select></div>
            <x-field name="nomor_peserta" label="Nomor peserta" :value="$peserta->nomor_peserta" maxlength="255" /><x-field name="nama_lengkap" label="Nama lengkap" :value="$peserta->nama_lengkap" maxlength="255" />
            <div><label class="label" for="jenis_kelamin">Jenis kelamin *</label><select id="jenis_kelamin" name="jenis_kelamin" class="input" required><option value="L" @selected(old('jenis_kelamin', $peserta->jenis_kelamin) === 'L')>Laki-laki</option><option value="P" @selected(old('jenis_kelamin', $peserta->jenis_kelamin) === 'P')>Perempuan</option></select></div>
            <x-field name="email" label="Email" type="email" :value="$peserta->email" maxlength="255" /><x-field name="no_hp" label="Nomor telepon" type="tel" :value="$peserta->no_hp" maxlength="20" /><x-field name="asal_kampus_s1" label="Asal kampus S1" :value="$peserta->asal_kampus_s1" maxlength="255" /><x-field name="ipk_s1" label="IPK S1" type="number" :value="$peserta->ipk_s1" step="0.01" min="0" max="4" />
        </div></section>
        <section class="panel p-6 sm:p-8"><div class="mb-6 flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg font-bold">02 · Penilaian PKPA</h2><span class="rounded-xl bg-teal-50 px-4 py-2 text-sm text-teal-800">Rata-rata: <strong data-average>0,00</strong></span></div><div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach($features as $name => $label)
                <x-field :name="$name" :label="'X'.($loop->index + 1).' · '.$label" type="number" :value="$peserta->penilaian?->{$name}" step="0.01" min="0" max="100" :data-grade="$name === 'persen_kehadiran' ? null : 'true'" />
            @endforeach
            <div><label class="label" for="status_aktual">Status aktual *</label><select name="status_aktual" id="status_aktual" class="input" required>@foreach(['Belum Ditentukan', 'Lulus', 'Tidak Lulus'] as $status)<option @selected(old('status_aktual', $peserta->penilaian?->status_aktual) === $status)>{{ $status }}</option>@endforeach</select></div>
        </div></section><div class="flex gap-3"><button class="btn" type="submit">Simpan peserta & penilaian</button><a class="btn-secondary" href="{{ route('peserta.index') }}">Batal</a></div>
    </form>
</x-layout>
