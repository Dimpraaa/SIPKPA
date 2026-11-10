<x-layout :title="$angkatan->exists ? 'Ubah Angkatan' : 'Tambah Angkatan'" description="Lengkapi identitas, periode, dan status pelaksanaan pendidikan.">
    <x-slot:actions><a href="{{ route('angkatan.index') }}" class="btn-secondary"><x-icon name="chevron-left" /> Daftar angkatan</a></x-slot:actions>
    <form method="POST" action="{{ $angkatan->exists ? route('angkatan.update', $angkatan) : route('angkatan.store') }}" class="panel form-section max-w-4xl p-6 sm:p-8">
        @csrf
        @if ($angkatan->exists) @method('PUT') @endif

        <div class="form-section-heading"><div><p class="eyebrow">Periode pendidikan</p><h2 class="mt-1">Informasi angkatan</h2><p>Kode menjadi identitas angkatan pada daftar peserta dan pelaporan.</p></div><span class="text-xs text-slate-500">* Wajib diisi</span></div>
        <div class="grid gap-6 sm:grid-cols-2">
            <x-field name="kode_angkatan" label="Kode angkatan" :value="$angkatan->kode_angkatan" maxlength="255" />
            <x-field name="nama_angkatan" label="Nama angkatan" :value="$angkatan->nama_angkatan" maxlength="255" />
            <x-field name="tanggal_mulai" label="Tanggal mulai" type="date" :value="$angkatan->tanggal_mulai?->format('Y-m-d')" />
            <x-field name="tanggal_selesai" label="Tanggal selesai" type="date" :value="$angkatan->tanggal_selesai?->format('Y-m-d')" />
            <div>
                <label class="label" for="status_angkatan">Status angkatan</label>
                <select name="status_angkatan" id="status_angkatan" class="input" required>
                    @foreach (['berjalan' => 'Berjalan', 'selesai' => 'Selesai'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status_angkatan', $angkatan->status_angkatan) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-actions mt-8">
            <button class="btn" type="submit">Simpan angkatan</button>
            <a href="{{ route('angkatan.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
</x-layout>
