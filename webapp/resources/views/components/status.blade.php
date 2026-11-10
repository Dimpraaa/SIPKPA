@props(['value', 'compact' => false])

<span @class([
    'status-badge',
    'status-success' => in_array($value, ['Lulus', 'berjalan', 'Aktif']),
    'status-danger' => $value === 'Tidak Lulus',
    'status-warning' => $value === 'Berisiko Tidak Lulus',
    'status-neutral' => ! in_array($value, ['Lulus', 'berjalan', 'Aktif', 'Tidak Lulus', 'Berisiko Tidak Lulus']),
])>{{ $value === 'berjalan' ? 'Berjalan' : ($value === 'selesai' ? 'Selesai' : $value) }}</span>
