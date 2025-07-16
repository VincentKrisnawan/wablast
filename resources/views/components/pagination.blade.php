@props(['paginator'])

@if ($paginator->hasPages())
    <div class="custom-pagination-container">
        {{-- Informasi "Showing X to Y of Z" --}}
        <div class="pagination-info">
            <span>Showing <b>{{ $paginator->firstItem() }}</b> to <b>{{ $paginator->lastItem() }}</b> of <b>{{ $paginator->total() }}</b> results</span>
        </div>

        {{-- Tombol Halaman --}}
        <nav class="pagination-links">
            {{-- Tombol "Previous" --}}
            @if ($paginator->onFirstPage())
                <span class="pagination-link disabled">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="pagination-link" rel="prev">‹</a>
            @endif

            {{-- Elemen Paginasi (Nomor dan Separator "...") --}}
            @foreach ($paginator->links()->elements as $element)
                {{-- Handle "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="pagination-link disabled">{{ $element }}</span>
                @endif

                {{-- Handle Array of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-link active">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="pagination-link">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Tombol "Next" --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="pagination-link" rel="next">›</a>
            @else
                <span class="pagination-link disabled">›</span>
            @endif
        </nav>
    </div>
@endif
