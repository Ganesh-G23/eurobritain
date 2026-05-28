@extends('associate.layouts.app')
@php
    $docFields = [
        'legal_proof' => 'Legal Proof of company',
        'pan' => 'PAN / Sales Tax Number / Income Tax Proof',
        'msme_udyog_aadhar' => 'MSME / Udyog Aadhar',
        'gstn' => 'GSTN',
        'factory_registration' => 'Factory Registration',
        'purchase_bills' => 'Copy of purchase bills-2',
        'sales_bills' => 'Copy of sales bills-2',
        'staff_biodata' => 'Copies of staff biodata -2',
        'electricity_bill' => 'Copy electricity bill',
        'product_inspection' => 'Product Inspection / Testing Report',
        'employee_competence_matrix' => 'List of employees/ Competence matrix',
        'previous_iso_ce_certificate' => 'Copy of Previous ISO/CE certificate',
        'suppliers_list' => 'List of suppliers',
        'product_catalogue_brochure' => 'Product Catalogued/Brochure',
        'pollution_clearance_certificate' => 'Pollution Clearance certificate of factories',
    ];
@endphp
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }} — {{ $application->company_name }}</h5>
                <a href="{{ url('certificate-application/list') }}" class="btn btn-label-secondary btn-sm">Back to Application List</a>
            </div>
            <div class="card-body">
                @if (!$clientDocument)
                    <div class="alert alert-warning mb-0">No client documents found for this application.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                @foreach ($docFields as $field => $label)
                                    @php
                                        $urlMap = [
                                            'legal_proof' => $clientDocument->legal_proof_url,
                                            'factory_registration' => $clientDocument->factory_registration_url,
                                            'purchase_bills' => $clientDocument->purchase_bills_url,
                                            'sales_bills' => $clientDocument->sales_bills_url,
                                            'staff_biodata' => $clientDocument->staff_biodata_url,
                                            'electricity_bill' => $clientDocument->electricity_bill_url,
                                            'product_inspection' => $clientDocument->product_inspection_url,
                                            'employee_competence_matrix' => $clientDocument->employee_competence_matrix_url,
                                            'previous_iso_ce_certificate' => $clientDocument->previous_iso_ce_certificate_url,
                                            'suppliers_list' => $clientDocument->suppliers_list_url,
                                            'product_catalogue_brochure' => $clientDocument->product_catalogue_brochure_url,
                                            'pollution_clearance_certificate' => $clientDocument->pollution_clearance_certificate_url,
                                        ];
                                        $url = $urlMap[$field] ?? null;
                                        $value = $clientDocument->{$field};
                                    @endphp
                                    @php
                                        $extension = $value ? strtolower(pathinfo($value, PATHINFO_EXTENSION)) : '';
                                        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']);
                                    @endphp
                                    <tr>
                                        <th style="width:40%">{{ $label }}</th>
                                        <td>
                                            @if (in_array($field, ['pan', 'msme_udyog_aadhar', 'gstn']))
                                                {{ $value ?: '—' }}
                                            @elseif ($url && $isImage)
                                                <a href="{{ $url }}" target="_blank" data-bs-toggle="tooltip" title="Click to open full size">
                                                    <img src="{{ $url }}" alt="{{ $label }}" class="img-thumbnail" style="max-height:140px; max-width:240px; object-fit:contain;">
                                                </a>
                                            @elseif ($url)
                                                <a href="{{ $url }}" target="_blank" class="d-inline-flex align-items-center gap-1">
                                                    <i class="icon-base ti tabler-file-text"></i>
                                                    <span>View file ({{ strtoupper($extension) ?: 'file' }})</span>
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
