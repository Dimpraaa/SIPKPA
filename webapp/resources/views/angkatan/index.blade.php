<x-layout title="Angkatan PKPA" description="Periode pendidikan, jumlah peserta, dan status pelaksanaan setiap angkatan.">
    @if (auth()->user()->role === 'admin_akademik')
        <x-slot:actions><a href="{{ route('angkatan.create') }}" class="btn"><x-icon name="plus" /> Tambah angkatan</a></x-slot:actions>
    @endif

    <div data-pagination-region="angkatan" class="panel overflow-hidden">
        <div class="table-heading">
            <div><p class="eyebrow">Administrasi pendidikan</p><h2 class="section-title mt-1">Daftar angkatan</h2><p>Periode pelaksanaan dan akses ke data peserta.</p></div>
            <span class="text-xs text-slate-600 tabular-nums">{{ number_format($angkatan->total(), 0, ',', '.') }} angkatan tercatat</span>
        </div>
        <div class="overflow-x-auto" role="region" aria-label="Daftar angkatan" tabindex="0">
            <table class="w-full">
                <thead class="bg-slate-50"><tr><th scope="col">Angkatan</th><th scope="col">Periode</th><th scope="col" class="text-right">Peserta</th><th scope="col">Status</th><th scope="col">Tindakan</th></tr></thead>
                <tbody>
                    @forelse ($angkatan as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap"><div class="flex items-center gap-4"><span class="cohort-code">{{ $item->kode_angkatan }}</span><div><a class="link" href="{{ route('peserta.index', ['angkatan_id' => $item->id]) }}">{{ $item->nama_angkatan }}</a><p class="mt-1 text-xs text-slate-500">Pendidikan Khusus Profesi Advokat</p></div></div></td>
                            <td class="whitespace-nowrap tabular-nums">{{ $item->tanggal_mulai->format('d M Y') }}<br><span class="text-slate-500">s.d. {{ $item->tanggal_selesai->format('d M Y') }}</span></td>
                            <td class="whitespace-nowrap text-right text-lg font-semibold tabular-nums">{{ number_format($item->peserta_count, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap"><x-status :value="$item->status_angkatan" /></td>
                            <td class="whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <a class="link" href="{{ route('peserta.index', ['angkatan_id' => $item->id]) }}">Peserta</a>
                                    @if (auth()->user()->role === 'admin_akademik')
                                        <x-action-menu :label="'Tindakan untuk '.$item->nama_angkatan">
                                        <a href="{{ route('angkatan.edit', $item) }}"><x-icon name="edit" /> Ubah angkatan</a>
                                        <form method="POST" action="{{ route('angkatan.destroy', $item) }}" data-confirm="Hapus angkatan ini?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger">Hapus</button>
                                        </form>
                                        </x-action-menu>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">Belum ada angkatan. Tambahkan angkatan untuk mulai mendata peserta.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">{{ $angkatan->onEachSide(1)->links() }}</div>
    </div>
</x-layout>
