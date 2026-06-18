<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $voucher->voucher_no }}</title>
    <style>
        @page { margin: 15mm; }
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 16px; }
        .header p { margin: 2px 0; font-size: 10px; }
        .title { text-align: center; font-size: 14px; font-weight: bold; border: 2px solid #333; padding: 6px; margin: 10px 0; text-transform: uppercase; }
        .info { width: 100%; margin-bottom: 10px; }
        .info td { padding: 4px 8px; }
        .info .label { font-weight: bold; width: 20%; }
        .entries { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .entries th, .entries td { border: 1px solid #333; padding: 6px 10px; }
        .entries th { background: #f0f0f0; }
        .entries .total td { font-weight: bold; background: #f9f9f9; }
        .amount-words { margin: 10px 0; padding: 8px; border: 1px solid #ccc; font-style: italic; }
        .signatures { display: flex; justify-content: space-between; margin-top: 50px; }
        .sig-block { text-align: center; width: 30%; border-top: 1px solid #333; padding-top: 5px; font-size: 11px; }
        @media print { body { print-color-adjust: exact; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>SAURASHTRA EXPRESS</h1>
        <p>H.O.: 2- Patel Nagar, Bhoja Bhagat Street, 50ft Ring Road, Rajkot - 360002</p>
        <p>GSTIN: 24AFSPJ7382P1ZI | Contact: 097279 00008</p>
    </div>

    <div class="title">{{ \App\Models\Accounting\Voucher::getTypeLabel($voucher->voucher_type) }}</div>

    <table class="info">
        <tr>
            <td class="label">Voucher No:</td><td><strong>{{ $voucher->voucher_no }}</strong></td>
            <td class="label">Date:</td><td>{{ $voucher->voucher_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Branch:</td><td>{{ $voucher->branch }}</td>
            <td class="label">Status:</td><td>{{ ucfirst($voucher->status) }}</td>
        </tr>
    </table>

    <div class="amount-words">
        <strong>Narration:</strong> {{ $voucher->narration }}
    </div>

    <table class="entries">
        <thead>
            <tr><th style="width:10%">Code</th><th>Particulars</th><th style="width:18%">Debit (₹)</th><th style="width:18%">Credit (₹)</th></tr>
        </thead>
        <tbody>
            @foreach($voucher->entries as $entry)
            <tr>
                <td>{{ $entry->account->code }}</td>
                <td>{{ $entry->account->name }}</td>
                <td style="text-align:right">{{ $entry->debit > 0 ? number_format($entry->debit, 2) : '' }}</td>
                <td style="text-align:right">{{ $entry->credit > 0 ? number_format($entry->credit, 2) : '' }}</td>
            </tr>
            @endforeach
            <tr class="total">
                <td colspan="2" style="text-align:right">Total</td>
                <td style="text-align:right">₹ {{ number_format($voucher->entries->sum('debit'), 2) }}</td>
                <td style="text-align:right">₹ {{ number_format($voucher->entries->sum('credit'), 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="amount-words">
        <strong>Amount:</strong> ₹ {{ number_format($voucher->total_amount, 2) }}
    </div>

    <div class="signatures">
        <div class="sig-block">Prepared By</div>
        <div class="sig-block">Checked By</div>
        <div class="sig-block">Authorized By</div>
    </div>

    <script>window.print();</script>
</body>
</html>
