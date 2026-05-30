<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: serif; font-size: 11px; color: #000; margin: 0; padding: 0; line-height: 1.35; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #000; padding: 4px 6px; vertical-align: top; }
        .no-border td, .no-border th { border: none; }
        .label-cell { font-weight: bold; width: 28%; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .upper { text-transform: uppercase; }
        .header-title { font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .form-title { font-size: 14px; font-weight: bold; text-transform: uppercase; }
        .info-value { min-height: 18px; }
        .attendance-table th { font-weight: bold; background: #f5f5f5; }
        .attendance-table td { height: 35px; }
        .sn-col { width: 6%; text-align: center; }
    </style>
</head>
<body>
@php
    $auditDate = $details->created_at ? $details->created_at->format('d-m-Y') : '';
    $standard = $details->certificateType?->description ?? ($details->certificateType?->code ?? '');
    $companyTitle = $isIaf
        ? 'MAGNITUDE MANAGEMENT SERVICES PRIVATE LIMITED'
        : '';
@endphp

<table class="no-border" style="margin-bottom:4px;">
    <tr>
        <td class="no-border center" colspan="3">
            @if ($logoPath)
                <img src="{{ $logoPath }}" style="max-height:55px;" alt="Logo">
            @endif
        </td>
    </tr>
    <tr>
        <td class="no-border center" colspan="3" style="border-top:2px solid #000 !important; padding-top:6px;">
            <span class="header-title">{{ $companyTitle }}</span>
        </td>
    </tr>
</table>

<table class="no-border" style="margin-bottom:8px;">
    <tr>
        <td class="no-border" style="width:30%;">
            <strong>F22 Issue 01</strong><br>
            Rev 00 ({{ $auditDate }})
        </td>
        <td class="no-border center form-title" style="width:40%; vertical-align:middle;">ATTENDENCE SHEET</td>
        <td class="no-border" style="width:30%;">&nbsp;</td>
    </tr>
</table>

<table style="margin-bottom:10px;">
    <tr>
        <td class="label-cell">Date of audit</td>
        <td class="info-value">{{ $auditDate }}</td>
    </tr>
    <tr>
        <td class="label-cell">Name of the Company</td>
        <td class="info-value">{{ $details->company_name ?? '' }}</td>
    </tr>
    <tr>
        <td class="label-cell">Standard</td>
        <td class="info-value">{{ $standard }}</td>
    </tr>
    <tr>
        <td class="label-cell">Audit type</td>
        <td class="info-value">Certification</td>
    </tr>
    <tr>
        <td class="label-cell">Scope of Certification</td>
        <td class="info-value upper">{!! nl2br(e($details->scope ?? '')) !!}</td>
    </tr>
</table>

<table class="attendance-table">
    <tr>
        <th rowspan="2" class="sn-col center">S.N.</th>
        <th rowspan="2" class="center" style="width:22%;">Name</th>
        <th rowspan="2" class="center" style="width:18%;">Position</th>
        <th rowspan="2" class="center" style="width:18%;">Department</th>
        <th colspan="2" class="center">Signature</th>
    </tr>
    <tr>
        <th class="center" style="width:18%;">Opening Meeting</th>
        <th class="center" style="width:18%;">Closing Meeting</th>
    </tr>
    @for ($i = 1; $i <= 15; $i++)
        <tr>
            <td class="center">{{ $i }}</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
    @endfor
</table>
</body>
</html>
