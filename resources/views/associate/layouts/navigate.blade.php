@php
    $active_tab = $active_tab ?? '';
    $sub_active_tab = $sub_active_tab ?? '';
@endphp

<li class="menu-item {{ $active_tab == 'dashboard' ? 'active' : '' }}">
    <a href="{{ url('dashboard') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-smart-home"></i>
        <div data-i18n="Dashboard">Dashboard</div>
    </a>
</li>

<li class="menu-item {{ $active_tab == 'client' ? 'active open' : '' }}">
    <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ti tabler-building"></i>
        <div data-i18n="Client">Client</div>
    </a>
    <ul class="menu-sub">
        <li class="menu-item {{ $sub_active_tab == 'add' && $active_tab == 'client' ? 'active' : '' }}">
            <a href="{{ url('client/add') }}" class="menu-link">
                <div data-i18n="Add Client">Add Client</div>
            </a>
        </li>
        <li class="menu-item {{ $sub_active_tab == 'list' && $active_tab == 'client' ? 'active' : '' }}">
            <a href="{{ url('client/list') }}" class="menu-link">
                <div data-i18n="Client List">Client List</div>
            </a>
        </li>
    </ul>
</li>

<li class="menu-item {{ $active_tab == 'certificate_application' ? 'active open' : '' }}">
    <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ti tabler-file-certificate"></i>
        <div data-i18n="Certificate Application">Certificate Application</div>
    </a>
    <ul class="menu-sub">
        <li class="menu-item {{ $sub_active_tab == 'add' && $active_tab == 'certificate_application' ? 'active' : '' }}">
            <a href="{{ url('certificate-application/add') }}" class="menu-link">
                <div data-i18n="Add Application">Add Application</div>
            </a>
        </li>
        <li class="menu-item {{ $sub_active_tab == 'list' && $active_tab == 'certificate_application' ? 'active' : '' }}">
            <a href="{{ url('certificate-application/list') }}" class="menu-link">
                <div data-i18n="Application List">Application List</div>
            </a>
        </li>
    </ul>
</li>


<!-- <li class="menu-item">
    <a href="{{ url('logout') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-logout"></i>
        <div data-i18n="Logout">Logout</div>
    </a>
</li> -->
