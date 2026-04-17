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
                                    <label class="form-label" for="eventLabel">Event Type</label>
                                    <select class="select2 form-select" id="eventLabel" name="event_type_id"
                                        data-select-placeholder="Select Event Type">
                                        <option value="">Select Event Type</option>

                                        @foreach ($event_types as $type)
                                            <option value="{{ $type->id }}" data-color="{{ $type->color_code }}">
                                                {{ $type->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="ajax-error" style="color: red;"></span>
                                </div>
                                <div class="mb-5 ajax-field form-control-validation">
                                    <label class="form-label" for="eventStartDate">Start Date</label>
                                    <input type="text" class="form-control" id="eventStartDate" name="start_date"
                                        placeholder="Start Date" />
                                    <span class="ajax-error" style="color: red;"></span>
                                </div>
                                <div class="mb-5 ajax-field form-control-validation">
                                    <label class="form-label" for="eventEndDate">End Date</label>
                                    <input type="text" class="form-control" id="eventEndDate" name="end_date"
                                        placeholder="End Date" />
                                    <span class="ajax-error" style="color: red;"></span>
                                </div>
                                <!-- Classrooms -->
                                <div class="mb-4 ajax-field select2-primary">
                                    <label class="form-label" for="eventGuests">Add Classrooms</label>
                                    <select class="select2 form-select" id="eventGuests" name="classrooms[]" multiple>
                                        @foreach ($classrooms as $classroom)
                                            <option value="{{ $classroom->id }}">
                                                {{ $classroom->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="ajax-error" style="color: red;"></span>
                                </div>

                                <!-- Batches -->
                                <div class="mb-4 ajax-field select2-primary">
                                    <label class="form-label" for="eventBatches">Add Batches</label>
                                    <select class="select2 form-select" id="eventBatches" name="batches[]" multiple>
                                        @foreach ($batches as $batch)
                                            <option value="{{ $batch->id }}"
                                                data-classroom-id="{{ $batch->classroom_id }}">
                                                {{ $batch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="ajax-error" style="color: red;"></span>
                                </div>

                                <div class="mb-5 ajax-field">
                                    <label class="form-label" for="eventDescription">Notes (optional)</label>
                                    <textarea class="form-control" name="description" id="eventDescription" rows="3"></textarea>
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

            // =============================
            // CLEAR ERRORS
            // =============================
            function clearAjaxErrors(container) {
                var $c = $(container);
                $c.find('.ajax-error').text('');
                $c.find('.form-control').removeClass('is-invalid');

                // Reset Select2 borders (full reset; showAjaxErrors may set border shorthand)
                $c.find('.select2-selection').each(function() {
                    this.style.border = '';
                    this.style.borderColor = '';
                });
            }

            /** Map Laravel/formatErrors keys to actual form field names (e.g. classrooms → classrooms[]). */
            function resolveErrorField(key) {
                if (key === 'classrooms' || key.indexOf('classrooms[') === 0) {
                    return $('[name="classrooms[]"]');
                }
                if (key === 'batches' || key.indexOf('batches[') === 0) {
                    return $('[name="batches[]"]');
                }
                var normalized = key.replace(/\.\d+/g, '[]');
                return $('#eventForm')
                    .find('[name]')
                    .filter(function() {
                        return this.name === normalized;
                    })
                    .first();
            }

            // =============================
            // SHOW ERRORS
            // =============================
            function showAjaxErrors(errors) {
                $.each(errors, function(key, value) {
                    var msg = Array.isArray(value) ? value[0] : value;
                    if (msg == null || msg === '') {
                        return;
                    }

                    var field = resolveErrorField(key);
                    if (!field.length) {
                        return;
                    }

                    var $container = field.closest('.ajax-field');
                    var isSelect2 =
                        field.is('select') &&
                        (field.hasClass('select2') || field.data('select2'));

                    if (isSelect2) {
                        $container.find('.ajax-error').text(msg);
                        field.next('.select2-container').find('.select2-selection').css('border', '1px solid red');
                    } else {
                        field.addClass('is-invalid');
                        $container.find('.ajax-error').text(msg);
                    }
                });
            }

            // =============================
            // FILTER BATCHES BY CLASSROOM
            // =============================
            var classroomSel = $('#eventGuests');
            var batchSel = $('#eventBatches');

            if (classroomSel.length && batchSel.length) {

                function filterBatches() {
                    var selectedClassrooms = classroomSel.val() || [];

                    $('#eventBatches option').each(function() {
                        var classroomId = $(this).data('classroom-id');

                        if (!selectedClassrooms.length) {
                            $(this).prop('disabled', false);
                        } else {
                            $(this).prop('disabled', !selectedClassrooms.includes(String(classroomId)));
                        }
                    });

                    $('#eventBatches').trigger('change.select2');
                }

                classroomSel.on('change', filterBatches);
            }

            // Event type Select2 + colored dot: handled in app-calendar.js (#eventLabel, data-color)

            // =============================
            // CLASSROOM SELECT (TEXT ONLY)
            // =============================
            $('#eventGuests').select2({
                dropdownParent: $('#addEventSidebar'),
                placeholder: "Select Classrooms",

                templateResult: function(data) {
                    return data.text; // 🔥 no image
                },
                templateSelection: function(data) {
                    return data.text;
                }
            });

            // =============================
            // BATCH SELECT (TEXT ONLY)
            // =============================
            $('#eventBatches').select2({
                dropdownParent: $('#addEventSidebar'),
                placeholder: "Select Batches",

                templateResult: function(data) {
                    return data.text;
                },
                templateSelection: function(data) {
                    return data.text;
                }
            });

            // =============================
            // AJAX SUBMIT
            // =============================
            $(document).on('click', '#saveEventToServerBtn', function(e) {
                e.preventDefault();

                clearAjaxErrors('#addEventSidebar');

                var $form = $('#eventForm');
                var btn = $(this);

                btn.prop('disabled', true).text('Saving...');

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    dataType: 'json',

                    success: function(res) {

                        btn.prop('disabled', false).text('Save to schedule');

                        if (res.status === 0) {
                            showAjaxErrors(res.error_array); // 🔥 FIXED
                        } else {
                            processAjaxResponse(res, 1000, '#addEventSidebar', 'no');
                        }
                    },

                    error: function() {
                        btn.prop('disabled', false).text('Save to schedule');
                    }
                });
            });

        })();
    </script>
@endsection
