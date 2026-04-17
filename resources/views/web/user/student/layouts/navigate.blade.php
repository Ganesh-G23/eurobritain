@php
    $portalUser = session('portal_user');
    $initial = strtoupper(substr(trim($portalUser['name'] ?? 'U'), 0, 1));
    $active_tab = $active_tab ?? '';
    $teacher = $teacher ?? null;
@endphp

<nav class="layout-navbar navbar navbar-expand-xl align-items-center" id="layout-navbar">
    <div class="container-xxl">
        <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4 ms-0">
            <a href="{{ url('user/student/dashboard') }}" class="app-brand-link">
                <span class="app-brand-logo demo">
                    <img src="{{ url('public/admin_theme/assets/img/logo.png') }}" alt="EliteGrade Logo" class="img-fluid"
                        style="max-height: 40px;">
                </span>
                <span class="app-brand-text demo menu-text fw-bold text-heading d-none">EliteGrade</span>
            </a>
        </div>

        <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
            <ul class="navbar-nav flex-row align-items-center ms-md-auto">
                @php
                    $currentTeacher = $teacher;
                    if (!$currentTeacher) {
                        $selectedTeacherId = (int) (session('selected_teacher_id') ?? 0);
                        if ($selectedTeacherId > 0) {
                            $currentTeacher = \App\Models\PortalUser::where('role', 1)->find($selectedTeacherId);
                        }
                    }
                    // Fetch mapped teachers for quick switcher
                    $studentId = (int) (session('portal_user.id') ?? (session('portal_user')['id'] ?? 0));
                    $studentTeachers = \Illuminate\Support\Facades\DB::table('portal_user as t')
                        ->join('student_teacher_map as stm', 'stm.teacher_id', '=', 't.id')
                        ->leftJoin('student_classroom_map as scm', function ($join) {
                            $join
                                ->on('scm.student_id', '=', 'stm.student_id')
                                ->on('scm.teacher_id', '=', 'stm.teacher_id')
                                ->whereRaw(
                                    'scm.id = (SELECT MIN(scm2.id) FROM student_classroom_map scm2 WHERE scm2.student_id = stm.student_id AND scm2.teacher_id = stm.teacher_id)',
                                );
                        })
                        ->leftJoin('classrooms as c', 'c.id', '=', 'scm.classroom_id')
                        ->leftJoin('batches as b', 'b.id', '=', 'scm.batch_id')
                        ->where('t.role', 1)
                        ->where('stm.student_id', $studentId)
                        ->whereNull('t.deleted_at')
                        ->orderBy('t.name')
                        ->select([
                            't.id as teacher_id',
                            't.name as teacher_name',
                            't.email as teacher_email',
                            'c.name as classroom_name',
                            'b.name as batch_name',
                        ])
                        ->get();
                @endphp

                <li class="nav-item dropdown me-2">
                    <a class="nav-link dropdown-toggle hide-arrow btn btn-text-secondary rounded-pill d-flex align-items-center px-3 gap-1"
                        id="nav-teacher" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false"
                        title="Switch teacher">
                        <i class="icon-base ti tabler-user-star icon-22px text-heading"></i>
                        <span class="d-none d-sm-inline">
                            {{ $currentTeacher ? $currentTeacher->name ?? 'Teacher' : 'Select Teacher' }}
                        </span>
                        <i class="icon-base ti tabler-chevron-down icon-18px text-heading dropdown-chevron"
                            aria-hidden="true"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-teacher" style="min-width: 260px;">
                        @if (($studentTeachers ?? collect())->count() > 0)
                            <li class="px-3 py-2 text-muted small">Switch teacher</li>
                            @foreach ($studentTeachers as $t)
                                <li>
                                    <a class="dropdown-item"
                                        href="{{ url('user/select-teacher/access/' . (int) $t->teacher_id) }}">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div>
                                                <div>{{ $t->teacher_name }}</div>
                                                <div class="text-body-secondary small">
                                                    @if ($t->classroom_name)
                                                        <span>Classroom:
                                                            {{ $t->classroom_name }}</span>
                                                    @endif
                                                </div>
                                                <div class="text-body-secondary small">
                                                    @if ($t->batch_name)
                                                        <span>Batch: {{ $t->batch_name }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if ($currentTeacher && (int) ($currentTeacher->id ?? 0) === (int) $t->teacher_id)
                                                <i class="icon-base ti tabler-check text-success ms-2"></i>
                                            @endif
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                            <li>
                                <div class="dropdown-divider my-1"></div>
                            </li>
                        @endif
                        <li>
                            <a class="dropdown-item" href="{{ url('user/select-teacher') }}">
                                <i class="icon-base ti tabler-switch-3 me-2"></i> Manage teachers
                            </a>
                        </li>
                    </ul>
                </li>

                @php
                    $studentNavNotifications = $layoutStudentNotifications ?? collect();
                    $studentNavUnreadCount = (int) ($StudentUnreadNotificationCount ?? 0);
                @endphp
                <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-2">
                    <a class="nav-link dropdown-toggle hide-arrow btn btn-icon btn-text-secondary rounded-pill"
                        href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                        aria-expanded="false">
                        <span class="position-relative">
                            <i class="icon-base ti tabler-bell icon-22px text-heading"></i>
                            <span
                                class="badge rounded-pill bg-danger badge-dot badge-notifications border @if ($studentNavUnreadCount < 1) d-none @endif"></span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end p-0">
                        <li class="dropdown-menu-header border-bottom">
                            <div class="dropdown-header d-flex align-items-center py-3">
                                <h6 class="mb-0 me-auto">Notification</h6>
                                <div class="d-flex align-items-center h6 mb-0">
                                    <span class="badge bg-label-primary me-2">{{ $studentNavUnreadCount }} New</span>

                                </div>
                            </div>
                        </li>
                        <li class="dropdown-notifications-list scrollable-container">
                            <ul class="list-group list-group-flush">
                                @forelse ($studentNavNotifications as $navNotification)
                                    @php
                                        $navData = is_array($navNotification->data)
                                            ? $navNotification->data
                                            : json_decode($navNotification->data, true) ?? [];

                                        $navTitle = $navData['title'] ?? 'Notification';
                                        $navMessage = $navData['message'] ?? '';

                                        // ✅ GET IDs
                                        $meta = $navData['meta'] ?? [];
                                        $batchId = $meta['batch_id'] ?? null;
                                        $examId = $meta['exam_id'] ?? null;
                                        $classroomId = $meta['classroom_id'] ?? null;

                                        // ✅ BUILD URL
                                        $url =
                                            $batchId && $examId && $classroomId
                                                ? url(
                                                    'user/student/classroom/' .
                                                        $classroomId .
                                                        '?batch=' .
                                                        $batchId .
                                                        '&exam=' .
                                                        $examId,
                                                )
                                                : 'javascript:void(0)';
                                    @endphp

                                    <li class="list-group-item list-group-item-action dropdown-notifications-item">
                                        <div class="d-flex align-items-start w-100">
                                            <a href="{{ $url }}"
                                                class="d-flex flex-grow-1 min-w-0 text-reset text-decoration-none py-2 ps-3 pe-2">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar">
                                                        <span class="avatar-initial rounded-circle bg-label-success">
                                                            <i class="icon-base ti tabler-clipboard-check"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 min-w-0">
                                                    <h6 class="mb-1 small">{{ $navTitle }}</h6>
                                                    <small class="mb-1 d-block text-body">{{ $navMessage }}</small>
                                                    <small class="text-body-secondary">
                                                        {{ $navNotification->created_at }}
                                                    </small>
                                                </div>
                                            </a>
                                            <div class="flex-shrink-0 dropdown-notifications-actions pt-2 pe-2">
                                                <a href="javascript:void(0)" class="dropdown-notifications-read"
                                                    title="Mark as read"><span class="badge badge-dot"></span></a>
                                                <a href="javascript:void(0)" class="dropdown-notifications-archive"
                                                    data-id="{{ $navNotification->id }}">
                                                    <span class="icon-base ti tabler-x"></span>
                                                </a>

                                            </div>
                                        </div>
                                    </li>

                                @empty
                                    <li class="list-group-item list-group-item-action dropdown-notifications-item">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar">
                                                    <span class="avatar-initial rounded-circle bg-label-secondary">
                                                        <i class="icon-base ti tabler-bell-off"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 small">No notifications</h6>
                                                <small class="mb-1 d-block text-body">
                                                    When your teacher updates your marks, you will see them here.
                                                </small>
                                                <small class="text-body-secondary">—</small>
                                            </div>
                                            <div class="flex-shrink-0 dropdown-notifications-actions">
                                                <a href="javascript:void(0)" class="dropdown-notifications-read">
                                                    <span class="badge badge-dot"></span>
                                                </a>
                                                <!-- ✅ NO data-id here -->
                                                <a href="javascript:void(0)" class="dropdown-notifications-archive">
                                                    <span class="icon-base ti tabler-x"></span>
                                                </a>
                                            </div>
                                        </div>
                                    </li>
                                @endforelse

                            </ul>
                        </li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle hide-arrow btn btn-icon btn-text-secondary rounded-pill"
                        id="nav-theme" href="javascript:void(0);" data-bs-toggle="dropdown">
                        <i class="icon-base ti tabler-sun icon-22px theme-icon-active text-heading"></i>
                        <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-theme-text">
                        <li>
                            <button type="button" class="dropdown-item align-items-center active"
                                data-bs-theme-value="light" aria-pressed="false">
                                <span><i class="icon-base ti tabler-sun icon-22px me-3"
                                        data-icon="sun"></i>Light</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item align-items-center"
                                data-bs-theme-value="dark" aria-pressed="true">
                                <span><i class="icon-base ti tabler-moon-stars icon-22px me-3"
                                        data-icon="moon-stars"></i>Dark</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item align-items-center"
                                data-bs-theme-value="system" aria-pressed="false">
                                <span><i class="icon-base ti tabler-device-desktop-analytics icon-22px me-3"
                                        data-icon="device-desktop-analytics"></i>System</span>
                            </button>
                        </li>
                    </ul>
                </li>
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                    <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);"
                        data-bs-toggle="dropdown">
                        <div class="avatar avatar-online">
                            <span class="avatar-initial rounded-circle bg-label-primary">{{ $initial }}</span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item mt-0" href="{{ url('user/profile') }}">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-2">
                                        <div class="avatar avatar-online">
                                            <span
                                                class="avatar-initial rounded-circle bg-label-primary">{{ $initial }}</span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">{{ $portalUser['name'] ?? '' }}</h6>
                                        <small class="text-body-secondary">{{ $portalUser['email'] ?? '' }}</small>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <div class="dropdown-divider my-1 mx-n2"></div>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ url('user/profile') }}">
                                <i class="icon-base ti tabler-user me-3 icon-md"></i><span class="align-middle">My
                                    Profile</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ url('user/security') }}">
                                <i class="icon-base ti tabler-lock me-3 icon-md"></i><span class="align-middle">Change
                                    Password</span>
                            </a>
                        </li>
                        <li>
                            <div class="dropdown-divider my-1 mx-n2"></div>
                        </li>
                        <li>
                            <div class="d-grid px-2 pt-2 pb-1">
                                <a class="btn btn-sm btn-danger d-flex" href="{{ url('user/logout') }}">
                                    <small class="align-middle">Logout</small>
                                    <i class="icon-base ti tabler-logout ms-2 icon-14px"></i>
                                </a>
                            </div>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<aside id="layout-menu" class="layout-menu-horizontal menu-horizontal menu flex-grow-0">
    <div class="container-xxl d-flex h-100">
        <ul class="menu-inner">
            <li class="menu-item {{ $active_tab === 'student_dashboard' ? 'active' : '' }}">
                <a href="{{ url('user/student/dashboard') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-smart-home"></i>
                    <div>Dashboard</div>
                </a>
            </li>
            <li class="menu-item {{ $active_tab === 'student_classrooms' ? 'active' : '' }}">
                <a href="{{ url('user/student/classrooms') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-school"></i>
                    <div>Classroom</div>
                </a>
            </li>
            <li class="menu-item {{ $active_tab === 'student_events' ? 'active' : '' }}">
                <a href="{{ url('user/student/events') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-calendar-event"></i>
                    <div>Events</div>
                </a>
            </li>
            <li class="menu-item {{ in_array($active_tab, ['profile', 'security']) ? 'active open' : '' }}">
                <a href="javascript:void(0)" class="menu-link menu-toggle">
                    <i class="menu-icon icon-base ti tabler-layout-sidebar"></i>
                    <div>Accounts</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item {{ $active_tab === 'profile' ? 'active' : '' }}">
                        <a href="{{ url('user/profile') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-user"></i>
                            <div>My Profile</div>
                        </a>
                    </li>
                    <li class="menu-item {{ $active_tab === 'security' ? 'active' : '' }}">
                        <a href="{{ url('user/security') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-lock"></i>
                            <div>Change Password</div>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</aside>

@push('portal_notification_scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            $(document).on('click', '.dropdown-notifications-archive', function() {

                let $btn = $(this);
                let id = $btn.data('id');

                $.post("{{ route('notification.delete') }}", {
                    _token: "{{ csrf_token() }}",
                    id: id
                }, function(res) {

                    if (res.status === 1) {

                        // ✅ remove notification row
                        let $item = $btn.closest('li');
                        $item.remove();

                        // ✅ UPDATE COUNT
                        let $countBadge = $('.badge.bg-label-primary');
                        let text = $countBadge.text(); // "2 New"
                        let currentCount = parseInt(text) || 0;

                        if (currentCount > 0) {
                            currentCount--;
                        }

                        $countBadge.text(currentCount + ' New');

                        // ✅ HIDE RED DOT
                        if (currentCount <= 0) {
                            $('.badge-notifications').addClass('d-none');
                        }

                        // ✅ EMPTY STATE
                        if ($('.dropdown-notifications-list ul li').length === 0) {
                            $('.dropdown-notifications-list ul').html(`
                        <li class="list-group-item list-group-item-action dropdown-notifications-item">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar">
                                        <span class="avatar-initial rounded-circle bg-label-secondary">
                                            <i class="icon-base ti tabler-bell-off"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 small">No notifications</h6>
                                    <small class="mb-1 d-block text-body">
                                        No new notifications.
                                    </small>
                                </div>
                            </div>
                        </li>
                    `);
                        }
                    }

                });

            });

        });
    </script>
@endpush
