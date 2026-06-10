<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GR Print - {{ $copy->gr_no }}</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }
        .page-break {
            page-break-after: always;
            height: 100vh;
        }
        .gr-document {
            border: 2px solid #000;
            border-radius: 8px;
            padding: 15px;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .header-section {
            text-align: center;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .company-address {
            font-size: 11px;
            margin: 5px 0;
        }
        .jurisdiction-gst {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            margin-top: 5px;
        }
        .copy-label {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 5px 0;
            padding: 3px;
            border: 1px solid #000;
        }
        .route-info {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        .route-info span {
            font-weight: bold;
        }
        .party-info {
            border: 1px solid #000;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
        }
        .party-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .party-info td {
            padding: 3px 0;
            vertical-align: top;
        }
        .party-info .label {
            font-weight: bold;
            width: 100px;
        }
        .charges-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .charges-table th,
        .charges-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        .charges-table th {
            background: #f0f0f0;
        }
        .charges-table .amount-col {
            text-align: right;
            width: 100px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px;
            background: #f9f9f9;
            border: 1px solid #000;
            border-radius: 4px;
        }
        .summary-row .label {
            font-weight: bold;
        }
        .terms {
            font-size: 9px;
            margin-top: auto;
            padding-top: 10px;
            border-top: 1px solid #ccc;
        }
        .terms p {
            margin: 3px 0;
        }
        .signature-section {
            text-align: right;
            padding-top: 20px;
        }
        .gr-number {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            padding: 5px;
            background: #e0e0e0;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .payment-badge {
            display: inline-block;
            padding: 3px 10px;
            border: 2px solid #000;
            border-radius: 4px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .ewb-field {
            background: #fffde0;
            padding: 5px;
            border: 1px solid #000;
            border-radius: 4px;
            margin-top: 5px;
        }
    </style>
</head>
<body>

{{-- COPY 1: ORIGINAL (For Consignee) --}}
<div class="gr-document">
    <div class="header-section">
        <div class="company-name">SAURASHTRA EXPRESS</div>
        <div class="company-address">
            H.O.: 2- Patel Nagar, Bhoja Bhagat Street, 50ft Ring Road, Rajkot - 360002 (Gujarat)<br>
            Contact: 097279 00008, 93750 88088
        </div>
        <div class="jurisdiction-gst">
            <span>Subject to Rajkot Jurisdiction</span>
            <span>GST No.: 24AFSPJ7382P1ZI</span>
        </div>
    </div>

    <div class="copy-label">ORIGINAL (For Consignee)</div>

    <div class="gr-number">GR No: {{ $copy->gr_no }} &nbsp;&nbsp;|&nbsp;&nbsp; Date: {{ $copy->copy_date }}</div>

    <div class="route-info">
        <div>From: <span>{{ $copy->from_dest }}</span></div>
        <div>To: <span>{{ $copy->to_dest }}</span></div>
    </div>

    <div class="party-info">
        <table>
            <tr>
                <td class="label">Consignor:</td>
                <td><strong>{{ $copy->consignor }}</strong></td>
                <td class="label">GST No:</td>
                <td>{{ $copy->consignor_gst_no ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Address:</td>
                <td colspan="3">{{ $copy->consignor_address ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Consignee:</td>
                <td><strong>{{ $copy->consignee }}</strong></td>
                <td class="label">GST No:</td>
                <td>{{ $copy->consignee_gst_no ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Address:</td>
                <td colspan="3">{{ $copy->consignee_address ?: 'N/A' }}</td>
            </tr>
        </table>
    </div>

    @if($copy->eway_bill_number)
    <div class="ewb-field">
        <strong>E-Way Bill No:</strong> {{ $copy->eway_bill_number }}
    </div>
    @endif

    <table class="charges-table">
        <thead>
            <tr>
                <th style="width:15%">Packages</th>
                <th style="width:45%">Description of Goods</th>
                <th style="width:20%">Particulars</th>
                <th style="width:20%" class="amount-col">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td rowspan="6" style="text-align:center; vertical-align:middle;">
                    <strong>{{ $copy->nugs ?: '-' }}</strong><br>
                    <small>{{ $copy->meth ?: '-' }}</small>
                </td>
                <td rowspan="6" style="vertical-align:top;">
                    {{ $copy->description ?: 'N/A' }}
                </td>
                <td>Freight</td>
                <td class="amount-col">{{ number_format($copy->frieght_amount ?: 0, 2) }}</td>
            </tr>
            <tr>
                <td>Sur. Ch.</td>
                <td class="amount-col">{{ number_format($copy->sur_ch ?: 0, 2) }}</td>
            </tr>
            <tr>
                <td>C/R</td>
                <td class="amount-col">{{ number_format($copy->c_r ?: 0, 2) }}</td>
            </tr>
            <tr>
                <td>Other</td>
                <td class="amount-col">{{ number_format($copy->other ?: 0, 2) }}</td>
            </tr>
            <tr>
                <td>BC (Booking Charge)</td>
                <td class="amount-col">{{ number_format($copy->bc_amount ?: 0, 2) }}</td>
            </tr>
            <tr style="background:#f0f0f0;">
                <td><strong>TOTAL</strong></td>
                <td class="amount-col"><strong>{{ number_format($copy->total_amount ?: 0, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="summary-row">
        <div>
            <span class="label">Bill Amount:</span> ₹{{ number_format($copy->bill_amount ?: 0, 2) }} |
            <span class="label">Weight:</span> {{ $copy->weight ?: '-' }} kg |
            <span class="label">PM:</span> {{ $copy->pm ?: '-' }}
        </div>
        <div>
            @if($copy->paid == 1)
                <span class="payment-badge" style="background:#d4edda;">PAID ✓</span>
            @elseif($copy->to_pay == 1)
                <span class="payment-badge" style="background:#fff3cd;">TO-PAY ✓</span>
            @endif
        </div>
    </div>

    <div class="terms">
        <strong>Terms & Conditions:</strong>
        <p>(1) We are not responsible for goods after 6 months of booking date. (2) Goods will not be delivered without Consignee copy. (3) We are not responsible for damage, shortage or theft in transit. (4) The receipt without date will be cancelled.</p>
    </div>

    <div class="signature-section">
        <p>For {{ $copy->from_dest }} Office</p>
        <br><br>
        <p>Authorised Signatory</p>
    </div>
</div>

{{-- COPY 2: DUPLICATE (For Consignor) --}}
<div class="page-break"></div>
<div class="gr-document">
    <div class="header-section">
        <div class="company-name">SAURASHTRA EXPRESS</div>
        <div class="company-address">
            H.O.: 2- Patel Nagar, Bhoja Bhagat Street, 50ft Ring Road, Rajkot - 360002 (Gujarat)<br>
            Contact: 097279 00008, 93750 88088
        </div>
        <div class="jurisdiction-gst">
            <span>Subject to Rajkot Jurisdiction</span>
            <span>GST No.: 24AFSPJ7382P1ZI</span>
        </div>
    </div>

    <div class="copy-label">DUPLICATE (For Consignor)</div>

    <div class="gr-number">GR No: {{ $copy->gr_no }} &nbsp;&nbsp;|&nbsp;&nbsp; Date: {{ $copy->copy_date }}</div>

    <div class="route-info">
        <div>From: <span>{{ $copy->from_dest }}</span></div>
        <div>To: <span>{{ $copy->to_dest }}</span></div>
    </div>

    <div class="party-info">
        <table>
            <tr>
                <td class="label">Consignor:</td>
                <td><strong>{{ $copy->consignor }}</strong></td>
                <td class="label">GST No:</td>
                <td>{{ $copy->consignor_gst_no ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Address:</td>
                <td colspan="3">{{ $copy->consignor_address ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Consignee:</td>
                <td><strong>{{ $copy->consignee }}</strong></td>
                <td class="label">GST No:</td>
                <td>{{ $copy->consignee_gst_no ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Address:</td>
                <td colspan="3">{{ $copy->consignee_address ?: 'N/A' }}</td>
            </tr>
        </table>
    </div>

    @if($copy->eway_bill_number)
    <div class="ewb-field">
        <strong>E-Way Bill No:</strong> {{ $copy->eway_bill_number }}
    </div>
    @endif

    <table class="charges-table">
        <thead>
            <tr>
                <th style="width:15%">Packages</th>
                <th style="width:45%">Description of Goods</th>
                <th style="width:20%">Particulars</th>
                <th style="width:20%" class="amount-col">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td rowspan="6" style="text-align:center; vertical-align:middle;">
                    <strong>{{ $copy->nugs ?: '-' }}</strong><br>
                    <small>{{ $copy->meth ?: '-' }}</small>
                </td>
                <td rowspan="6" style="vertical-align:top;">
                    {{ $copy->description ?: 'N/A' }}
                </td>
                <td>Freight</td>
                <td class="amount-col">{{ number_format($copy->frieght_amount ?: 0, 2) }}</td>
            </tr>
            <tr>
                <td>Sur. Ch.</td>
                <td class="amount-col">{{ number_format($copy->sur_ch ?: 0, 2) }}</td>
            </tr>
            <tr>
                <td>C/R</td>
                <td class="amount-col">{{ number_format($copy->c_r ?: 0, 2) }}</td>
            </tr>
            <tr>
                <td>Other</td>
                <td class="amount-col">{{ number_format($copy->other ?: 0, 2) }}</td>
            </tr>
            <tr>
                <td>BC (Booking Charge)</td>
                <td class="amount-col">{{ number_format($copy->bc_amount ?: 0, 2) }}</td>
            </tr>
            <tr style="background:#f0f0f0;">
                <td><strong>TOTAL</strong></td>
                <td class="amount-col"><strong>{{ number_format($copy->total_amount ?: 0, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="summary-row">
        <div>
            <span class="label">Bill Amount:</span> ₹{{ number_format($copy->bill_amount ?: 0, 2) }} |
            <span class="label">Weight:</span> {{ $copy->weight ?: '-' }} kg |
            <span class="label">PM:</span> {{ $copy->pm ?: '-' }}
        </div>
        <div>
            @if($copy->paid == 1)
                <span class="payment-badge" style="background:#d4edda;">PAID ✓</span>
            @elseif($copy->to_pay == 1)
                <span class="payment-badge" style="background:#fff3cd;">TO-PAY ✓</span>
            @endif
        </div>
    </div>

    <div class="terms">
        <strong>Terms & Conditions:</strong>
        <p>(1) We are not responsible for goods after 6 months of booking date. (2) Goods will not be delivered without Consignee copy. (3) We are not responsible for damage, shortage or theft in transit. (4) The receipt without date will be cancelled.</p>
    </div>

    <div class="signature-section">
        <p>For {{ $copy->from_dest }} Office</p>
        <br><br>
        <p>Authorised Signatory</p>
    </div>
</div>

{{-- COPY 3: TRIPLICATE (For Transporter) --}}
<div class="page-break"></div>
<div class="gr-document">
    <div class="header-section">
        <div class="company-name">SAURASHTRA EXPRESS</div>
        <div class="company-address">
            H.O.: 2- Patel Nagar, Bhoja Bhagat Street, 50ft Ring Road, Rajkot - 360002 (Gujarat)<br>
            Contact: 097279 00008, 93750 88088
        </div>
        <div class="jurisdiction-gst">
            <span>Subject to Rajkot Jurisdiction</span>
            <span>GST No.: 24AFSPJ7382P1ZI</span>
        </div>
    </div>

    <div class="copy-label">TRIPLICATE (For Transporter)</div>

    <div class="gr-number">GR No: {{ $copy->gr_no }} &nbsp;&nbsp;|&nbsp;&nbsp; Date: {{ $copy->copy_date }}</div>

    <div class="route-info">
        <div>From: <span>{{ $copy->from_dest }}</span></div>
        <div>To: <span>{{ $copy->to_dest }}</span></div>
    </div>

    <div class="party-info">
        <table>
            <tr>
                <td class="label">Consignor:</td>
                <td><strong>{{ $copy->consignor }}</strong></td>
                <td class="label">GST No:</td>
                <td>{{ $copy->consignor_gst_no ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Address:</td>
                <td colspan="3">{{ $copy->consignor_address ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Consignee:</td>
                <td><strong>{{ $copy->consignee }}</strong></td>
                <td class="label">GST No:</td>
                <td>{{ $copy->consignee_gst_no ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Address:</td>
                <td colspan="3">{{ $copy->consignee_address ?: 'N/A' }}</td>
            </tr>
        </table>
    </div>

    @if($copy->eway_bill_number)
    <div class="ewb-field">
        <strong>E-Way Bill No:</strong> {{ $copy->eway_bill_number }}
    </div>
    @endif

    <table class="charges-table">
        <thead>
            <tr>
                <th style="width:15%">Packages</th>
                <th style="width:45%">Description of Goods</th>
                <th style="width:20%">Particulars</th>
                <th style="width:20%" class="amount-col">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td rowspan="6" style="text-align:center; vertical-align:middle;">
                    <strong>{{ $copy->nugs ?: '-' }}</strong><br>
                    <small>{{ $copy->meth ?: '-' }}</small>
                </td>
                <td rowspan="6" style="vertical-align:top;">
                    {{ $copy->description ?: 'N/A' }}
                </td>
                <td>Freight</td>
                <td class="amount-col">{{ number_format($copy->frieght_amount ?: 0, 2) }}</td>
            </tr>
            <tr>
                <td>Sur. Ch.</td>
                <td class="amount-col">{{ number_format($copy->sur_ch ?: 0, 2) }}</td>
            </tr>
            <tr>
                <td>C/R</td>
                <td class="amount-col">{{ number_format($copy->c_r ?: 0, 2) }}</td>
            </tr>
            <tr>
                <td>Other</td>
                <td class="amount-col">{{ number_format($copy->other ?: 0, 2) }}</td>
            </tr>
            <tr>
                <td>BC (Booking Charge)</td>
                <td class="amount-col">{{ number_format($copy->bc_amount ?: 0, 2) }}</td>
            </tr>
            <tr style="background:#f0f0f0;">
                <td><strong>TOTAL</strong></td>
                <td class="amount-col"><strong>{{ number_format($copy->total_amount ?: 0, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="summary-row">
        <div>
            <span class="label">Bill Amount:</span> ₹{{ number_format($copy->bill_amount ?: 0, 2) }} |
            <span class="label">Weight:</span> {{ $copy->weight ?: '-' }} kg |
            <span class="label">PM:</span> {{ $copy->pm ?: '-' }}
        </div>
        <div>
            @if($copy->paid == 1)
                <span class="payment-badge" style="background:#d4edda;">PAID ✓</span>
            @elseif($copy->to_pay == 1)
                <span class="payment-badge" style="background:#fff3cd;">TO-PAY ✓</span>
            @endif
        </div>
    </div>

    <div class="terms">
        <strong>Terms & Conditions:</strong>
        <p>(1) We are not responsible for goods after 6 months of booking date. (2) Goods will not be delivered without Consignee copy. (3) We are not responsible for damage, shortage or theft in transit. (4) The receipt without date will be cancelled.</p>
    </div>

    <div class="signature-section">
        <p>For {{ $copy->from_dest }} Office</p>
        <br><br>
        <p>Authorised Signatory</p>
    </div>
</div>

</body>
</html>