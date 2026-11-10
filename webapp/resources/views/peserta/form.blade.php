<x-layout :title="$peserta->exists ? 'Ubah Peserta & Penilaian' : 'Tambah Peserta & Penilaian'" description="Lengkapi identitas dan nilai peserta. Rata-rata dihitung otomatis dari lima nilai akademik.">
    <x-slot:actions><a class="btn-secondary" href="{{ $peserta->exists ? route('peserta.show', $peserta) : route('peserta.index') }}"><x-icon name="chevron-left" /> {{ $peserta->exists ? 'Detail peserta' : 'Daftar peserta' }}</a></x-slot:actions>
    <form action="{{ $peserta->exists ? route('peserta.update', $peserta) : route('peserta.store') }}" method="POST" class="grid gap-6">
        @csrf
        @if ($peserta->exists) @method('PUT') @endif

        <section class="panel form-section p-6 sm:p-8">
            <div class="form-section-heading"><div><p class="eyebrow">01 / Data peserta</p><h2 class="mt-1">Identitas peserta</h2><p>Data pribadi dan latar belakang pendidikan peserta PKPA.</p></div><span class="text-xs text-slate-500">* Wajib diisi</span></div>
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="label" for="angkatan_id">Angkatan <span class="text-rose-600" aria-hidden="true">*</span></label>
                    <select name="angkatan_id" id="angkatan_id" class="input" required>
                        <option value="">Pilih angkatan</option>
                        @foreach ($angkatan as $item)
                            <option value="{{ $item->id }}" @selected(old('angkatan_id', $peserta->angkatan_id) == $item->id)>{{ $item->nama_angkatan }}</option>
                        @endforeach
                    </select>
                </div>
                <x-field name="nomor_peserta" label="Nomor peserta" :value="$peserta->nomor_peserta" maxlength="255" />
                <x-field name="nama_lengkap" label="Nama lengkap" :value="$peserta->nama_lengkap" maxlength="255" />
                <div>
                    <label class="label" for="jenis_kelamin">Jenis kelamin <span class="text-rose-600" aria-hidden="true">*</span></label>
                    <select id="jenis_kelamin" name="jenis_kelamin" class="input" required>
                        <option value="L" @selected(old('jenis_kelamin', $peserta->jenis_kelamin) === 'L')>Laki-laki</option>
                        <option value="P" @selected(old('jenis_kelamin', $peserta->jenis_kelamin) === 'P')>Perempuan</option>
                    </select>
                </div>
                <x-field name="email" label="Email" type="email" :value="$peserta->email" maxlength="255" />
                <x-field name="no_hp" label="Nomor telepon" type="tel" :value="$peserta->no_hp" maxlength="20" />
                <x-field name="asal_kampus_s1" label="Asal kampus S1" :value="$peserta->asal_kampus_s1" maxlength="255" />
                <x-field name="ipk_s1" label="IPK S1" type="number" :value="$peserta->ipk_s1" step="0.01" min="0" max="4" />
            </div>
        </section>

        <section class="panel p-6 sm:p-8">
            <div class="form-section-heading">
                <div><p class="eyebrow">02 / Catatan akademik</p><h2 class="mt-1">Penilaian PKPA</h2><p>Kehadiran dalam persen, lima nilai akademik pada skala 0–100. Status aktual dicatat secara terpisah dari prediksi.</p></div>
                <p class="average-preview">Pratinjau rata-rata <strong class="tabular-nums" data-average>0,00</strong><span>/ 100 · tanpa kehadiran</span></p>
            </div>
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($features as $name => $label)
                    <x-field :name="$name" :label="$label" type="number" :value="$peserta->penilaian?->{$name}" step="0.01" min="0" max="100" :data-grade="$name === 'persen_kehadiran' ? null : 'true'" />
                @endforeach
                <div>
                    <label class="label" for="status_aktual">Status aktual <span class="text-rose-600" aria-hidden="true">*</span></label>
                    <select name="status_aktual" id="status_aktual" class="input" required>
                        @foreach (['Belum Ditentukan', 'Lulus', 'Tidak Lulus'] as $status)
                            <option @selected(old('status_aktual', $peserta->penilaian?->status_aktual) === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>
        <div class="form-actions">
            <button class="btn" type="submit">Simpan peserta &amp; penilaian</button>
            <a class="btn-secondary" href="{{ route('peserta.index') }}">Batal</a>
        </div>
    </form>
</x-layout>
