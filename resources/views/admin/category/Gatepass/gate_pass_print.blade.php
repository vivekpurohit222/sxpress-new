<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Gatepass - {{ $gp->gp_no }}</title>
    <style>
        @page { margin: 15mm; }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 2px 0; font-size: 10px; }
        .doc-title { text-align: right; font-size: 14px; font-weight: bold; margin: 5px 0; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info-table td { padding: 4px 8px; vertical-align: top; }
        .info-table .label { font-weight: bold; width: 30%; }
        .gr-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .gr-table th, .gr-table td { border: 1px solid #333; padding: 5px 8px; text-align: left; }
        .gr-table th { background: #f0f0f0; }
        .footer { margin-top: 30px; display: flex; justify-content: space-between; }
        .signature-block { text-align: center; width: 30%; }
        .signature-line { border-top: 1px solid #333; margin-top: 30px; padding-top: 5px; }
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SAURASHTRA EXPRESS</h1>
        <p>H.O.: 2- Patel Nagar, Bhoja Bhagat Street, 50ft Ring Road, Rajkot - 360002</p>
        <p>Contact: 097279 00008, 93750 88088 | GSTIN: 24AFSPJ7382P1ZI</p>
    </div>

    <div class="doc-title">GATEPASS</div>

    <table class="info-table">
        <tr>
            <td class="label">Gatepass No:</td>
            <td><strong>{{ $gp->gp_no }}</strong></td>
            <td class="label">Date:</td>
            <td>{{ $gp->gp_date ? \Carbon\Carbon::parse($gp->gp_date)->format('d/m/Y') : date('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">From Station:</td>
            <td>{{ $gp->from_dest ?? '-' }}</td>
            <td class="label">To Station:</td>
            <td>{{ $gp->to_dest ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Vehicle:</td>
            <td>{{ $gp->vehicle ? $gp->vehicle->vehicle_number : ($gp->vehicle_no ?? '-') }}</td>
            <td class="label">Driver:</td>
            <td>{{ $gp->driver ? $gp->driver->driver_name : ($gp->driver_name ?? '-') }}</td>
        </tr>
        @if($gp->remarks)
        <tr>
            <td class="label">Remarks:</td>
            <td colspan="3">{{ $gp->remarks }}</td>
        </tr>
        @endif
    </table>

    <strong>Linked GRs:</strong>
    <table class="gr-table">
        <thead>
            <tr>
                <th>Sr.</th>
                <th>GR No.</th>
                <th>Consignor</th>
                <th>Consignee</th>
                <th>From</th>
                <th>To</th>
            </tr>
        </thead>
        <tbody>
            @forelse($gp->grs as $i => $gr)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $gr->gr_no }}</td>
                <td>{{ Str::limit($gr->consignor, 20) }}</td>
                <td>{{ Str::limit($gr->consignee, 20) }}</td>
                <td>{{ $gr->from_dest }}</td>
                <td>{{ $gr->to_dest }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">No linked GRs</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div class="signature-block">
            <div class="signature-line">Authorized Signature</div>
        </div>
        <div class="signature-block">
            <div class="signature-line">Driver Signature</div>
        </div>
        <div class="signature-block">
            <div class="signature-line">Office Copy</div>
        </div>
    </div>

    <script>window.print();</script>
</body>
</html>