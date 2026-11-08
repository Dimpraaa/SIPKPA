<div data-pagination-controls class="flex flex-col gap-4 2xl:flex-row 2xl:items-center 2xl:justify-between">
    <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500">
        @if ($paginator->total() === 0)
            <p>Tidak ada data yang sesuai.</p>
        @elseif ($paginator->count() === 0)
            <p>Halaman ini tidak memiliki data. <a href="{{ $paginator->url(1) }}" class="link">Kembali ke halaman pertama</a></p>
        @else
            <p>
                Menampilkan <span class="font-semibold text-slate-700 tabular-nums">{{ number_format($paginator->firstItem(), 0, ',', '.') }}–{{ number_format($paginator->lastItem(), 0, ',', '.') }}</span>
                dari <span class="font-semibold text-slate-700 tabular-nums">{{ number_format($paginator->total(), 0, ',', '.') }}</span> data
            </p>
        @endif
        <span class="pagination-loading" aria-hidden="true">
            <span class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-teal-200 border-t-teal-700 motion-reduce:animate-none"></span>
            Memuat…
        </span>
    </div>

    @if ($paginator->hasPages())
        <nav aria-label="Navigasi halaman tabel" class="flex items-center justify-between gap-2 sm:justify-start 2xl:justify-end">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Halaman sebelumnya" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 text-sm text-slate-300">
                    <x-icon name="chevron-left" /><span class="sr-only 2xl:not-sr-only">Sebelumnya</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya" title="Halaman sebelumnya" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 text-sm font-medium text-slate-600 transition hover:border-teal-300 hover:bg-teal-50 hover:text-teal-800">
                    <x-icon name="chevron-left" /><span class="sr-only 2xl:not-sr-only">Sebelumnya</span>
                </a>
            @endif

            <p class="whitespace-nowrap text-xs text-slate-500 tabular-nums sm:hidden">
                Halaman <strong class="font-semibold text-slate-800">{{ $paginator->currentPage() }}</strong> / {{ $paginator->lastPage() }}
            </p>

            <ul class="hidden flex-wrap items-center justify-center gap-1 sm:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li><span class="inline-flex h-10 min-w-6 items-center justify-center text-slate-400" aria-hidden="true">…</span></li>
                    @else
                        @foreach ($element as $page => $url)
                            <li>
                                @if ($page === $paginator->currentPage())
                                    <span aria-current="page" aria-label="Halaman {{ $page }}, halaman saat ini" class="inline-flex h-10 min-w-10 items-center justify-center rounded-lg border border-teal-700 bg-teal-700 px-2 text-sm font-semibold text-white shadow-xs tabular-nums">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" aria-label="Ke halaman {{ $page }}" class="inline-flex h-10 min-w-10 items-center justify-center rounded-lg border border-transparent px-2 text-sm font-medium text-slate-600 tabular-nums transition hover:border-slate-200 hover:bg-slate-50 hover:text-teal-800">{{ $page }}</a>
                                @endif
                            </li>
                        @endforeach
                    @endif
                @endforeach
            </ul>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya" title="Halaman berikutnya" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 text-sm font-medium text-slate-600 transition hover:border-teal-300 hover:bg-teal-50 hover:text-teal-800">
                    <span class="sr-only 2xl:not-sr-only">Berikutnya</span><x-icon name="chevron-right" />
                </a>
            @else
                <span aria-disabled="true" aria-label="Halaman berikutnya" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 text-sm text-slate-300">
                    <span class="sr-only 2xl:not-sr-only">Berikutnya</span><x-icon name="chevron-right" />
                </span>
            @endif
        </nav>
    @elseif ($paginator->total() > 0)
        <p class="text-xs text-slate-400">Halaman 1 dari 1</p>
    @endif
</div>
