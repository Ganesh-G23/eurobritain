<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 8mm 10mm 8mm 10mm; }
        body { font-family: serif; font-size: 9.5px; color: #000; margin: 0; padding: 0; line-height: 1.45; text-align: justify; }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; }
        .bordered td { border: 1px solid #000; padding: 6px 8px; }
        .no-border td, .no-border th { border: none; padding: 0; }
        .center { text-align: center; }
        .header-title { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px; }
        .form-title { font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .section-title { font-weight: bold; margin: 6px 0 3px; font-size: 9px; }
        .clause { margin: 0 0 3px; }
        .sub-clause { margin: 0 0 2px 14px; }
        .sig-cell { padding: 6px 8px; height: 110px; }
        .sig-cell .lbl { margin-top: 2px; }
    </style>
</head>
<body>

<table class="no-border" style="margin-bottom:2px;">
    <tr>
        <td class="no-border center" colspan="3">
            @if ($logoPath)
                <img src="{{ $logoPath }}" style="max-height:48px;" alt="Logo">
            @endif
        </td>
    </tr>
    <tr>
        <td class="no-border center" colspan="3" style="border-top:2px solid #000 !important; padding-top:4px;">
            <span class="header-title">{{ $brand['full'] }}</span>
        </td>
    </tr>
</table>

@php
    $applicationDate = $details->created_at ? $details->created_at->format('d.m.Y') : '';
@endphp

<table class="no-border" style="margin-bottom:6px;">
    <tr>
        <td class="no-border" style="width:30%;">
            <strong>F01 Issue 01 Rev 00</strong><br>
            ({{ $applicationDate }})
        </td>
        <td class="no-border center form-title" style="width:40%; vertical-align:middle;">CLIENT AGREEMENT</td>
        <td class="no-border" style="width:30%;">&nbsp;</td>
    </tr>
</table>

<p class="section-title">1. Rights and Duties of {{ $brand['short'] }}</p>

<p class="clause"><strong>4.1</strong> The ownership for Logo or mark, Certification documents, Audit reports etc lies with {{ $brand['short'] }} and in any situation the second party make any incorrect reference to the certification status or misleading the use of certification documents, Mark or logo or audit reports then {{ $brand['short'] }} will take the following steps</p>

<p class="sub-clause"><strong>4.1.1</strong> Prima Facie {{ $brand['short'] }} will request to the second party for correction and corrective action.</p>
<p class="sub-clause"><strong>4.1.2</strong> In case second party is not taking the corrective action then {{ $brand['short'] }} shall suspend and Withdrawal of certification.</p>
<p class="sub-clause"><strong>4.1.3</strong> In case still Second party is not taking action then a notice of Infringement of Intellectual property shall be given and Legal action shall be taken against the second party</p>

<p class="clause"><strong>4.2</strong> {{ $brand['short'] }} shall not disclosed information about a particular certified client or individual to a third party without the written consent of the certified client or individual concerned Except as required in this part of ISO/IEC 17021.</p>

<p class="clause"><strong>4.3</strong> When the {{ $brand['short'] }} is required by law or authorized by contractual arrangements (such as with the accreditation body) to release confidential information, the client or individual concerned shall, unless prohibited by law, be notified of the information provided.</p>

<p class="clause"><strong>4.4</strong> The certification body shall have processes and where applicable equipment and facilities that ensure the secure handling of confidential information</p>

<p class="clause"><strong>4.5</strong> Any information about the client (eg complaint, Notice or feedback) received by {{ $brand['short'] }} from the any person other than client like complainant/Regulators/Statutory bodies or any other person shall be treated confidential and cant not be disclosed to client. All other information, except for information that is made publicly accessible by the client, will be considered confidential by {{ $brand['short'] }}</p>

<p class="clause"><strong>4.6</strong> when there is any change in the requirement of the certification then {{ $brand['short'] }} will send a notice to client company intimating the new requirement or change. The client has to Comply to notice of any changes to its requirements for certification and verification of compliance with the new requirements</p>

<p class="clause"><strong>4.7</strong> {{ $brand['short'] }} shall provide information of client's, address standard and scope in public domain.</p>

<p class="clause"><strong>4.8</strong> Information provided by the {{ $brand['short'] }} to any client or to the marketplace, including advertising, shall be accurate and not misleading.</p>

<p class="clause"><strong>4.9</strong> {{ $brand['short'] }} shall provide a detailed description of the initial and continuing certification activity, including the application, initial audits, surveillance audits, and the process for granting, refusing, maintaining of certification, expanding or reducing the scope of certification, renewing, suspending or restoring, or withdrawing of certification which is available on the website of the company i.e {{ $brand['website'] }}</p>

<p class="clause"><strong>4.10</strong> the normative requirements for certification; if required form time to time</p>

<p class="clause"><strong>4.11</strong> information about the fees for application, initial certification and continuing certification in the form of the quotation or work order.</p>

<p class="clause"><strong>4.12</strong> when there is any change in the requirement of the certification then {{ $brand['short'] }} will send a notice to client company intimating the new requirement or change. The client has to Comply to notice of any changes to its requirements for certification and verification of compliance</p>

<p class="clause"><strong>4.13</strong> {{ $brand['short'] }} can conduct audits of certified clients at short notice or unannounced audit to investigate complaints after ensuring that it belongs to {{ $brand['short'] }}, or in response to changes (Legal status, Organisation and management, address and sites, scope, major changes to management system and processes, fatal accidents or a legal action by any regulatory authority OR as follow up on suspended clients. The client Company cant refuse or reject or make any objection for the Auditor or the Audit Team in case of short notice Audit.</p>

<p class="section-title">5. Liability:</p>

<p class="clause"><strong>5.1</strong> Except, in the case of deliberate neglect on the part of {{ $brand['short'] }}, its employees, servants or agents, {{ $brand['short'] }} shall not be liable for any loss or damage sustained by any person due to any act of omission or error whatsoever or howsoever caused during the performance of its assessment, certification or other services.</p>

<p class="clause">{{ $brand['short'] }} shall not be liable in any respect, should it be prevented from discharging such obligations as a result of any matter beyond its control which could not be reasonably foreseen.</p>

<p class="section-title">7. Disputes:</p>

<p class="clause">In case of the dispute arise between the parties then it shall be settled by appointment of the sole arbitrator as the {{ $brand['law'] }}. Aggrieved party can challenge the award of arbitrator with 30 days of the award but the Jurisdiction area shall be {{ $brand['jurisdiction'] }} only and the case can be filed in the competent court of {{ $brand['jurisdiction'] }} only.</p>

<table class="bordered" style="margin-top:6px;">
    <tr>
        <td class="sig-cell" style="width:50%;">
            <strong>FOR &amp; ONBEHALF OF</strong><br><br>
            {{ $brand['left_signatory'] }}
            <br><br><br><br><br>
            <div class="lbl">Name of Signatory:</div>
            <div class="lbl">Designation:</div>
            <div class="lbl">Date:</div>
        </td>
        <td class="sig-cell" style="width:50%;">
            <strong>FOR &amp; ONBEHALF OF</strong><br><br>
            {{ $details->company_name ?? '' }}
            <br><br><br><br><br>
            <div class="lbl">Name of Signatory:</div>
            <div class="lbl">Designation:</div>
            <div class="lbl">Date:</div>
        </td>
    </tr>
</table>

</body>
</html>
