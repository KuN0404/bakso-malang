@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Navigasi Halaman" class="flex items-center justify-between">
            {{-- Mobile --}}
            <div class="flex justify-between flex-1 sm:hidden">
                <span>
                    @if ($paginator->onFirstPage())
                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-200 cursor-not-allowed leading-5 rounded-lg">
                            Sebelumnya
                        </span>
                    @else
                        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 leading-5 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition ease-in-out duration-150">
                            Sebelumnya
                        </button>
                    @endif
                </span>

                <span>
                    @if ($paginator->hasMorePages())
                        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-200 leading-5 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition ease-in-out duration-150">
                            Berikutnya
                        </button>
                    @else
                        <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-400 bg-white border border-gray-200 cursor-not-allowed leading-5 rounded-lg">
                            Berikutnya
                        </span>
                    @endif
                </span>
            </div>

            {{-- Desktop --}}
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between sm:gap-4">
                <div>
                    <p class="text-sm text-gray-600">
                        Menampilkan
                        <span class="font-medium text-gray-800">{{ $paginator->firstItem() }}</span>
                        &ndash;
                        <span class="font-medium text-gray-800">{{ $paginator->lastItem() }}</span>
                        dari
                        <span class="font-medium text-gray-800">{{ $paginator->total() }}</span>
                        data
                    </p>
                </div>

                <div>
                    <span class="relative z-0 inline-flex rtl:flex-row-reverse rounded-lg shadow-sm">
                        {{-- Previous Page Link --}}
                        @if ($paginator->onFirstPage())
                            <span aria-disabled="true" aria-label="Sebelumnya" class="relative inline-flex items-center justify-center h-9 w-9 text-gray-300 bg-white border border-gray-200 cursor-not-allowed rounded-l-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </span>
                        @else
                            <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="relative inline-flex items-center justify-center h-9 w-9 text-gray-500 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-50 hover:text-gray-700 focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 transition ease-in-out duration-150" aria-label="Sebelumnya">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                        @endif

                        {{-- Pagination Elements --}}
                        @foreach ($elements as $element)
                            {{-- "Three Dots" Separator --}}
                            @if (is_string($element))
                                <span aria-disabled="true" class="relative -ml-px inline-flex items-center justify-center h-9 min-w-9 px-2 text-sm font-medium text-gray-400 bg-white border border-gray-200 cursor-default">{{ $element }}</span>
                            @endif

                            {{-- Array Of Links --}}
                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    @if ($page == $paginator->currentPage())
                                        <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}" aria-current="page" class="relative -ml-px inline-flex items-center justify-center h-9 min-w-9 px-2 text-sm font-medium text-white bg-primary-600 border border-primary-600 cursor-default z-10">{{ $page }}</span>
                                    @else
                                        <button wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}" type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="relative -ml-px inline-flex items-center justify-center h-9 min-w-9 px-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 hover:text-gray-800 focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 transition ease-in-out duration-150" aria-label="Ke halaman :page">
                                            {{ $page }}
                                        </button>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($paginator->hasMorePages())
                            <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="relative -ml-px inline-flex items-center justify-center h-9 w-9 text-gray-500 bg-white border border-gray-200 rounded-r-lg hover:bg-gray-50 hover:text-gray-700 focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 transition ease-in-out duration-150" aria-label="Berikutnya">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        @else
                            <span aria-disabled="true" aria-label="Berikutnya" class="relative -ml-px inline-flex items-center justify-center h-9 w-9 text-gray-300 bg-white border border-gray-200 cursor-not-allowed rounded-r-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </span>
                        @endif
                    </span>
                </div>
            </div>
        </nav>
    @endif
</div>
