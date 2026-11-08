@props(['label'])

<details {{ $attributes->merge(['class' => 'action-menu', 'data-action-menu' => '']) }}>
    <summary aria-label="{{ $label }}" title="{{ $label }}">
        Lainnya <x-icon name="chevron-down" class="size-4" />
    </summary>
    <div class="action-menu-content" aria-label="{{ $label }}">
        {{ $slot }}
    </div>
</details>
