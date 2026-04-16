@extends('web.user.layouts.app')

{{-- Replace theme demo events with DB-only feed (runs after app-calendar-events.js) --}}
@push('after_calendar_events_seed')
    <script>
        (function() {
            var el = document.getElementById('teacher-calendar-feed-json');
            if (!el) {
                return;
            }
            var portal = [];
            try {
                portal = JSON.parse(el.textContent || '[]');
            } catch (e) {
                portal = [];
            }
            window.events = Array.isArray(portal) ? portal : [];
            el.remove();
        })();
    </script>
@endpush

@section('content')
    <script type="application/json" id="teacher-calendar-feed-json">@json($calendar_events ?? [])</script>
    <div class="container-xxl flex-grow-1 container-p-y pb-2">
        <div class="card app-calendar-wrapper">
            <div class="row g-0">
                <!-- Calendar Sidebar -->
                <div class="col app-calendar-sidebar border-end" id="app-calendar-sidebar">
                    <div class="border-bottom p-6 my-sm-0 mb-4">
                        <button class="btn btn-primary btn-toggle-sidebar w-100" data-bs-toggle="offcanvas"
                            data-bs-target="#addEventSidebar" aria-controls="addEventSidebar">
                            <i class="icon-base ti tabler-plus icon-16px me-2"></i>
                            <span class="align-middle">Add Event</span>
                        </button>
                    </div>
                    <div class="px-3 pt-2">
                        <!-- inline calendar (flatpicker) -->
                        <div class="inline-calendar"></div>
                    </div>
                    <hr class="mb-6 mx-n4 mt-3" />
                    <div class="px-6 pb-2">
                        <!-- Filter -->
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
                                    if ($swatch !== '' && ($swatch[0] ?? '') !== '#' && preg_match('/^[0-9a-fA-F]{3,8}$/', $swatch)) {
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
                                <p class="small text-muted ms-2 mb-0">No event types yet. Add types under <strong>Event
                                    Types</strong> to filter the calendar.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <!-- /Calendar Sidebar -->

                <!-- Calendar & Modal -->
                <div class="col app-calendar-content">
                    <div class="card shadow-none border-0">
                        <div class="card-body pb-0">
                            <!-- FullCalendar -->
                            <div id="calendar"></div>
                        </div>
                    </div>
                    <div class="app-overlay"></div>
                    <!-- FullCalendar Offcanvas -->
                    <div class="offcanvas offcanvas-end event-sidebar" tabindex="-1" id="addEventSidebar"
                        aria-labelledby="addEventSidebarLabel">
                        <div class="offcanvas-header border-bottom">
                            <h5 class="offcanvas-title" id="addEventSidebarLabel">Add Event</h5>
                            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                                aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body">
                            <form class="event-form pt-0" id="eventForm" method="POST"
                                action="{{ url('user/teacher/events/save-event') }}">
                                @csrf
                                <input type="hidden" name="event_id" id="serverEventId" value="" />
                                <div class="col-12 ajax-msg mb-3"></div>
                                <div class="mb-5 ajax-field form-control-validation">
                                    <label class="form-label" for="eventTitle">Title</label>
                                    <input type="text" class="form-control" id="eventTitle" name="title"
                                        placeholder="Event Title" />
                                    <span class="ajax-error" style="color: red;"></span>
                                </div>
                                <div class="mb-5 ajax-field">
                                    <label class="form-label" for="eventTypeSelect">Event type (database)</label>
                                    <select class="select2 form-select" id="eventTypeSelect" name="event_type_id">
                                        <option value="">Select event type</option>
                                        @foreach ($event_types ?? [] as $et)
                                            <option value="{{ $et->id }}">{{ $et->title }}</option>
                                        @endforeach
                                    </select>
                                    <span class="ajax-error" style="color: red;"></span>
                                </div>
                                <div class="mb-5 ajax-field">
                                    <label class="form-label" for="schedule_event_date">Date</label>
                                    <input type="date" class="form-control" id="schedule_event_date" name="event_date"
                                        value="{{ date('Y-m-d') }}" />
                                    <span class="ajax-error" style="color: red;"></span>
                                </div>
                                <div class="mb-5 ajax-field">
                                    <label class="form-label" for="eventTime">Time</label>
                                    <input type="time" class="form-control" id="eventTime" name="event_time"
                                        value="09:00" />
                                    <span class="ajax-error" style="color: red;"></span>
                                </div>
                                <div class="mb-5">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" id="allClassroomsSwitch"
                                            name="all_classrooms" value="1" />
                                        <label class="form-check-label" for="allClassroomsSwitch">All classrooms</label>
                                    </div>
                                </div>
                                <div class="mb-5 ajax-field">
                                    <label class="form-label" for="eventClassroom">Classroom (optional)</label>
                                    <select class="select2 form-select" id="eventClassroom" name="classroom_id">
                                        <option value="">None</option>
                                        @foreach ($classrooms ?? [] as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="ajax-error" style="color: red;"></span>
                                </div>
                                <div class="mb-5">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" id="allBatchesSwitch"
                                            name="all_batches" value="1" />
                                        <label class="form-check-label" for="allBatchesSwitch">All batches</label>
                                    </div>
                                </div>
                                <div class="mb-5 ajax-field">
                                    <label class="form-label" for="eventBatches">Batch (optional)</label>
                                    <select class="select2 form-select" id="eventBatches" name="batch_id">
                                        <option value="">None</option>
                                        @foreach ($batches ?? [] as $b)
                                            <option value="{{ $b->id }}" data-classroom-id="{{ $b->classroom_id }}">
                                                {{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="ajax-error" style="color: red;"></span>
                                </div>
                                <div class="mb-5 ajax-field">
                                    <label class="form-label" for="eventDescription">Notes (optional)</label>
                                    <textarea class="form-control" name="description" id="eventDescription"
                                        rows="3"></textarea>
                                    <span class="ajax-error" style="color: red;"></span>
                                </div>
                                <div class="mb-5 ajax-field">
                                    <label class="form-label" for="eventStatus">Status</label>
                                    <select class="select2 form-select" id="eventStatus" name="status">
                                        <option value="0">Active</option>
                                        <option value="1">Inactive</option>
                                    </select>
                                    <span class="ajax-error" style="color: red;"></span>
                                </div>
                                <div class="d-flex justify-content-sm-between justify-content-start mt-6 gap-2">
                                    <div class="d-flex">
                                        <button type="submit" id="saveEventToServerBtn"
                                            class="btn btn-primary btn-add-event me-4" data-text-new="Save to schedule"
                                            data-text-edit="Update event">
                                            Save to schedule
                                        </button>
                                        <button type="reset" class="btn btn-label-secondary btn-cancel me-sm-0 me-1"
                                            data-bs-dismiss="offcanvas">
                                            Cancel
                                        </button>
                                    </div>
                                    
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
                <!-- /Calendar & Modal -->
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            var classroomSel = document.getElementById('eventClassroom');
            var batchSel = document.getElementById('eventBatches');
            if (classroomSel && batchSel) {
                function filterBatchesByClassroom() {
                    var cid = classroomSel.value || '';
                    var opts = batchSel.querySelectorAll('option[data-classroom-id]');
                    opts.forEach(function(o) {
                        if (!cid) {
                            o.hidden = false;
                            return;
                        }
                        o.hidden = o.getAttribute('data-classroom-id') !== cid;
                    });
                    var cur = batchSel.querySelector('option:checked');
                    if (cur && cur.hidden) {
                        batchSel.value = '';
                    }
                }
                classroomSel.addEventListener('change', filterBatchesByClassroom);
                filterBatchesByClassroom();
                document.addEventListener('teacherCalendarPrefill', function() {
                    filterBatchesByClassroom();
                });
            }
        })();

        $(document).on('click', '#saveEventToServerBtn', function() {
            clearAjaxErrors('#addEventSidebar');
            var $form = $('#eventForm');
            var scheduleDate = ($form.find('#schedule_event_date').val() || '').trim();
            if (!scheduleDate) {
                $('#addEventSidebar .ajax-msg').html(
                    '<div class="alert alert-danger mb-0" role="alert">Please choose a date.</div>');
                return;
            }
            var scheduleTime = ($form.find('#eventTime').val() || '').trim();
            if (!scheduleTime) {
                $('#addEventSidebar .ajax-msg').html(
                    '<div class="alert alert-danger mb-0" role="alert">Please choose a time.</div>');
                return;
            }

            var btn = $(this);

            function restoreSaveBtnLabel() {
                var editing = ($form.find('#serverEventId').val() || '').trim() !== '';
                btn.text(editing ? (btn.attr('data-text-edit') || 'Update event') : (btn.attr(
                    'data-text-new') || 'Save to schedule'));
            }

            btn.prop('disabled', true).text('Saving...');
            $.post($form.attr('action'), $form.serialize(), function(res) {
                btn.prop('disabled', false);
                restoreSaveBtnLabel();
                processAjaxResponse(res, 1000, '#addEventSidebar', 'no');
            }, 'json').fail(function() {
                btn.prop('disabled', false);
                restoreSaveBtnLabel();
            });
        });
    </script>
@endsection
