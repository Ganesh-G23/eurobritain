@php
$sub_active_tab = $sub_active_tab ?? "";
$active_tab = $active_tab ?? "";
$adminLevel = (int) data_get(session('admin'), 'user_level', 0);
@endphp
<!-- Dashboards -->
<li class="menu-item {{ $active_tab == 'dashboard' ? 'active' : '' }}">
    <a href="{{ url('admin/dashboard') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-smart-home"></i>
        <div data-i18n="Dashboard">Dashboard</div>
    </a>
</li>
<!-- Certificate Type -->
<li class="menu-item {{ $active_tab == 'certificate_type' ? 'active open' : '' }}">
    <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ti tabler-certificate"></i>
        <div data-i18n="Certificate Type">Certificate Type</div>
    </a>
    <ul class="menu-sub">
        <li class="menu-item {{ $sub_active_tab == 'add' && $active_tab == 'certificate_type' ? 'active' : '' }}">
            <a href="{{ url('admin/certificate-type/add') }}" class="menu-link">
                <div data-i18n="Add Certificate Type">Add Certificate Type</div>
            </a>
        </li>
        <li class="menu-item {{ $sub_active_tab == 'list' && $active_tab == 'certificate_type' ? 'active' : '' }}">
            <a href="{{ url('admin/certificate-type/list') }}" class="menu-link">
                <div data-i18n="Certificate Type List">Certificate Type List</div>
            </a>
        </li>
    </ul>
</li>
<!-- Associate -->
<li class="menu-item {{ $active_tab == 'associate' ? 'active open' : '' }}">
    <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ti tabler-users"></i>
        <div data-i18n="Associate">Associate</div>
    </a>
    <ul class="menu-sub">
        <li class="menu-item {{ $sub_active_tab == 'add' && $active_tab == 'associate' ? 'active' : '' }}">
            <a href="{{ url('admin/associate/add') }}" class="menu-link">
                <div data-i18n="Add Associate">Add Associate</div>
            </a>
        </li>
        <li class="menu-item {{ $sub_active_tab == 'list' && $active_tab == 'associate' ? 'active' : '' }}">
            <a href="{{ url('admin/associate/list') }}" class="menu-link">
                <div data-i18n="Associate List">Associate List</div>
            </a>
        </li>
    </ul>
</li>
<!-- Client -->
<li class="menu-item {{ $active_tab == 'client' ? 'active open' : '' }}">
    <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ti tabler-building"></i>
        <div data-i18n="Client">Client</div>
    </a>
    <ul class="menu-sub">
        <li class="menu-item {{ $sub_active_tab == 'add' && $active_tab == 'client' ? 'active' : '' }}">
            <a href="{{ url('admin/client/add') }}" class="menu-link">
                <div data-i18n="Add Client">Add Client</div>
            </a>
        </li>
        <li class="menu-item {{ $sub_active_tab == 'list' && $active_tab == 'client' ? 'active' : '' }}">
            <a href="{{ url('admin/client/list') }}" class="menu-link">
                <div data-i18n="Client List">Client List</div>
            </a>
        </li>
    </ul>
</li>
<!-- Certificate Application -->
<li class="menu-item {{ $active_tab == 'certificate_application' ? 'active open' : '' }}">
    <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ti tabler-file-certificate"></i>
        <div data-i18n="Certificate Application">Certificate Application</div>
    </a>
    <ul class="menu-sub">
        <li class="menu-item {{ $sub_active_tab == 'add' && $active_tab == 'certificate_application' ? 'active' : '' }}">
            <a href="{{ url('admin/certificate-application/add') }}" class="menu-link">
                <div data-i18n="Add Application">Add Application</div>
            </a>
        </li>
        <li class="menu-item {{ $sub_active_tab == 'list' && $active_tab == 'certificate_application' ? 'active' : '' }}">
            <a href="{{ url('admin/certificate-application/list') }}" class="menu-link">
                <div data-i18n="Application List">Application List</div>
            </a>
        </li>
    </ul>
</li>
<!-- Certificate -->
<li class="menu-item {{ $active_tab == 'certificate' ? 'active open' : '' }}">
    <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ti tabler-award"></i>
        <div data-i18n="Due Certificates/Audit">Due Certificates/Audit</div>
    </a>
    <ul class="menu-sub">
        <li class="menu-item {{ $sub_active_tab == 'due_list' && $active_tab == 'certificate' ? 'active' : '' }}">
            <a href="{{ url('admin/certificate/due-list') }}" class="menu-link">
                <div data-i18n="Due List">Due List</div>
            </a>
        </li>
        <li class="menu-item {{ $sub_active_tab == 'due_audit_list' && $active_tab == 'certificate' ? 'active' : '' }}">
            <a href="{{ url('admin/certificate/due-audit-list') }}" class="menu-link">
                <div data-i18n="Due Audit List">Due Audit List</div>
            </a>
        </li>
    </ul>
</li>
<!-- Certificate List -->
<li class="menu-item {{ $active_tab == 'certificate_list' ? 'active' : '' }}">
    <a href="{{ url('admin/certificate/list') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-award"></i>
        <div data-i18n="Certificate List">Certificate List</div>
    </a>
</li>
<!-- Invoice -->
<li class="menu-item {{ $active_tab == 'invoice' ? 'active open' : '' }}">
    <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ti tabler-file-invoice"></i>
        <div data-i18n="Invoice">Invoice</div>
    </a>
    <ul class="menu-sub">
        <li class="menu-item {{ $sub_active_tab == 'add' && $active_tab == 'invoice' ? 'active' : '' }}">
            <a href="{{ url('admin/invoice/add') }}" class="menu-link">
                <div data-i18n="Add Invoice">Add Invoice</div>
            </a>
        </li>
        <li class="menu-item {{ $sub_active_tab == 'list' && $active_tab == 'invoice' ? 'active' : '' }}">
            <a href="{{ url('admin/invoice/list') }}" class="menu-link">
                <div data-i18n="Invoice List">Invoice List</div>
            </a>
        </li>
    </ul>
</li>
<!-- Payment -->
<li class="menu-item {{ $active_tab == 'payment' ? 'active open' : '' }}">
    <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ti tabler-cash"></i>
        <div data-i18n="Payment">Payment</div>
    </a>
    <ul class="menu-sub">
        <li class="menu-item {{ $sub_active_tab == 'add' && $active_tab == 'payment' ? 'active' : '' }}">
            <a href="{{ url('admin/payment/add') }}" class="menu-link">
                <div data-i18n="Add Payment">Add Payment</div>
            </a>
        </li>
        <li class="menu-item {{ $sub_active_tab == 'list' && $active_tab == 'payment' ? 'active' : '' }}">
            <a href="{{ url('admin/payment/list') }}" class="menu-link">
                <div data-i18n="Payment List">Payment List</div>
            </a>
        </li>
    </ul>
</li>
<!-- Report -->
<li class="menu-item {{ $active_tab == 'report' ? 'active open' : '' }}">
    <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ti tabler-report"></i>
        <div data-i18n="Report">Report</div>
    </a>
    <ul class="menu-sub">
        <li class="menu-item {{ $sub_active_tab == 'associate' && $active_tab == 'report' ? 'active' : '' }}">
            <a href="{{ url('admin/report/associate') }}" class="menu-link">
                <div data-i18n="Associate Wise">Associate Wise</div>
            </a>
        </li>
        <li class="menu-item {{ $sub_active_tab == 'client' && $active_tab == 'report' ? 'active' : '' }}">
            <a href="{{ url('admin/report/client') }}" class="menu-link">
                <div data-i18n="Client Wise">Client Wise</div>
            </a>
        </li>
    </ul>
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
        <div data-i18n="Security">Security</div>
    </a>
</li>

<li class="menu-item">
    <a href="{{ url('admin/logout') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-logout"></i>
        <div data-i18n="Logout">Logout</div>
    </a>
</li>