<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: serif; font-size: 10px; color: #000; margin: 0; padding: 0; line-height: 1.3; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #000; padding: 4px 5px; vertical-align: top; }
        .no-border td, .no-border th { border: none; }
        .label-cell { font-weight: bold; width: 28%; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .upper { text-transform: uppercase; }
        .note { font-size: 8px; font-style: italic; color: #333; }
        .cb { font-family: dejavusans, sans-serif; font-size: 12px; line-height: 1; padding: 0 1px; }
        .header-title { font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .form-title { font-size: 14px; font-weight: bold; text-transform: uppercase; }
        .section-head { font-weight: bold; background: #f5f5f5; }
        .small { font-size: 8px; }
        .sig-box { height: 50px; }
    </style>
</head>
<body>
@php
    $employee = $details->employee_details ?? [];
    $addressShifts = $details->address_shift_details ?? [];
    $siteCount = count($addressShifts);
    $site1 = $addressShifts[0]['address'] ?? '';
    $site2 = $siteCount > 1 ? ($addressShifts[1]['address'] ?? '') : 'Not Applicable';
    $fullTime = data_get($employee, 'full_time', '');
    $partTime = data_get($employee, 'part_time', '');
    $appDate = $details->created_at ? $details->created_at->format('d-M-y') : '';
    $website = trim($details->website ?? '');
    $email = trim($details->contact_email ?? '');
    $phone = trim($details->contact_mobile ?? '');
    $contactLine = trim(
        ($website !== '' ? 'Website: '.$website : '').
        ($email !== '' ? ($website !== '' ? ', ' : '').'Email: '.$email : '').
        ($phone !== '' ? ($website !== '' || $email !== '' ? ' and ' : '').'Phone number '.$phone : '')
    );
    $cb = '<span class="cb">&#9744;</span>';
@endphp

{{-- Header --}}
<table class="no-border" style="margin-bottom:4px;">
    <tr>
        <td class="no-border center" colspan="2">
            @if ($logoPath)
                <img src="{{ $logoPath }}" style="max-height:55px;" alt="MMS">
            @endif
        </td>
    </tr>
    <tr>
        <td class="no-border center" colspan="2" style="border-top:2px solid #000 !important; padding-top:6px;">
            <span class="header-title">MAGNITUDE MANAGEMENT SERVICES PRIVATE LIMITED</span>
        </td>
    </tr>
</table>

<table class="no-border" style="margin-bottom:6px;">
    <tr>
        <td class="no-border" style="width:30%;">
            <strong>F08 Issue 01</strong><br>
            Rev 00 ({{ $appDate }})
        </td>
        <td class="no-border center form-title" style="width:40%; vertical-align:middle;">APPLICATION FORM</td>
        <td class="no-border" style="width:30%;">&nbsp;</td>
    </tr>
</table>

{{-- Page 1 body --}}
<table>
    <tr>
        <td class="label-cell">Date of Application</td>
        <td colspan="3">{{ $appDate }}</td>
    </tr>
    <tr>
        <td class="label-cell">Name of the Company</td>
        <td colspan="3">{{ $details->company_name ?? '' }}</td>
    </tr>
    <tr>
        <td class="label-cell">Address</td>
        <td colspan="3">{!! nl2br(e($details->address ?? '')) !!}</td>
    </tr>
    <tr>
        <td class="label-cell">Website, Email and Phone number</td>
        <td colspan="3">{{ $contactLine }}</td>
    </tr>
    <tr>
        <td class="label-cell">No of Sites</td>
        <td colspan="3">{{ $siteCount > 0 ? $siteCount : '' }}</td>
    </tr>
    <tr>
        <td class="label-cell">Site 1 Address</td>
        <td colspan="3">{!! nl2br(e($site1)) !!}</td>
    </tr>
    <tr>
        <td class="label-cell">Site 2 Address<br><span class="small">(For more site attach separate Sheet)</span></td>
        <td colspan="3">{!! nl2br(e($site2)) !!}</td>
    </tr>
    <tr>
        <td class="label-cell">Contact Person Name and Designation</td>
        <td colspan="3">
            @if ($details->communication_person)
                 {{ $details->communication_person }}
            @endif
        </td>
    </tr>
    <tr>
        <td class="label-cell">Legal Status</td>
        <td colspan="3">
            Company : Private {!! $cb !!} &nbsp; Public {!! $cb !!} &nbsp; Proprietorship {!! $cb !!} &nbsp; Partnership {!! $cb !!} &nbsp; Govt Undertaken {!! $cb !!} &nbsp; PSU {!! $cb !!} &nbsp; NGO {!! $cb !!}
        </td>
    </tr>
    <tr>
        <td class="label-cell">Statutory and Regulatory Requirement</td>
        <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
        <td class="label-cell">Certification Scheme</td>
        <td colspan="3">
            ISO 9001:2015 {!! $cb !!} ISO 14001:2015 {!! $cb !!} ISO 45001:2018 {!! $cb !!} ISO 22000:2018 {!! $cb !!} GMP {!! $cb !!}<br>
            ISO 27001:2013 {!! $cb !!} ISO 50001:2018 {!! $cb !!} ISO 13484:2016 {!! $cb !!} HACCP {!! $cb !!} GDP {!! $cb !!}
        </td>
    </tr>
    <tr>
        <td class="label-cell">Scope of Certification</td>
        <td colspan="3" class="upper">{!! nl2br(e($details->scope ?? '')) !!}</td>
    </tr>
    <tr>
        <td class="label-cell">Accreditation</td>
        <td colspan="3">EGAC {!! $cb !!}&nbsp;&nbsp;Compliance {!! $cb !!}</td>
    </tr>
    <tr>
        <td class="label-cell">Non Applicability of clause, if any</td>
        <td colspan="3">Clause {!! $cb !!}&nbsp;&nbsp;Justification {!! $cb !!}</td>
    </tr>
    <tr>
        <td class="label-cell">Outsourced Process, If any</td>
        <td colspan="3">&nbsp;<br>&nbsp;</td>
    </tr>
</table>

<table style="margin-top:-1px;">
    <tr>
        <td colspan="7" class="section-head center">No of Employees</td>
    </tr>
    <tr>
        <td class="center bold" style="width:22%;">Location</td>
        <td class="center bold" style="width:10%;">Shifts</td>
        <td class="center bold" style="width:12%;">Full Time</td>
        <td class="center bold" style="width:12%;">Part time</td>
        <td class="center bold" style="width:14%;">Performing Same type of Job</td>
        <td class="center bold" style="width:14%;">Temporary Unskilled workers</td>
        <td class="center bold" style="width:16%;">Effective No. of Employees</td>
    </tr>
    <tr>
        <td>Site 1</td>
        <td>&nbsp;</td>
        <td>{{ $fullTime }}</td>
        <td>{{ $partTime }}</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td>Site 2 (Temporary)</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td class="bold">TOTAL</td>
        <td>&nbsp;</td>
        <td>{{ $fullTime }}</td>
        <td>{{ $partTime }}</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td colspan="7" class="note">
            Note: For EnMS certification, the number of personnel shall be the number of employees (full time, part time, temporary, unskilled workers) who are performing the same type of job and are included in the scope of certification. The effective number of employees is the total number of personnel who are performing the same type of job and are included in the scope of certification.
        </td>
    </tr>
    <tr>
        <td class="label-cell">Certification Program Required</td>
        <td colspan="6">
            Initial {!! $cb !!}&nbsp;&nbsp;Surveillance {!! $cb !!}&nbsp;&nbsp;Recertification {!! $cb !!}&nbsp;&nbsp;Transfer {!! $cb !!}
        </td>
    </tr>
    <tr>
        <td class="label-cell">Combined Audit</td>
        <td colspan="6">
            In the case of several certification programmes, would you like the audits to be Combined or carried out separately?<br>
            Yes {!! $cb !!}&nbsp;&nbsp;No {!! $cb !!}<br>
            If the answer is yes, please specify which combination:<br>&nbsp;
        </td>
    </tr>
    <tr>
        <td class="label-cell">Is Already Certified for any Standard</td>
        <td colspan="6">
            Yes {!! $cb !!}&nbsp;&nbsp;No {!! $cb !!}<br>
            If yes, please mention the standard:<br>&nbsp;
        </td>
    </tr>
    <tr>
        <td class="label-cell">Is Consultants Involved</td>
        <td colspan="6">
            Yes {!! $cb !!}&nbsp;&nbsp;No {!! $cb !!}<br>
            If yes, please mention the consultant:<br>&nbsp;
        </td>
    </tr>
    <tr>
        <td class="label-cell">Key Process Involved</td>
        <td colspan="6">Requirement analysis, design and development, testing, implementation, support and maintenance of ERP and POS software solutions.</td>
    </tr>
    <tr>
        <td class="label-cell">Additional Information Required</td>
        <td colspan="6">&nbsp;<br>&nbsp;</td>
    </tr>
</table>

<pagebreak />

{{-- Page 2 --}}
<table>
    <tr>
        <td colspan="2">
            Do you have Register of Significant Environment aspect? Yes {!! $cb !!} No {!! $cb !!}<br>
            Do you have An Environmental Management Manual? Yes {!! $cb !!} No {!! $cb !!}<br>
            Do you have An Internal Environmental Audit Programme? Yes {!! $cb !!} No {!! $cb !!}<br>
            Has the Internal Environmental Audit Programme been implemented? Yes {!! $cb !!} No {!! $cb !!}
        </td>
    </tr>
    <tr>
        <td class="label-cell" style="width:18%;">FSMS</td>
        <td>
            HACCP Implementation or Study Conducted: Yes {!! $cb !!} No {!! $cb !!}<br>
            No of HACCP Studies: &nbsp;&nbsp;&nbsp; No of Sites: &nbsp;&nbsp;&nbsp; No of Process Lines:<br>
            Processing is: Seasonal {!! $cb !!} Continuous {!! $cb !!}
        </td>
    </tr>
    <tr>
        <td class="label-cell">OHSMS</td>
        <td>
            Hazard's Identified? Yes {!! $cb !!} No {!! $cb !!}<br>
            Detail any critical occupational health &amp; safety risks identified?<br>&nbsp;<br>&nbsp;
        </td>
    </tr>
    <tr>
        <td class="label-cell">EnMS</td>
        <td>
            Annual Energy Consumption (TJ): &nbsp;&nbsp;&nbsp; No Of Energy Sources: &nbsp;&nbsp;&nbsp; Number of significant energy uses (SEUs):
        </td>
    </tr>
    <tr>
        <td class="label-cell">ISMS/ITSMS</td>
        <td>
            <span class="note"><em>Additional Information Required (Tick one in each box)</em><br>
            The following information is required for organizations in critical business sectors and/or where the failure of the management system could have a significant impact on the country.</span>
            <br><br>
            1. Organization work in non critical business sector and non regulated sector {!! $cb !!}<br>
            2. Organization work in critical business sector and/or regulated sector {!! $cb !!}<br>
            3. Organization work in critical business sector and/or regulated sector and the failure of the management system could have a significant impact on the country {!! $cb !!}
        </td>
    </tr>
    <tr>
        <td colspan="2" class="section-head center">Business and organization Complexity</td>
    </tr>
    <tr>
        <td class="label-cell">Types of Business and regulatory Requirement</td>
        <td>
            1. Organization work in non critical business sector and non regulated sector {!! $cb !!}<br>
            2. Organization work in critical business sector and/or regulated sector {!! $cb !!}<br>
            3. Organization work in critical business sector and/or regulated sector and the failure of the management system could have a significant impact on the country {!! $cb !!}
        </td>
    </tr>
    <tr>
        <td class="label-cell">Process and Task</td>
        <td>
            1. Simple Process, Low number of products and services, single business unit included in scope of certification {!! $cb !!}<br>
            2. Moderate Process, Moderate number of products and services, few business units included in scope of certification {!! $cb !!}<br>
            3. Complex Process, High number of products and services, many business units included in scope of certification {!! $cb !!}
        </td>
    </tr>
    <tr>
        <td class="label-cell">Level of establishment of the Management System</td>
        <td>
            1. No elements of other Management system are implemented {!! $cb !!}<br>
            2. Some elements of other Management system are implemented, others not {!! $cb !!}<br>
            3. All elements of other Management system are implemented {!! $cb !!}
        </td>
    </tr>
    <tr>
        <td colspan="2" class="section-head center">IT Environment Complexity</td>
    </tr>
    <tr>
        <td class="label-cell">ITMS Infrastructure Complexity</td>
        <td>
            1. Single IT platform, single server, single location {!! $cb !!}<br>
            2. Several different IT platforms, several servers, several locations {!! $cb !!}<br>
            3. Many different IT platforms, many servers, many locations {!! $cb !!}
        </td>
    </tr>
    <tr>
        <td class="label-cell">ITSMS</td>
        <td>
            Can records be made available for review?<br>&nbsp;
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <strong>DECLARATION:</strong> The above information is true to the best of my knowledge and belief and I am authorized to provide such information on behalf of the company
        </td>
    </tr>
</table>

<table style="margin-top:-1px;">
    <tr>
        <td class="center bold" style="width:25%;">Name</td>
        <td class="center bold" style="width:25%;">Designation</td>
        <td class="center bold" style="width:25%;">Managing Director</td>
        <td class="center bold" style="width:25%;">Signature</td>
    </tr>
    <tr>
        <td class="sig-box">&nbsp;</td>
        <td class="sig-box">&nbsp;</td>
        <td class="sig-box">&nbsp;</td>
        <td class="sig-box">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="4" class="section-head center">MMS Official Use</td>
    </tr>
    <tr>
        <td colspan="4">
            Can the Application Proceed for Application Review : Yes {!! $cb !!} No {!! $cb !!}
        </td>
    </tr>
    <tr>
        <td class="center bold" style="width:33%;">Name of Officer</td>
        <td class="center bold" style="width:34%;">Name of Application reviewer</td>
        <td class="center bold" colspan="2">Date</td>
    </tr>
    <tr>
        <td class="sig-box">&nbsp;</td>
        <td class="sig-box">&nbsp;</td>
        <td class="sig-box" colspan="2">&nbsp;</td>
    </tr>
</table>
</body>
</html>
