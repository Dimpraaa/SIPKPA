@props(['value' => null, 'minimum' => null, 'percentage' => false])

<span @class(['whitespace-nowrap tabular-nums', 'font-semibold text-red-700' => $value !== null && $minimum !== null && $value < $minimum])>
    @if ($value !== null)
        @if ($minimum !== null && $value < $minimum)<span class="sr-only">Di bawah standar minimum: </span>@endif
        {{ number_format((float) $value, 2, ',', '.') }}{{ $percentage ? '%' : '' }}
    @else
        —
    @endif
</span>
