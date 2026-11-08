@props(['name'])

<svg {{ $attributes->merge(['class' => 'w-5 h-5 shrink-0']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('edit')
            <path d="m16.5 3.75 3.75 3.75M4.5 15.75 16.5 3.75a2.65 2.65 0 0 1 3.75 3.75l-12 12-4.5.75z" />
            @break
        @case('chevron-down')
            <path d="m6 9 6 6 6-6" />
            @break
        @case('chevron-left')
            <path d="m15 18-6-6 6-6" />
            @break
        @case('chevron-right')
            <path d="m9 6 6 6-6 6" />
            @break
        @case('dashboard')
            <path d="M3.75 3.75h6v6h-6zm10.5 0h6v6h-6zm-10.5 10.5h6v6h-6zm10.5 0h6v6h-6z" />
            @break
        @case('calendar')
            <path d="M6.75 3v3.75M17.25 3v3.75M3 9h18M5.25 4.5h13.5A2.25 2.25 0 0 1 21 6.75v12A2.25 2.25 0 0 1 18.75 21H5.25A2.25 2.25 0 0 1 3 18.75v-12A2.25 2.25 0 0 1 5.25 4.5Z" />
            @break
        @case('users')
            <path d="M15 19.5a6 6 0 0 0-12 0v.75h12zm6-1.5a4.5 4.5 0 0 0-5.25-4.44M12 7.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.5 1.5a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
            @break
        @case('model')
            <path d="M9 3h6M10 3v6l-6 9a2 2 0 0 0 1.66 3h12.68A2 2 0 0 0 20 18l-6-9V3M7.5 14h9" />
            @break
        @case('prediction')
            <path d="M3.75 3v17.25H21M7.5 15l4.5-4.5 3 3 5.25-6M16.5 7.5h3.75v3.75" />
            @break
        @case('warning')
            <path d="m10.3 4.5-8 14a2 2 0 0 0 1.74 3h15.92a2 2 0 0 0 1.74-3l-8-14a2 2 0 0 0-3.4 0ZM12 9v4.5M12 17.25h.008" />
            @break
        @case('report')
            <path d="M14.25 3H6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 6 21h12a2.25 2.25 0 0 0 2.25-2.25V9Zm0 0v6h6M8.25 16.5v-3m3.75 3v-6m3.75 6v-1.5" />
            @break
        @case('clipboard')
            <path d="M9 4.5H6.75A2.25 2.25 0 0 0 4.5 6.75v12A2.25 2.25 0 0 0 6.75 21h10.5a2.25 2.25 0 0 0 2.25-2.25v-12a2.25 2.25 0 0 0-2.25-2.25H15M9 3h6v3H9zm-.75 10.5 2.25 2.25 5.25-5.25" />
            @break
        @case('download')
            <path d="M12 3v12m-4.5-4.5L12 15l4.5-4.5M3.75 15.75v3A2.25 2.25 0 0 0 6 21h12a2.25 2.25 0 0 0 2.25-2.25v-3" />
            @break
        @case('logout')
            <path d="M9 8.25V5.5A2.5 2.5 0 0 1 11.5 3h7A2.5 2.5 0 0 1 21 5.5v13a2.5 2.5 0 0 1-2.5 2.5h-7A2.5 2.5 0 0 1 9 18.5v-2.75M15 12H3m4.5-4.5L3 12l4.5 4.5" />
            @break
        @case('arrow-right')
            <path d="M3.75 12h16.5m-6.75-6.75L20.25 12l-6.75 6.75" />
            @break
        @case('plus')
            <path d="M12 4.5v15m-7.5-7.5h15" />
            @break
        @case('menu')
            <path d="M3.75 6h16.5M3.75 12h16.5M3.75 18h16.5" />
            @break
    @endswitch
</svg>
