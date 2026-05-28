<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: serif; font-size: 11px; color: #000; margin: 0; padding: 0; }
        table { border-collapse: collapse; width: 100%; }
        .th-label { background-color: #f0f0f0; font-weight: bold; text-align: left; vertical-align: top; }
        td, th { border: 1px solid #000; padding: 5px; vertical-align: top; }
        .declaration { font-size: 8px; text-align: justify; line-height: 1.25; }
        .disclaimer-cell { font-size: 10px; text-align: justify; line-height: 1.35; }
        .signature-box { height: 70px; border-bottom: 1px solid #000; }
        .signature-label { text-align: center; font-weight: bold; padding: 4px 5px; }
        .footer-table td { border: none; font-size: 10px; padding: 2px 4px; }
        .title-header { font-size: 16px; font-weight: bold; text-transform: uppercase; text-align: right; vertical-align: middle; }
        .no-border { border: none !important; }
        .light-text { color: #222; font-weight: normal; font-size: 10px; }
    </style>
</head>
<body>
@php
    $director = $details->director_details ?? [];
    $employee = $details->employee_details ?? [];
    $addressShifts = $details->address_shift_details ?? [];
    $auditTypesText = !empty($auditTypeNames) ? implode(', ', $auditTypeNames) : '';
    $certificateStandard = $details->certificateType?->description ?? ($details->certificateType?->code ?? '');
@endphp

<table class="no-border" style="width:100%; margin-bottom:8px;">
    <tr>
        <td class="no-border" style="width:45%; vertical-align:middle;">
            @if ($logoPath)
                <img src="{{ $logoPath }}" style="max-height:60px;" alt="EUROBRITAIN">
            @endif
        </td>
        <td class="no-border title-header" style="width:55%;">CERTIFICATION APPLICATION FORM</td>
    </tr>
</table>

<table style="margin-top:0;">
    <tr>
        <td colspan="4" class="disclaimer-cell">
            The information you have given in this form is forwarded directly to the certification department. The wrong information given in this form may cause the wrong preparation of your certificate. Our company is not responsible for the enforcements caused given wrong and/or missing information. Please be sure from the correctness of the information and approve.
        </td>
    </tr>
    <tr>
        <td class="th-label" style="width:35%;">Full Name Of The Company  <span class="light-text">:(mentioned in Commercial registry gazette)</span></td>
        <td colspan="3">{{ $details->company_name ?? '' }}</td>
    </tr>
    <tr>
        <td class="th-label">Full Address Of The Company  <span class="light-text">:(mentioned in Commercial registry gazette)</span></td>
        <td colspan="3">{{ $details->address ?? '' }}</td>
    </tr>
    <tr>
        <td class="th-label">Scope  <span class="light-text">:(In English)</span></td>
        <td colspan="3">{{ $details->scope ?? '' }}</td>
    </tr>
    <tr>
        <td class="th-label" style="width:25%;">Phone Number :</td>
        <td style="width:25%;">{{ $details->contact_mobile ?? '' }}</td>
        <td class="th-label" style="width:25%;">Fax Number :</td>
        <td style="width:25%;">{{ $details->fax_number ?? '' }}</td>
    </tr>
    <tr>
        <td class="th-label">Email :</td>
        <td>{{ $details->contact_email ?? '' }}</td>
        <td class="th-label">Website :</td>
        <td>{{ $details->website ?? '' }}</td>
    </tr>
    <tr>
        <td class="th-label">Name and Title of the person who will be communicated :</td>
        <td>{{ $details->communication_person ?? '' }}</td>
        <td class="th-label">Management Representative Name :</td>
        <td>{{ $details->management_representative ?? '' }}</td>
    </tr>
    <tr>
        <td class="th-label">Name and Title of the Top Manager :</td>
        <td>{{ $details->top_manager ?? '' }}</td>
        <td class="th-label">Mobile Phone of the Top Management :</td>
        <td>{{ $details->top_management_mobile ?? '' }}</td>
    </tr>
    <tr>
        <td class="th-label">Name of Director/Partner/Proprietor :</td>
        <td>{{ data_get($director, 'first_name', '') }}</td>
        <td>{{ data_get($director, 'middle_name', '') }}</td>
        <td>{{ data_get($director, 'last_name', '') }}</td>
    </tr>
    <tr>
        <td class="th-label">Employee Number :</td>
        <td>{{ data_get($employee, 'employee_number', '') }}</td>
        <td class="th-label">Full Time :</td>
        <td>{{ data_get($employee, 'full_time', '') }}</td>
    </tr>
    <tr>
        <td class="th-label">Part Time :</td>
        <td colspan="3">{{ data_get($employee, 'part_time', '') }}</td>
    </tr>
    <tr>
        <td class="th-label" colspan="4">Number of sites &amp; shifts (if more than one please provide the details) :</td>
    </tr>
    @forelse ($addressShifts as $site)
        <tr>
            <td class="th-label" style="width:35%;">Address :</td>
            <td style="width:35%;">{{ $site['address'] ?? '' }}</td>
            <td class="th-label" style="width:15%;">Shifts :</td>
            <td style="width:15%;">
                @foreach ($site['shifts'] ?? [] as $shift)
                    @if (!$loop->first)<br>@endif
                    {{ ($shift['from'] ?? '') }} to {{ ($shift['to'] ?? '') }}
                @endforeach
            </td>
        </tr>
    @empty
        <tr>
            <td class="th-label">Address :</td>
            <td></td>
            <td class="th-label">Shifts :</td>
            <td></td>
        </tr>
    @endforelse
    <tr>
        <td class="th-label">Subcontractor :</td>
        <td>{{ $details->subcontractor ?? '' }}</td>
        <td class="th-label">In main Process :</td>
        <td>{{ $details->in_main_process ?? '' }}</td>
    </tr>
    <tr>
        <td class="th-label">Number of Executive Personnel :</td>
        <td>{{ $details->executive_personnel ?? '' }}</td>
        <td class="th-label">In Design :</td>
        <td>{{ $details->in_design ?? '' }}</td>
    </tr>
    <tr>
        <td class="th-label" style="width:35%;">Service Requested System Standard :</td>
        <td colspan="3">{{ $certificateStandard }}</td>
    </tr>
    <tr>
        <td class="th-label">Service Requested Audit Type :</td>
        <td colspan="3">{{ $auditTypesText }}</td>
    </tr>
    <tr>
        <td colspan="4" class="declaration">
            <strong>Declaration :</strong> We have read the EUROBRITAIN'S Application Terms Information Form (FRM.12) and commit to apply these. I declare the currency and correctness of all the information given above, accept the responsibility of the negative situations caused from the missing information or informing wrongly. Also, We are aware that the certificate is granted for specified tenure &amp; will remain valid subject to the timely audits done for maintaining the required standards. The certification details &amp; effectiveness are listed at www.eurobritain.co.uk. We understand that our product comes under notified certification category. We also understand that this is a non- notified certificate, non IAF accredited and based on self- declaration submit by us. The DFWQAA or EUROBRITAIN does not assure the quality of the products produced or the services offered. The relationship between EUROBRITAIN and DFWQAA is its membership in the institute. The certificate is granted based on audit done as per relevant EUROBRITAIN standards on corresponding date &amp; EUROBRITAIN is not responsible for our failure to maintain relevant standards.
        </td>
    </tr>
    <tr>
        <td class="signature-box" style="width:25%; padding:0;"></td>
        <td class="signature-box" style="width:25%; padding:0;"></td>
        <td class="signature-box" style="width:25%; padding:0;"></td>
        <td class="signature-box" style="width:25%; padding:0;"></td>
    </tr>
    <tr>
        <td class="signature-label">Signature &amp; Seal</td>
        <td class="signature-label">Name of Signatory</td>
        <td class="signature-label">Designation</td>
        <td class="signature-label">Date</td>
    </tr>
</table>

<table class="footer-table" style="width:100%; margin-top:8px;">
    <tr>
        <td style="width:40%; text-align:left;"><strong>Address:</strong> 63/66 Hatton Garden, Fifth Floor Suite 23, London, ECIN 8LE, UK</td>
        <td style="width:30%; text-align:center;"><strong>Email:</strong> info@eurobritain.co.uk</td>
        <td style="width:30%; text-align:right;"><strong>Website:</strong> www.eurobritain.co.uk</td>
    </tr>
</table>
</body>
</html>
