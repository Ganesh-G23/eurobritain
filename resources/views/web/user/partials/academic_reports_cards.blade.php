@php
    $downloadBase = rtrim($download_base_url ?? '', '/');
    $types = [
        'test' => [
            'title' => 'Test Report',
            'description' => 'Download marks and results for all tests under your enrollment.',
            'icon' => 'tabler-clipboard-check',
            'color' => 'primary',
        ],
        'attendance' => [
            'title' => 'Attendance Report',
            'description' => 'Download attendance history with present, absent, and late counts.',
            'icon' => 'tabler-calendar-check',
            'color' => 'success',
        ],
        'overall' => [
            'title' => 'Overall Report',
            'description' => 'Combined attendance summary and test performance in one PDF.',
            'icon' => 'tabler-report-analytics',
            'color' => 'info',
        ],
    ];
@endphp

<div class="row g-4">
    @foreach ($types as $type => $meta)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="avatar avatar-md">
                            <span class="avatar-initial rounded bg-label-{{ $meta['color'] }}">
                                <i class="icon-base ti {{ $meta['icon'] }} icon-26px"></i>
                            </span>
                        </span>
                        <div>
                            <h5 class="mb-0">{{ $meta['title'] }}</h5>
                        </div>
                    </div>
                    <p class="text-body-secondary small flex-grow-1 mb-4">{{ $meta['description'] }}</p>
                    <a href="{{ $downloadBase }}/{{ $type }}" class="btn btn-{{ $meta['color'] }} w-100" target="_blank" rel="noopener">
                        <i class="icon-base ti tabler-download me-1"></i> Download PDF
                    </a>
                </div>
            </div>
        </div>
    @endforeach
</div>
