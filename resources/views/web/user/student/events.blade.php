@extends('web.user.student.layouts.app')

@push('page_styles')
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/fullcalendar/fullcalendar.css') }}" />
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/css/pages/app-calendar.css') }}" />
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/flatpickr/flatpickr.css') }}" />
@endpush

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y pb-2" data-student-events-readonly="1">
        <div class="card app-calendar-wrapper">
            {{-- Row stretch (default): sidebar matches calendar height so border-end is full length --}}
            <div class="row g-0">
                <div class="col app-calendar-sidebar border-end" id="app-calendar-sidebar">
                    <div class="border-bottom px-6 py-3">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <h6 class="mb-0">Events</h6>
                        </div>
                    </div>
                    <div class="px-3 pt-3 pb-1">
                        <div class="inline-calendar"></div>
                    </div>
                    <hr class="mb-4 mx-n4 mt-2" />
                    <div class="px-6 pb-2">
                        <div>
                            <h5>Event Filters</h5>
                        </div>

                        <div class="form-check form-check-secondary mb-5 ms-2">
                            <input class="form-check-input select-all" type="checkbox" id="selectAll" data-value="all"
                                checked />
                            <label class="form-check-label" for="selectAll">View All</label>
                        </div>

                        <div class="app-calendar-events-filter text-heading">
                            @php
                                $filterStyleClasses = [
                                    'form-check-danger',
                                    '',
                                    'form-check-warning',
                                    'form-check-success',
                                    'form-check-info',
                                ];
                            @endphp
                            @forelse ($event_types ?? [] as $et)
                                @php
                                    $filterKey = 'et' . (int) $et->id;
                                    $extra = $filterStyleClasses[$loop->index % count($filterStyleClasses)];
                                    $swatch = $et->color_code ? trim((string) $et->color_code) : '#696cff';
                                    if (
                                        $swatch !== '' &&
                                        ($swatch[0] ?? '') !== '#' &&
                                        preg_match('/^[0-9a-fA-F]{3,8}$/', $swatch)
                                    ) {
                                        $swatch = '#' . $swatch;
                                    }
                                    if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $swatch)) {
                                        $swatch = '#696cff';
                                    }
                                @endphp
                                <div class="form-check {{ $extra }} mb-3 ms-2">
                                    <input class="form-check-input input-filter" type="checkbox"
                                        id="filter-event-type-{{ $et->id }}" data-value="{{ $filterKey }}"
                                        checked />
                                    <label class="form-check-label d-flex align-items-center gap-2"
                                        for="filter-event-type-{{ $et->id }}">
                                        <span class="badge badge-dot me-0 flex-shrink-0"
                                            style="background-color: {{ e($swatch) }} !important; border: 1px solid {{ e($swatch) }};"
                                            aria-hidden="true"></span>
                                        <span>{{ $et->title }}</span>
                                    </label>
                                </div>
                            @empty
                                <p class="small text-muted ms-2 mb-0">No events found.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col app-calendar-content">
                    <div class="card shadow-none border-0">
                        <div class="card-body pb-0">
                            <div id="calendar"></div>
                        </div>
                    </div>
                    <div class="app-overlay"></div>

                    <div class="offcanvas offcanvas-end event-sidebar" tabindex="-1" id="viewEventSidebar"
                        aria-labelledby="viewEventSidebarLabel">
                        <div class="offcanvas-header border-bottom">
                            <h5 class="offcanvas-title" id="viewEventSidebarLabel">Event Details</h5>
                            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                                aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body">
                            <div class="pt-0">
                                <div class="mb-4">
                                    <label class="form-label mb-1">Event Name</label>
                                    <div class="form-control bg-label-secondary" id="viewEventTitle">-</div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label mb-1">Event Type</label>
                                    <div class="form-control bg-label-secondary" id="viewEventType">-</div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label mb-1">Date / Time</label>
                                    <div class="form-control bg-label-secondary" id="viewEventDateTime">-</div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label mb-1">Notes</label>
                                    <div class="form-control bg-label-secondary" id="viewEventNotes"
                                        style="min-height: 84px;">No notes</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script type="application/json" id="student-calendar-feed-json">@json($calendar_events ?? [])</script>
    <script src="{{ url('public/admin_theme/assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/libs/fullcalendar/fullcalendar.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/js/app-calendar-events.js') }}"></script>

    <script>
        (function() {
            var el = document.getElementById('student-calendar-feed-json');
            if (!el) return;

            try {
                window.events = JSON.parse(el.textContent || '[]');
            } catch (e) {
                window.events = [];
            }

            el.remove();
        })();
    </script>

    <script src="{{ url('public/admin_theme/assets/js/app-calendar.js') }}"></script>
@endsection
