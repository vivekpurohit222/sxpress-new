<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Freight Memo - {{ $freight->fm_no }}</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 8px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 2px 0; font-size: 10px; }
        .title { text-align: center; font-size: 14px; font-weight: bold; border: 1px solid #333; padding: 4px; margin: 8px 0; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.info td { padding: 3px 6px; border: 1px solid #ccc; }
        table.info th { padding: 3px 6px; border: 1px solid #ccc; background: #f5f5f5; text-align: left; width: 25%; }
        table.charges { width: 100%; border-collapse: collapse; margin: 8px 0; }
        table.charges th, table.charges td { border: 1px solid #333; padding: 5px 8px; }
        table.charges th { background: #f0f0f0; }
        .settlement { width: 50%; float: right; border: 2px solid #333; padding: 10px; margin-top: 8px; }
        .settlement td { padding: 4px 8px; }
        .settlement .total { font-weight: bold; font-size: 14px; border-top: 2px solid #333; }
        .footer { clear: both; margin-top: 40px; display: flex; justify-content: space-between; }
        .sig-block { text-align: center; width: 30%; border-top: 1px solid #333; padding-top: 5px; }
        .tc { font-size: 9px; color: #666; margin-top: 15px; clear: both; }
        @media print { body { print-color-adjust: exact; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>SAURASHTRA EXPRESS</h1>
        <p>H.O.: 2- Patel Nagar, Bhoja Bhagat Street, 50ft Ring Road, Rajkot - 360002</p>
        <p>Contact: 097279 00008, 93750 88088 | GSTIN: 24AFSPJ7382P1ZI</p>
    </div>

    <div class="title">FREIGHT MEMO / TRUCK HIRE SETTLEMENT</div>

    <table class="info">
        <tr>
            <th>F.M. No:</th><td><strong>{{ $freight->fm_no }}</strong></td>
            <th>Date:</th><td>{{ $freight->fm_date ? \Carbon\Carbon::parse($freight->fm_date)->format('d/m/Y') : '-' }}</td>
        </tr>
        <tr>
            <th>Truck No:</th><td><strong>{{ $freight->truck_no }}</strong></td>
            <th>Driver:</th><td>{{ $freight->driver_name ?? '-' }}</td>
        </tr>
        <tr>
            <th>From:</th><td>{{ $freight->from_dest }}</td>
            <th>To:</th><td>{{ $freight->to_dest }}</td>
        </tr>
    </table>

    {{-- Settlement Breakdown --}}
    <table class="charges">
        <thead>
            <tr><th width="5%">Sr.</th><th>Particulars</th><th width="25%">Amount (₹)</th></tr>
        </thead>
        <tbody>
            <tr style="background:#eef"><td></td><td><strong>Total Lorry Hire (Agreed Freight)</strong></td><td style="text-align:right"><strong>{{ number_format($freight->truck_freight ?? 0, 2) }}</strong></td></tr>
            @if(($freight->entry_1_amount ?? 0) > 0)
            <tr><td>1</td><td>Less: {{ $freight->entry_1 ?: 'Advance Paid' }}</td><td style="text-align:right">− {{ number_format($freight->entry_1_amount, 2) }}</td></tr>
            @endif
            @if(($freight->commission ?? 0) > 0)
            <tr><td>2</td><td>Less: Broker Commission</td><td style="text-align:right">− {{ number_format($freight->commission, 2) }}</td></tr>
            @endif
            @if(($freight->entry_2_amount ?? 0) > 0)
            <tr><td>3</td><td>Less: {{ $freight->entry_2 ?: 'Hamali' }}</td><td style="text-align:right">− {{ number_format($freight->entry_2_amount, 2) }}</td></tr>
            @endif
            @if(($freight->entry_3_amount ?? 0) > 0)
            <tr><td>4</td><td>Less: {{ $freight->entry_3 ?: 'Detention' }}</td><td style="text-align:right">− {{ number_format($freight->entry_3_amount, 2) }}</td></tr>
            @endif
            @if(($freight->entry_4_amount ?? 0) > 0)
            <tr><td>5</td><td>Less: {{ $freight->entry_4 ?: 'TDS' }}</td><td style="text-align:right">− {{ number_format($freight->entry_4_amount, 2) }}</td></tr>
            @endif
            @if(($freight->other_charges ?? 0) > 0)
            <tr><td>6</td><td>Less: Other Deductions</td><td style="text-align:right">− {{ number_format($freight->other_charges, 2) }}</td></tr>
            @endif
        </tbody>
    </table>

    {{-- Settlement Box --}}
    <div class="settlement">
        <table width="100%">
            <tr><td>Total Lorry Hire:</td><td style="text-align:right">₹ {{ number_format($freight->truck_freight ?? 0, 2) }}</td></tr>
            <tr><td>Total Deductions:</td><td style="text-align:right">− ₹ {{ number_format(($freight->truck_freight ?? 0) - ($freight->balance_due ?? 0), 2) }}</td></tr>
            <tr class="total"><td><strong>Balance Payable to Owner:</strong></td><td style="text-align:right"><strong>₹ {{ number_format($freight->balance_due ?? 0, 2) }}</strong></td></tr>
        </table>
    </div>

    @if($freight->note)
    <div style="clear:both; margin-top:15px;"><strong>Note:</strong> {{ $freight->note }}</div>
    @endif

    <div class="tc">
        <strong>Terms & Conditions:</strong><br>
        1. If goods not delivered within agreed time, ₹1500/- per day penalty deducted from freight.<br>
        2. Transporter not responsible for goods after 6 months of booking.<br>
        3. Subject to Rajkot jurisdiction.
    </div>

    <div class="footer">
        <div class="sig-block">Driver's Signature</div>
        <div class="sig-block">Owner's Signature</div>
        <div class="sig-block">For Saurashtra Express</div>
    </div>

    <script>window.print();</script>
</body>
</html>
