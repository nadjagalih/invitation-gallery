{{--
  Paginator ucapan. View pagination bawaan Laravel memakai class Tailwind yang
  tidak ada di halaman undangan, jadi tampilannya akan rusak; ini penggantinya.

  Prev/next saja, bukan daftar nomor: di lebar 480px daftar nomor halaman
  memaksa pembungkusan baris dan target sentuh yang terlalu kecil.
--}}
@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="Navigasi ucapan">
        @if ($paginator->onFirstPage())
            <span class="disabled" aria-disabled="true">&larr; Baru</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">&larr; Baru</a>
        @endif

        @if ($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <span>Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
        @else
            <span>Halaman {{ $paginator->currentPage() }}</span>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Lama &rarr;</a>
        @else
            <span class="disabled" aria-disabled="true">Lama &rarr;</span>
        @endif
    </nav>
@endif
