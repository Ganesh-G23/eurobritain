<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: serif; font-size: 11px; color: #000; margin: 0; padding: 0; }
        table { border-collapse: collapse; width: 100%; }
        .th-label { background-color: #f0f0f0; font-weight: bold; text-align: left; vertical-align: top; }
        td, th { border: 1px solid #000; padding: 5px; vertical-align: top; }
        .no-border { border: none !important; }
        .title-header { font-size: 18px; font-weight: bold; text-align: right; vertical-align: middle; }
        .text-right { text-align: right; }
        .section-title { font-weight: bold; font-size: 12px; margin: 12px 0 6px; }
        .footer-table td { border: none; font-size: 10px; padding: 2px 4px; }
    </style>
</head>
<body>
@php
    $associate = $details->associate;
    $client = $details->client;
@endphp

<table class="no-border" style="width:100%; margin-bottom:12px;">
    <tr>
        <td class="no-border" style="width:45%; vertical-align:middle;">
            @if ($logoPath)
                <img src="{{ $logoPath }}" style="max-height:60px;" alt="EUROBRITAIN">
            @endif
        </td>
        <td class="no-border title-header" style="width:55%;">INVOICE</td>
    </tr>
</table>

<table style="margin-bottom:12px;">
    <tr>
        <td class="th-label" style="width:25%;">Invoice Number</td>
        <td style="width:25%;">{{ $details->invoice_number }}</td>
        <td class="th-label" style="width:25%;">Invoice Date</td>
        <td style="width:25%;">{{ $details->invoice_date?->format('d M Y') ?? '' }}</td>
    </tr>
</table>

<div class="section-title">Bill From</div>
<table style="margin-bottom:12px;">
    <tr>
        <td class="th-label" style="width:30%;">Company</td>
        <td>{{ $associate->company_name ?? '' }}</td>
    </tr>
    <tr>
        <td class="th-label">Contact</td>
        <td>{{ $associate->contact_person ?? '' }}</td>
    </tr>
    <tr>
        <td class="th-label">Email / Phone</td>
        <td>{{ trim(($associate->contact_email ?? '') . ($associate->contact_mobile ? ' / ' . $associate->contact_mobile : '')) }}</td>
    </tr>
    <tr>
        <td class="th-label">Address</td>
        <td>{{ trim(($associate->address ?? '') . ($associate->city ? ', ' . $associate->city : '')) }}</td>
    </tr>
</table>

<div class="section-title">Bill To</div>
<table style="margin-bottom:12px;">
    <tr>
        <td class="th-label" style="width:30%;">Company</td>
        <td>{{ $client->company_name ?? '' }}</td>
    </tr>
    <tr>
        <td class="th-label">Contact</td>
        <td>{{ $client->contact_person ?? '' }}</td>
    </tr>
    <tr>
        <td class="th-label">Email / Phone</td>
        <td>{{ trim(($client->contact_email ?? '') . ($client->contact_mobile ? ' / ' . $client->contact_mobile : '')) }}</td>
    </tr>
    <tr>
        <td class="th-label">Address</td>
        <td>
            {{ trim(
                ($client->address ?? '') .
                ($client->city ? ', ' . $client->city : '') .
                ($client->state?->name ? ', ' . $client->state->name : '') .
                ($client->country?->name ? ', ' . $client->country->name : '')
            ) }}
        </td>
    </tr>
</table>

<div class="section-title">Certificates</div>
<table>
    <thead>
        <tr>
            <th style="width:8%;">Sr No.</th>
            <th style="width:32%;">Certificate Number</th>
            <th style="width:40%;">Certificate Type</th>
            <th style="width:20%;" class="text-right">Amount</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($certificates as $index => $cert)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $cert->certificate_number }}</td>
                <td>{{ $cert->certificateType->description ?? $cert->certificateType->code ?? '—' }}</td>
                <td class="text-right">{{ number_format((float) $cert->amount, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align:center;">No certificates</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <th colspan="3" class="text-right">Total Amount</th>
            <th class="text-right">{{ number_format((float) $details->total_amount, 2) }}</th>
        <!-- </tr>
        <tr>
            <th colspan="3" class="text-right">Paid Amount</th>
            <th class="text-right">{{ number_format((float) $details->paid_amount, 2) }}</th>
        </tr>
        <tr>
            <th colspan="3" class="text-right">Pending Amount</th>
            <th class="text-right">{{ number_format((float) $details->pending_amount, 2) }}</th> -->
        </tr>
    </tfoot>
</table>

<!-- @if ($details->admin_note)
    <div class="section-title">Note</div>
    <p style="font-size:10px;">{{ $details->admin_note }}</p>
@endif -->

<table class="footer-table" style="width:100%; margin-top:12px;">
    <tr>
        <td style="width:40%; text-align:left;"><strong>Address:</strong> 63/66 Hatton Garden, Fifth Floor Suite 23, London, ECIN 8LE, UK</td>
        <td style="width:30%; text-align:center;"><strong>Email:</strong> info@eurobritain.co.uk</td>
        <td style="width:30%; text-align:right;"><strong>Website:</strong> www.eurobritain.co.uk</td>
    </tr>
</table>
</body>
</html>
