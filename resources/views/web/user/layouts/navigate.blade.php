@php
    $portalUser = session('portal_user');
    $portalUserInitial = strtoupper(substr(trim($portalUser['name'] ?? 'U'), 0, 1));
    $active_tab = $active_tab ?? '';
    $currentTeacherId = (int) (request()->route('teacherId') ?? 0);
    $selectedTeacherId = (int) (session('selected_teacher_id') ?? 0);
    $portalRole = (int) ($portalUser['role'] ?? 0);
    $teacherNavClassrooms = collect();
    $teacherCurrentClassroomId = 0;
    if ($portalRole === 1 && !empty($portalUser['id'])) {
        $teacherNavClassrooms = \App\Models\Classroom::where('teacher_id', (int) $portalUser['id'])
            ->withCount('batches')
            ->orderBy('name')
            ->get(['id', 'name']);
        if (preg_match('#^user/teacher/classrooms/details/(\d+)$#', request()->path(), $m)) {
            $teacherCurrentClassroomId = (int) $m[1];
        }
    }
    $teacherNavCurrentClassroom =
        $teacherCurrentClassroomId > 0 ? $teacherNavClassrooms->firstWhere('id', $teacherCurrentClassroomId) : null;
@endphp
<nav class="layout-navbar navbar navbar-expand-xl align-items-center" id="layout-navbar">
    <div class="container-xxl">
        <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4 ms-0">
            <a href="{{ $portalRole === 1 ? url('user/dashboard') : ($portalRole === 2 && $selectedTeacherId > 0 ? url('user/student/dashboard') : ($portalRole === 2 ? url('user/select-teacher') : ($portalRole === 3 ? url('user/parent/dashboard') : url('user/dashboard')))) }}"
                class="app-brand-link">
                <span class="app-brand-logo demo">
                    <img src="{{ url('public/admin_theme/assets/img/logo.png') }}" alt="EliteGrade Logo" class="img-fluid"
                        style="max-height: 40px;">
                </span>
                <span class="app-brand-text demo menu-text fw-bold text-heading d-none">EliteGrade</span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
                <i class="icon-base ti tabler-x icon-sm d-flex align-items-center justify-content-center"></i>
            </a>
        </div>

        <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
            <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                <i class="icon-base ti tabler-menu-2 icon-md"></i>
            </a>
        </div>

        <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
            <ul class="navbar-nav flex-row align-items-center ms-md-auto">
                <!-- Search -->
                <li class="nav-item navbar-search-wrapper btn btn-text-secondary btn-icon rounded-pill">
                    <a class="nav-item nav-link search-toggler px-0" href="javascript:void(0);">
                        <span class="d-inline-block text-body-secondary fw-normal" id="autocomplete"></span>
                    </a>
                </li>
                <!-- /Search -->

                @if ($portalRole === 3)
                    @php
                        $selectedStudentId = (int) (session('selected_student_id') ?? 0);
                        $currentStudent =
                            $selectedStudentId > 0
                                ? \App\Models\PortalUser::where('role', 2)->find($selectedStudentId)
                                : null;
                        $parentId = (int) ($portalUser['id'] ?? 0);
                        $children = \Illuminate\Support\Facades\DB::table('portal_user as s')
                            ->leftJoin('parent_student_map as psm', function ($join) use ($parentId) {
                                $join->on('psm.student_id', '=', 's.id')->where('psm.parent_id', '=', $parentId);
                            })
                            ->where('s.role', 2)
                            ->whereNull('s.deleted_at')
                            ->where(function ($q) use ($parentId) {
                                $q->where('s.parent_id', $parentId)->orWhereNotNull('psm.id');
                            })
                            ->orderBy('s.name')
                            ->select(['s.id as student_id', 's.name as student_name', 's.email as student_email'])
                            ->get();
                    @endphp

                    <li class="nav-item dropdown ms-2">
                        <a class="nav-link dropdown-toggle hide-arrow btn btn-text-secondary rounded-pill d-flex align-items-center px-3 gap-1"
                            id="nav-student" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false"
                            title="Switch student">
                            <i class="icon-base ti tabler-users icon-22px text-heading"></i>
                            <span class="d-none d-sm-inline">
                                {{ $currentStudent ? $currentStudent->name ?? 'Student' : 'Select Student' }}
                            </span>
                            <i class="icon-base ti tabler-chevron-down icon-18px text-heading dropdown-chevron"
                                aria-hidden="true"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-student"
                            style="min-width: 280px;">
                            @if (($children ?? collect())->count() > 0)
                                <li class="px-3 py-2 text-muted small">Switch student</li>
                                @foreach ($children as $child)
                                    <li>
                                        <a class="dropdown-item"
                                            href="{{ url('user/select-student/access/' . (int) $child->student_id) }}">
                                            <div class="d-flex align-items-start justify-content-between">
                                                <div>
                                                    <div>{{ $child->student_name }}</div>
                                                    <div class="text-body-secondary small">{{ $child->student_email }}
                                                    </div>
                                                </div>
                                                @if ($currentStudent && (int) ($currentStudent->id ?? 0) === (int) $child->student_id)
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
                                <a class="dropdown-item" href="{{ url('user/select-student') }}">
                                    <i class="icon-base ti tabler-switch-3 me-2"></i> Manage students
                                </a>
                            </li>
                        </ul>
                    </li>

                    @php
                        $parentNavNotifications = $layoutParentNotifications ?? collect();
                        $parentNavUnreadCount = (int) ($layoutParentUnreadNotificationCount ?? 0);
                    @endphp
                    <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-2">
                        <a class="nav-link dropdown-toggle hide-arrow btn btn-icon btn-text-secondary rounded-pill"
                            href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                            aria-expanded="false">
                            <span class="position-relative">
                                <i class="icon-base ti tabler-bell icon-22px text-heading"></i>
                                <span
                                    class="badge rounded-pill bg-danger badge-dot badge-notifications border @if ($parentNavUnreadCount < 1) d-none @endif"></span>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end p-0">
                            <li class="dropdown-menu-header border-bottom">
                                <div class="dropdown-header d-flex align-items-center py-3">
                                    <h6 class="mb-0 me-auto">Notification</h6>
                                    <div class="d-flex align-items-center h6 mb-0">
                                        <span class="badge bg-label-primary me-2">{{ $parentNavUnreadCount }} New</span>
                                    </div>
                                </div>
                            </li>
                            <li class="dropdown-notifications-list scrollable-container">
                                <ul class="list-group list-group-flush">
                                    @forelse ($parentNavNotifications as $navNotification)
                                        @php
                                            $navData = is_array($navNotification->data)
                                                ? $navNotification->data
                                                : json_decode($navNotification->data, true) ?? [];
                                            $navTitle = $navData['title'] ?? 'Notification';
                                            $navMessage = $navData['message'] ?? '';
                                            $navRead = $navNotification->read_at !== null;

                                            $meta = $navData['meta'] ?? [];

                                            $batchId = $meta['batch_id'] ?? null;
                                            $examId = $meta['exam_id'] ?? null;
                                            $classroomId = $meta['classroom_id'] ?? null;
                                            $notifyStudentId = isset($meta['student_id'])
                                                ? (int) $meta['student_id']
                                                : 0;

                                            $marksUrl =
                                                $batchId && $examId && $classroomId
                                                    ? url(
                                                        'user/parent/classroom/' .
                                                            $classroomId .
                                                            '?batch=' .
                                                            $batchId .
                                                            '&exam=' .
                                                            $examId .
                                                            ($notifyStudentId > 0
                                                                ? '&student=' . $notifyStudentId
                                                                : ''),
                                                    )
                                                    : 'javascript:void(0)';
                                        @endphp
                                        <li
                                            class="list-group-item list-group-item-action dropdown-notifications-item @if ($navRead) marked-as-read @endif">
                                            <div class="d-flex align-items-start w-100">
                                                <a href="{{ $marksUrl }}"
                                                    class="d-flex flex-grow-1 min-w-0 text-reset text-decoration-none py-2 ps-3 pe-2">
                                                    <div class="flex-shrink-0 me-3">
                                                        <div class="avatar">
                                                            <span
                                                                class="avatar-initial rounded-circle bg-label-success"><i
                                                                    class="icon-base ti tabler-clipboard-check"></i></span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 min-w-0">
                                                        <h6 class="mb-1 small">{{ $navTitle }}</h6>
                                                        @if (!empty($navData['student_name']))
                                                            <small
                                                                class="d-block text-body-secondary mb-1">{{ $navData['student_name'] }}</small>
                                                        @endif
                                                        <small
                                                            class="mb-1 d-block text-body">{{ $navMessage }}</small>
                                                        <small
                                                            class="text-body-secondary">{{ $navNotification->created_at }}</small>
                                                    </div>
                                                </a>
                                                <div class="flex-shrink-0 dropdown-notifications-actions pt-2 pe-2">
                                                    <a href="javascript:void(0)"
                                                        class="dropdown-notifications-read"><span
                                                            class="badge badge-dot"></span></a>
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
                                                        <span
                                                            class="avatar-initial rounded-circle bg-label-secondary"><i
                                                                class="icon-base ti tabler-bell-off"></i></span>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1 small">No notifications</h6>
                                                    <small class="mb-1 d-block text-body">When a teacher updates your
                                                        child&apos;s marks, you will see alerts here.</small>
                                                    <small class="text-body-secondary">—</small>
                                                </div>
                                                <div class="flex-shrink-0 dropdown-notifications-actions">
                                                    <a href="javascript:void(0)"
                                                        class="dropdown-notifications-read"><span
                                                            class="badge badge-dot"></span></a>
                                                    <a href="javascript:void(0)"
                                                        class="dropdown-notifications-archive"><span
                                                            class="icon-base ti tabler-x"></span></a>
                                                </div>
                                            </div>
                                        </li>
                                    @endforelse
                                </ul>
                            </li>
                        </ul>
                    </li>
                @endif

                @if ($portalRole === 1)
                    <li class="nav-item dropdown ms-2">
                        <a class="nav-link dropdown-toggle hide-arrow btn btn-text-secondary rounded-pill d-flex align-items-center px-3 gap-1"
                            id="nav-classroom" href="javascript:void(0);" data-bs-toggle="dropdown"
                            aria-expanded="false" title="Switch classroom">
                            <i class="icon-base ti tabler-school icon-22px text-heading"></i>
                            <span class="d-none d-sm-inline">
                                {{ $teacherNavCurrentClassroom ? $teacherNavCurrentClassroom->name : 'Classrooms' }}
                            </span>
                            <i class="icon-base ti tabler-chevron-down icon-18px text-heading" aria-hidden="true"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-classroom"
                            style="min-width: 280px;">
                            @if ($teacherNavClassrooms->isNotEmpty())
                                <li class="px-3 py-2 text-muted small">Switch classroom</li>
                                @foreach ($teacherNavClassrooms as $cr)
                                    <li>
                                        <a class="dropdown-item"
                                            href="{{ url('user/teacher/classrooms/details/' . $cr->id) }}">
                                            <div class="d-flex align-items-start justify-content-between">
                                                <div>
                                                    <div>{{ $cr->name }}</div>
                                                    @if (($cr->batches_count ?? 0) > 0)
                                                        <div class="text-body-secondary small">
                                                            {{ (int) $cr->batches_count }}
                                                            batch{{ (int) $cr->batches_count === 1 ? '' : 'es' }}
                                                        </div>
                                                    @endif
                                                </div>
                                                @if ($teacherCurrentClassroomId === (int) $cr->id)
                                                    <i
                                                        class="icon-base ti tabler-check text-success ms-2 flex-shrink-0"></i>
                                                @endif
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                                <!-- <li>
                                    <div class="dropdown-divider my-1"></div>
                                </li> -->
                            @endif
                            <!-- <li>
                                <a class="dropdown-item" href="{{ url('user/teacher/classrooms') }}">
                                    <i class="icon-base ti tabler-switch-3 me-2"></i> Manage classrooms
                                </a>
                            </li> -->
                        </ul>
                    </li>
                @endif

                <!--/ Language -->

                <!-- Style Switcher -->

                @if ($portalRole === 1)
                    @php
                        $teacherNavNotifications = $layoutTeacherNotifications ?? collect();
                        $teacherUnreadCount = (int) ($teacherUnreadNotificationCount ?? 0);
                    @endphp
                    <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-2">
                        <a class="nav-link dropdown-toggle hide-arrow btn btn-icon btn-text-secondary rounded-pill"
                            href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                            aria-expanded="false">
                            <span class="position-relative">
                                <i class="icon-base ti tabler-bell icon-22px text-heading"></i>
                                <span
                                    class="badge rounded-pill bg-danger badge-dot badge-notifications border @if ($teacherUnreadCount < 1) d-none @endif"></span>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end p-0">
                            <li class="dropdown-menu-header border-bottom">
                                <div class="dropdown-header d-flex align-items-center py-3">
                                    <h6 class="mb-0 me-auto">Notification</h6>
                                    <div class="d-flex align-items-center h6 mb-0">
                                        <span class="badge bg-label-primary me-2">{{ $teacherUnreadCount }} New</span>

                                    </div>
                                </div>
                            </li>
                            <li class="dropdown-notifications-list scrollable-container">
                                <ul class="list-group list-group-flush">
                                    @forelse ($teacherNavNotifications as $navNotification)
                                        @php
                                            $navData = is_array($navNotification->data)
                                                ? $navNotification->data
                                                : json_decode($navNotification->data, true) ?? [];

                                            $navTitle = $navData['title'] ?? 'Notification';
                                            $navMessage = $navData['message'] ?? '';
                                            $navType = $navData['type'] ?? '';
                                            $teacherLeaveUrl =
                                                $navType === 'leave' ? url('user/teacher/students/leave') : 'javascript:void(0)';
                                        @endphp

                                        <li class="list-group-item list-group-item-action dropdown-notifications-item">
                                            <div class="d-flex align-items-start w-100">
                                                <a href="{{ $teacherLeaveUrl }}"
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
                @endif

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
                <!-- / Style Switcher-->

                <!-- User -->
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                    <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);"
                        data-bs-toggle="dropdown">
                        <div class="avatar avatar-online">
                            <span
                                class="avatar-initial rounded-circle bg-label-primary">{{ $portalUserInitial }}</span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item mt-0" href="{{ url('user/profile') }}">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-2">
                                        <div class="avatar avatar-online">
                                            <span
                                                class="avatar-initial rounded-circle bg-label-primary">{{ $portalUserInitial }}</span>
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
                        @if ($portalRole === 1)
                            <li>
                                <a class="dropdown-item" href="{{ url('user/profile/settings') }}">
                                    <i class="icon-base ti tabler-settings me-3 icon-md"></i><span
                                        class="align-middle">Settings</span>
                                </a>
                            </li>
                        @endif

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
                <!--/ User -->
            </ul>
        </div>
    </div>
</nav>


<!-- Menu -->
<aside id="layout-menu" class="layout-menu-horizontal menu-horizontal menu flex-grow-0">
    <div class="container-xxl d-flex h-100">
        <ul class="menu-inner">
            @if ($portalRole === 1)
                <!-- Teacher Panel Menus -->
                <li class="menu-item {{ in_array($active_tab, ['dashboard', 'student_dashboard']) ? 'active' : '' }}">
                    <a href="{{ url('user/dashboard') }}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-smart-home"></i>
                        <div data-i18n="Dashboards">Dashboards</div>
                    </a>
                </li>

                <!-- Removed Attendance to match admin flow -->

                <li class="menu-item {{ $active_tab === 'teacher_classrooms' ? 'active' : '' }}">
                    <a href="{{ url('user/teacher/classrooms') }}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-school"></i>
                        <div data-i18n="Classrooms">Classrooms</div>
                    </a>
                </li>

                <li class="menu-item {{ $active_tab === 'teacher_batches' ? 'active' : '' }}">
                    <a href="{{ url('user/teacher/batches') }}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-folders"></i>
                        <div data-i18n="Batches">Batches</div>
                    </a>
                </li>

                <li class="menu-item {{ $active_tab === 'teacher_students' ? 'active' : '' }}">
                    <a href="{{ url('user/teacher/students') }}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-users"></i>
                        <div data-i18n="Students">Students</div>
                    </a>
                </li>

                <li class="menu-item {{ $active_tab === 'events' ? 'active' : '' }}">
                    <a href="{{ url('user/teacher/events') }}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-calendar"></i>
                        <div data-i18n="Events">Events</div>
                    </a>
                </li>

                <li class="menu-item {{ $active_tab === 'event_types' ? 'active' : '' }}">
                    <a href="{{ url('user/teacher/event_types') }}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-calendar"></i>
                        <div data-i18n="Event Types">Event Types</div>
                    </a>
                </li>
                <li class="menu-item {{ $active_tab === 'student_leave' ? 'active' : '' }}">
                    <a href="{{ url('user/teacher/students/leave') }}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-calendar-off"></i>
                        <div data-i18n="Student Leave">Student Leave</div>
                    </a>
                </li>
                <li class="menu-item {{ $active_tab === 'leaderboard' ? 'active' : '' }}">
                    <a href="{{ url('user/teacher/leaderboard') }}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-calendar"></i>
                        <div data-i18n="Leaderboard">Leaderboard</div>
                    </a>
                </li>
                <li class="menu-item {{ $active_tab === 'complaints' ? 'active' : '' }}">
                    <a href="{{ url('user/teacher/complaints') }}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-alert-circle"></i>
                        <div data-i18n="Complaints">Complaints</div>
                    </a>
                </li>
                <li class="menu-item {{ $active_tab === 'timelines' ? 'active' : '' }}">
                    <a href="{{ url('user/teacher/timelines/list') }}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-alert-circle"></i>
                        <div data-i18n="Timeline">Timeline</div>
                    </a>
                </li>
                <li
                    class="menu-item {{ in_array($active_tab, ['profile', 'security', 'teacher_settings']) ? 'active open' : '' }}">
                    <a href="javascript:void(0)" class="menu-link menu-toggle">
                        <i class="menu-icon icon-base ti tabler-layout-sidebar"></i>
                        <div data-i18n="Accounts">Accounts</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item {{ $active_tab === 'profile' ? 'active' : '' }}">
                            <a href="{{ url('user/profile') }}" class="menu-link">
                                <i class="menu-icon icon-base ti tabler-user"></i>
                                <div data-i18n="My Profile">My Profile</div>
                            </a>
                        </li>
                        <li class="menu-item {{ $active_tab === 'security' ? 'active' : '' }}">
                            <a href="{{ url('user/security') }}" class="menu-link">
                                <i class="menu-icon icon-base ti tabler-lock"></i>
                                <div data-i18n="Change Password">Change Password</div>
                            </a>
                        </li>
                        <li class="menu-item {{ $active_tab === 'teacher_settings' ? 'active' : '' }}">
                            <a href="{{ url('user/profile/settings') }}" class="menu-link">
                                <i class="menu-icon icon-base ti tabler-settings"></i>
                                <div data-i18n="Settings">Settings</div>
                            </a>
                        </li>
                    </ul>
                </li>
            @else
                <!-- Student/Parent -->
                <li class="menu-item {{ $active_tab === 'dashboard' ? 'active' : '' }}">
                    <a href="{{ $portalRole === 2 ? ($selectedTeacherId > 0 ? url('user/student/dashboard') : url('user/select-teacher')) : ($portalRole === 3 ? url('user/parent/dashboard') : url('user/dashboard')) }}"
                        class="menu-link">
                        <i class="menu-icon icon-base ti tabler-smart-home"></i>
                        <div data-i18n="Dashboard">Dashboard</div>
                    </a>
                </li>
                <li class="menu-item {{ $active_tab === 'classrooms' ? 'active' : '' }}">
                    <a href="{{ $portalRole === 2 ? ($selectedTeacherId > 0 ? url('user/student/classrooms') : url('user/select-teacher')) : ($portalRole === 3 ? ((int) (session('selected_student_id') ?? 0) > 0 ? url('user/parent/classrooms') : url('user/select-student')) : url('user/dashboard')) }}"
                        class="menu-link">
                        <i class="menu-icon icon-base ti tabler-school"></i>
                        <div data-i18n="Classrooms">Classrooms</div>
                    </a>
                </li>
                <li
                    class="menu-item {{ in_array($active_tab, ['student_events', 'parent_events'], true) ? 'active' : '' }}">
                    <a href="{{ $portalRole === 2 ? ($selectedTeacherId > 0 ? url('user/student/events') : url('user/select-teacher')) : ($portalRole === 3 ? ((int) (session('selected_student_id') ?? 0) > 0 ? url('user/parent/events') : url('user/select-student')) : url('user/dashboard')) }}"
                        class="menu-link">
                        <i class="menu-icon icon-base ti tabler-calendar-event"></i>
                        <div data-i18n="Events">Events</div>
                    </a>
                </li>
                <li class="menu-item {{ $active_tab === 'leave' ? 'active' : '' }}">
                    <a href="{{ $portalRole === 2 ? ($selectedTeacherId > 0 ? url('user/student/leave') : url('user/select-teacher')) : ($portalRole === 3 ? url('user/parent/leave') : url('user/dashboard')) }}"
                        class="menu-link">
                        <i class="menu-icon icon-base ti tabler-calendar-off"></i>
                        <div data-i18n="StudentLeave">Student leave</div>
                    </a>
                </li>
                <li class="menu-item {{ $active_tab === 'complaints' ? 'active' : '' }}">
                    <a href="{{ $portalRole === 2 ? ($selectedTeacherId > 0 ? url('user/student/complaints') : url('user/select-teacher')) : ($portalRole === 3 ? url('user/parent/complaints') : url('user/dashboard')) }}"
                        class="menu-link">
                        <i class="menu-icon icon-base ti tabler-alert-circle"></i>
                        <div data-i18n="Complaints">Complaints</div>
                    </a>
                </li>
                <li class="menu-item {{ in_array($active_tab, ['profile', 'security']) ? 'active open' : '' }}">
                    <a href="javascript:void(0)" class="menu-link menu-toggle">
                        <i class="menu-icon icon-base ti tabler-layout-sidebar"></i>
                        <div data-i18n="Accounts">Accounts</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item {{ $active_tab === 'profile' ? 'active' : '' }}">
                            <a href="{{ url('user/profile') }}" class="menu-link">
                                <i class="menu-icon icon-base ti tabler-user"></i>
                                <div data-i18n="My Profile">My Profile</div>
                            </a>
                        </li>
                        <li class="menu-item {{ $active_tab === 'security' ? 'active' : '' }}">
                            <a href="{{ url('user/security') }}" class="menu-link">
                                <i class="menu-icon icon-base ti tabler-lock"></i>
                                <div data-i18n="Change Password">Change Password</div>
                            </a>
                        </li>
                    </ul>
                </li>
            @endif
        </ul>
    </div>
</aside>
<!-- / Menu -->

@push('portal_notification_scripts')
    <script>
        (function() {
            if (window.__portalNotificationDeleteInit) {
                return;
            }
            window.__portalNotificationDeleteInit = true;
            document.addEventListener('click', function(e) {
                var btn = e.target.closest('a.dropdown-notifications-archive');
                if (!btn || !btn.getAttribute('data-id')) {
                    return;
                }
                e.preventDefault();
                e.stopImmediatePropagation();
                var id = btn.getAttribute('data-id');
                var $btn = $(btn);
                $.ajax({
                    url: "{{ route('notification.delete') }}",
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: id,
                    },
                    success: function(res) {
                        if (res.status === 1) {

                            // remove notification row
                            var $item = $btn.closest('li');
                            $item.remove();

                            // 🔥 UPDATE COUNT TEXT
                            var $countBadge = $('.badge.bg-label-primary');
                            var text = $countBadge.text(); // "2 New"
                            var currentCount = parseInt(text) || 0;

                            if (currentCount > 0) {
                                currentCount--;
                            }

                            $countBadge.text(currentCount + ' New');

                            // 🔥 HIDE RED DOT IF 0
                            if (currentCount <= 0) {
                                $('.badge-notifications').addClass('d-none');
                            }

                            // 🔥 IF NO NOTIFICATIONS LEFT → SHOW EMPTY UI
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
                    }

                });
            }, true);
        })();
    </script>
@endpush
