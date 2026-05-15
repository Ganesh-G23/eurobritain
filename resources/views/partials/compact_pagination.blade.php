@php
    $currentPage = max(1, (int) ($currentPage ?? 1));
    $totalPages = max(1, (int) ($totalPages ?? 1));
    $baseUrl = $baseUrl ?? url()->current();
    $queryParams = $queryParams ?? [];
    $perPageVal = $perPageVal ?? null;

    $paginationLink = static function (int $page) use ($baseUrl, $queryParams, $perPageVal): string {
        $params = array_merge($queryParams, ['page' => $page]);
        if ($perPageVal !== null) {
            $params['per_page'] = $perPageVal;
        }

        return $baseUrl . '?' . http_build_query($params);
    };

    if ($totalPages <= 3) {
        $pageNumbers = range(1, $totalPages);
    } elseif ($currentPage <= 2) {
        $pageNumbers = [1, 2, 3];
    } elseif ($currentPage >= $totalPages - 1) {
        $pageNumbers = [$totalPages - 2, $totalPages - 1, $totalPages];
    } else {
        $pageNumbers = [$currentPage - 1, $currentPage, $currentPage + 1];
    }

    $hasPrev = $currentPage > 1;
    $hasNext = $currentPage < $totalPages;
@endphp

@if ($totalPages > 1)
    <nav aria-label="Page navigation">
        <ul class="pagination pagination-sm justify-content-end mb-0">
            <li class="page-item {{ $hasPrev ? '' : 'disabled' }}">
                @if ($hasPrev)
                    <a class="page-link" href="{{ $paginationLink($currentPage - 1) }}" aria-label="Previous page">
                        <i class="icon-base ti tabler-chevron-left"></i>
                    </a>
                @else
                    <span class="page-link" aria-hidden="true">
                        <i class="icon-base ti tabler-chevron-left"></i>
                    </span>
                @endif
            </li>

            @foreach ($pageNumbers as $pageNum)
                <li class="page-item {{ $currentPage === $pageNum ? 'active' : '' }}">
                    <a class="page-link" href="{{ $paginationLink($pageNum) }}">{{ $pageNum }}</a>
                </li>
            @endforeach

            <li class="page-item {{ $hasNext ? '' : 'disabled' }}">
                @if ($hasNext)
                    <a class="page-link" href="{{ $paginationLink($currentPage + 1) }}" aria-label="Next page">
                        <i class="icon-base ti tabler-chevron-right"></i>
                    </a>
                @else
                    <span class="page-link" aria-hidden="true">
                        <i class="icon-base ti tabler-chevron-right"></i>
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
