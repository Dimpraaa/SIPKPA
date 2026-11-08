@props(['value', 'compact' => false])

<span @class([
    'inline-flex items-center whitespace-nowrap py-1 text-xs',
    'rounded-full px-2.5 font-semibold' => ! $compact,
    'rounded-md px-2 font-medium' => $compact,
    'bg-teal-50 text-teal-800' => in_array($value, ['Lulus', 'berjalan', 'Aktif']),
    'bg-rose-50 text-rose-800' => in_array($value, ['Tidak Lulus', 'Berisiko Tidak Lulus']),
    'bg-slate-100 text-slate-600' => ! in_array($value, ['Lulus', 'berjalan', 'Aktif', 'Tidak Lulus', 'Berisiko Tidak Lulus']),
])>{{ $value === 'berjalan' ? 'Berjalan' : ($value === 'selesai' ? 'Selesai' : $value) }}</span>
