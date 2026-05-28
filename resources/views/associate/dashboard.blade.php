@extends('associate.layouts.app')

@section('content')
    @php
        $associate = $associate ?? session('associate');
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3">Welcome, {{ data_get($associate, 'company_name', 'Associate') }}</h4>
                <p class="mb-1"><strong>Contact Person:</strong> {{ data_get($associate, 'contact_person', '-') }}</p>
                <p class="mb-1"><strong>Email:</strong> {{ data_get($associate, 'contact_email', '-') }}</p>
                <p class="mb-0"><strong>Mobile:</strong> {{ data_get($associate, 'contact_mobile', '-') }}</p>
            </div>
        </div>
    </div>
@endsection
