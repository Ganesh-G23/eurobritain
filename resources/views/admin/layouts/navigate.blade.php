@php
$sub_active_tab = $sub_active_tab ?? "";
$active_tab = $active_tab ?? "";
@endphp
<!-- Dashboards -->
<li class="menu-item {{ $active_tab == 'dashboard' ? 'active' : '' }}">
    <a href="{{ url('admin/dashboard') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-smart-home"></i>
        <div data-i18n="Dashboard">Dashboard</div>
    </a>
</li>

<!-- Teachers -->
<li class="menu-item {{ $active_tab == 'teacher' ? 'active' : '' }}">
    <a href="{{ url('admin/teacher') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-user"></i>
        <div data-i18n="Teachers">Teachers</div>
    </a>
</li>

<li class="menu-item {{ $active_tab == 'student' ? 'active' : '' }}">
    <a href="{{ url('admin/student') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-user"></i>
        <div data-i18n="Students">Students</div>
    </a>
</li>

<!-- Forms & Tables -->
<li class="menu-header small">
    <span class="menu-header-text" data-i18n="Settings">Settings</span>
</li>
<!-- Forms -->
<li class="menu-item {{ $active_tab == 'profile' ? 'active' : '' }}">
    <a href="{{ url('admin/profile') }}" class="menu-link {{ $active_tab == 'profile' ? 'active' : '' }}">
        <i class="menu-icon icon-base ti tabler-user"></i>
        <div data-i18n="My Profile">My Profile</div>
    </a>
</li>

<li class="menu-item {{ $active_tab == 'security' ? 'active' : '' }}">
    <a href="{{ url('admin/security') }}" class="menu-link {{ $active_tab == 'security' ? 'active' : '' }}">
        <i class="menu-icon icon-base ti tabler-lock"></i>
        <div data-i18n="Change Password">Change Password</div>
    </a>
</li>

<li class="menu-item">
    <a href="{{ url('admin/logout') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-logout"></i>
        <div data-i18n="Logout">Logout</div>
    </a>
</li>