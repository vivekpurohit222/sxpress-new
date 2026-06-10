<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Challan - {{ $challan->challan_no }}</title>
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
        .doc-title { text-align: center; font-size: 16px; font-weight: bold; margin: 5px 0; text-decoration: underline; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info-table td { padding: 4px 8px; }
        .info-table .label { font-weight: bold; width: 25%; }
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th, .items-table td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        .items-table th { background: #f0f0f0; text-align: center; }
        .items-table td { text-align: center; }
        .items-table td:nth-child(3) { text-align: left; }
        .total-row td { font-weight: bold; background: #f9f9f9; }
        .footer { margin-top: 30px; }
        .signature-line { border-top: 1px solid #333; margin-top: 40px; padding-top: 5px; text-align: center; width: 30%; margin-left: auto; margin-right: 0; }
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

    <div class="doc-title">DELIVERY CHALLAN</div>

    <table class="info-table">
        <tr>
            <td class="label">Challan No:</td>
            <td><strong>{{ $challan->challan_no }}</strong></td>
            <td class="label">Date:</td>
            <td>{{ $challan->challan_date ? \Carbon\Carbon::parse($challan->challan_date)->format('d/m/Y') : date('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">From:</td>
            <td>{{ $challan->from_dest ?? '-' }}</td>
            <td class="label">To:</td>
            <td>{{ $challan->to_dest ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Vehicle:</td>
            <td>{{ $challan->vehicle ? $challan->vehicle->vehicle_number : ($challan->truck_no ?? '-') }}</td>
            <td class="label">Driver:</td>
            <td>{{ $challan->driver ? $challan->driver->driver_name : ($challan->driver_name ?? '-') }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Sr.</th>
                <th>GR No.</th>
                <th>Description</th>
                <th>Pkgs</th>
                <th>Weight (kg)</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($challan->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->gr_no }}</td>
                <td>{{ Str::limit($item->description, 40) }}</td>
                <td>{{ $item->nugs ?? '-' }}</td>
                <td>{{ number_format($item->weight ?? 0, 2) }}</td>
                <td>{{ $item->remarks ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">No items</td>
            </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="3">Total</td>
                <td>{{ $challan->items->sum('nugs') }}</td>
                <td>{{ number_format($challan->total_weight ?? $challan->items->sum('weight'), 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div class="signature-line">Authorized Signature</div>
    </div>

    <script>window.print();</script>
</body>
</html>