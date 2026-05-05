@extends('web.user.student.layouts.app')

@push('page_styles')
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/fullcalendar/fullcalendar.css') }}" />
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/css/pages/app-calendar.css') }}" />
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/flatpickr/flatpickr.css') }}" />
@endpush

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y pb-2" data-student-events-readonly="1"
        data-save-personal-event="{{ url('user/student/personal-events/save') }}"
        data-delete-personal-event="{{ url('user/student/personal-events/delete') }}">
        <div class="card app-calendar-wrapper">
            <div class="row g-0">
                <div class="col app-calendar-sidebar border-end" id="app-calendar-sidebar">
                    <div class="border-bottom px-6 py-3">
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <h6 class="mb-0">Events</h6>
                            <button type="button" class="btn btn-sm btn-primary" id="btnStudentAddPersonalEvent">
                                Personal event
                            </button>
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
                            <div class="form-check form-check-info mb-3 ms-2">
                                <input class="form-check-input input-filter" type="checkbox" id="filter-personal"
                                    data-value="personal" checked />
                                <label class="form-check-label d-flex align-items-center gap-2" for="filter-personal">
                                    <span class="badge badge-dot me-0 flex-shrink-0"
                                        style="background-color: #0dcaf0 !important; border: 1px solid #0dcaf0;"
                                        aria-hidden="true"></span>
                                    <span>Personal</span>
                                </label>
                            </div>
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
                                <p class="small text-muted ms-2 mb-0">No teacher event types yet for this teacher.</p>
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
                                <div class="mb-4 d-none" id="viewEventDescriptionSection">
                                    <label class="form-label mb-1">Description</label>
                                    <div class="form-control bg-label-secondary" id="viewEventNotes"
                                        style="min-height: 84px; white-space: pre-wrap;"></div>
                                </div>
                                <div class="d-none flex-wrap gap-2 mb-3" id="viewEventPersonalActions">
                                    <button type="button" class="btn btn-sm btn-label-primary" id="btnViewEventEditPersonal">
                                        Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-label-danger"
                                        id="btnViewEventDeletePersonal">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="offcanvas offcanvas-end event-sidebar" tabindex="-1" id="personalEventSidebar"
                        aria-labelledby="personalEventSidebarLabel">
                        <div class="offcanvas-header border-bottom">
                            <h5 class="offcanvas-title" id="personalEventSidebarLabel">Personal event</h5>
                            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                                aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body">
                            <form id="studentPersonalEventForm" class="pt-0">
                                @csrf
                                <input type="hidden" name="id" id="personalEventId" value="" />
                                <div class="mb-3">
                                    <label class="form-label" for="personalEventTitle">Title</label>
                                    <input type="text" class="form-control" name="title" id="personalEventTitle"
                                        required />
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="personalEventStart">Start</label>
                                    <input type="text" class="form-control" name="start_at" id="personalEventStart"
                                        required />
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="personalEventEnd">End</label>
                                    <input type="text" class="form-control" name="end_at" id="personalEventEnd"
                                        required />
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="personalEventAllDay"
                                            checked />
                                        <label class="form-check-label" for="personalEventAllDay">All day</label>
                                    </div>
                                    <input type="hidden" name="all_day" id="personalEventAllDayHidden" value="1" />
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="reminder_eligible"
                                            id="personalEventReminder" value="1" />
                                        <label class="form-check-label" for="personalEventReminder">
                                            Remind me (12h &amp; 6h before)
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label" for="personalEventDescription">Description
                                        (optional)</label>
                                    <textarea class="form-control" name="description" id="personalEventDescription"
                                        rows="3"></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="personalEventSaveBtn">Save</button>
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">
                                        Cancel
                                    </button>
                                </div>
                                <p class="text-danger small mt-2 d-none" id="personalEventFormError"></p>
                            </form>
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

    <script>
        (function() {
            var root = document.querySelector('[data-save-personal-event]');
            if (!root) return;

            var saveUrl = root.getAttribute('data-save-personal-event');
            var deleteUrl = root.getAttribute('data-delete-personal-event');
            var personalSidebar = document.getElementById('personalEventSidebar');
            var bsPersonal =
                personalSidebar && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas ?
                new bootstrap.Offcanvas(personalSidebar) :
                null;

            var pStart = document.getElementById('personalEventStart');
            var pEnd = document.getElementById('personalEventEnd');
            var fpOpts = {
                monthSelectorType: 'static',
                static: true,
                enableTime: true,
                time_24hr: true,
                dateFormat: 'Y-m-d H:i'
            };
            var fpStart = pStart && typeof flatpickr !== 'undefined' ? flatpickr(pStart, fpOpts) : null;
            var fpEnd = pEnd && typeof flatpickr !== 'undefined' ? flatpickr(pEnd, fpOpts) : null;

            function csrfToken() {
                var m = document.querySelector('meta[name="csrf-token"]');
                return m ? m.getAttribute('content') : '';
            }

            function syncPersonalAllDay() {
                var cb = document.getElementById('personalEventAllDay');
                var h = document.getElementById('personalEventAllDayHidden');
                if (cb && h) h.value = cb.checked ? '1' : '0';
            }
            var pAD = document.getElementById('personalEventAllDay');
            if (pAD) pAD.addEventListener('change', syncPersonalAllDay);
            syncPersonalAllDay();

            function resetPersonalForm() {
                document.getElementById('personalEventId').value = '';
                document.getElementById('personalEventTitle').value = '';
                document.getElementById('personalEventDescription').value = '';
                document.getElementById('personalEventReminder').checked = false;
                document.getElementById('personalEventAllDay').checked = true;
                syncPersonalAllDay();
                document.getElementById('personalEventFormError').classList.add('d-none');
                if (fpStart) fpStart.clear();
                if (fpEnd) fpEnd.clear();
            }

            function openPersonalFormFromFcEvent(ev) {
                if (!ev) return;
                var ext = ev.extendedProps || {};
                document.getElementById('personalEventId').value =
                    ext.student_personal_event_id != null ? String(ext.student_personal_event_id) : '';
                document.getElementById('personalEventTitle').value = ev.title || '';
                document.getElementById('personalEventDescription').value =
                    ext.description != null ? String(ext.description) : '';
                document.getElementById('personalEventReminder').checked =
                    ext.reminder_eligible === 1 || ext.reminder_eligible === true || ext.reminder_eligible === '1';
                var allD = !(ext.all_day === false || ext.all_day === 0 || ext.all_day === '0');
                document.getElementById('personalEventAllDay').checked = allD;
                syncPersonalAllDay();
                if (ev.start) {
                    var sd = ev.start instanceof Date ? ev.start : new Date(ev.start);
                    if (!isNaN(sd.getTime()) && fpStart) fpStart.setDate(sd, true);
                }
                var endRaw = ev.end != null ? ev.end : ev.start;
                if (endRaw && fpEnd) {
                    var ed = endRaw instanceof Date ? endRaw : new Date(endRaw);
                    if (!isNaN(ed.getTime())) {
                        if (ev.allDay && endRaw !== ev.start) {
                            ed = new Date(ed.getTime() - 86400000);
                        }
                        fpEnd.setDate(ed, true);
                    }
                }
                document.getElementById('personalEventSidebarLabel').textContent =
                    document.getElementById('personalEventId').value ? 'Edit personal event' : 'Personal event';
                if (bsPersonal) bsPersonal.show();
            }

            document.getElementById('btnStudentAddPersonalEvent').addEventListener('click', function() {
                resetPersonalForm();
                document.getElementById('personalEventSidebarLabel').textContent = 'Personal event';
                var now = new Date();
                if (fpStart) fpStart.setDate(now, true);
                if (fpEnd) fpEnd.setDate(now, true);
                if (bsPersonal) bsPersonal.show();
            });

            document.getElementById('btnViewEventEditPersonal').addEventListener('click', function() {
                var ev = window.__portalLastViewedFcEvent;
                if (!ev || !ev.extendedProps || ev.extendedProps.source !== 'personal') return;
                var viewSb = document.getElementById('viewEventSidebar');
                if (viewSb && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                    var inst = bootstrap.Offcanvas.getInstance(viewSb);
                    if (inst) inst.hide();
                }
                openPersonalFormFromFcEvent(ev);
            });

            document.getElementById('btnViewEventDeletePersonal').addEventListener('click', function() {
                var toolbar = document.getElementById('viewEventPersonalActions');
                var id = toolbar ? toolbar.getAttribute('data-personal-id') : null;
                if (!id || !confirm('Delete this personal event?')) return;
                fetch(deleteUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        Accept: 'application/json'
                    },
                    body: JSON.stringify({
                        id: parseInt(id, 10)
                    })
                })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        if (data.status === 1) {
                            window.location.reload();
                        } else {
                            alert(data.error || 'Could not delete');
                        }
                    })
                    .catch(function() {
                        alert('Could not delete');
                    });
            });

            document.getElementById('studentPersonalEventForm').addEventListener('submit', function(e) {
                e.preventDefault();
                syncPersonalAllDay();
                var errEl = document.getElementById('personalEventFormError');
                errEl.classList.add('d-none');
                var btn = document.getElementById('personalEventSaveBtn');
                btn.disabled = true;
                var fd = new FormData(document.getElementById('studentPersonalEventForm'));
                fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        Accept: 'application/json'
                    },
                    body: fd
                })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        btn.disabled = false;
                        if (data.status === 1) {
                            window.location.reload();
                        } else if (data.error_array) {
                            errEl.textContent = Object.values(data.error_array).flat().join(' ');
                            errEl.classList.remove('d-none');
                        } else {
                            errEl.textContent = data.error || 'Save failed';
                            errEl.classList.remove('d-none');
                        }
                    })
                    .catch(function() {
                        btn.disabled = false;
                        errEl.textContent = 'Save failed';
                        errEl.classList.remove('d-none');
                    });
            });
        })();
    </script>
@endsection
