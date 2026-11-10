@props(['value' => null, 'minimum' => null, 'percentage' => false])

<span @class(['relative whitespace-nowrap tabular-nums', 'font-semibold text-red-700' => $value !== null && $minimum !== null && $value < $minimum])>
    @if ($value !== null)
        @if ($minimum !== null && $value < $minimum)<span class="sr-only">Di bawah ambang evaluasi simulasi: </span>@endif
        {{ number_format((float) $value, 2, ',', '.') }}{{ $percentage ? '%' : '' }}
    @else
        <span aria-hidden="true">—</span><span class="sr-only">Belum tersedia</span>
    @endif
</span>
