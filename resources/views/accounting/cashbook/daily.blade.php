@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Cash Book</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('accounting.cashbook.daily') }}">Cash Book</a></li><li class="active">Daily</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    <div class="card-header">
        <strong>Daily Cash Book — {{ \Carbon\Carbon::parse($date)->format('d M Y (l)') }}</strong>
        <div class="float-right no-print">
            <a href="{{ route('accounting.cashbook.monthly') }}" class="btn btn-outline-primary btn-sm">Monthly View</a>
            <button onclick="window.print()" class="btn btn-secondary btn-sm"><i class="fa fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        {{-- Filters --}}
        <form method="GET" class="mb-3 no-print">
            <div class="row">
                <div class="col-md-3"><input type="date" name="date" value="{{ $date }}" class="form-control" onchange="this.form.submit()"></div>
                @if($branches->count())
                <div class="col-md-3">
                    <select name="branch" class="form-control" onchange="this.form.submit()"><option value="">All Branches</option>
                    @foreach($branches as $b)<option value="{{ $b }}" {{ $branch == $b ? 'selected' : '' }}>{{ $b }}</option>@endforeach
                    </select>
                </div>
                @endif
            </div>
        </form>

        {{-- Opening Balance --}}
        <div class="alert alert-light py-2 mb-3">
            <strong>Opening Balance:</strong> ₹ {{ number_format($openingBalance, 2) }}
            @if($branch) <span class="badge badge-info ml-2">{{ $branch }}</span> @endif
        </div>

        <div class="row">
            {{-- LEFT: Receipts (Debit to Cash = money coming in) --}}
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-success text-white py-2"><strong>Receipts (Cash In)</strong></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light"><tr><th>Voucher</th><th>Particulars</th><th class="text-right">Amount (₹)</th></tr></thead>
                            <tbody>
                                @forelse($receipts as $entry)
                                <tr>
                                    <td><a href="{{ route('accounting.vouchers.show', $entry->voucher_id) }}">{{ $entry->voucher->voucher_no ?? '-' }}</a></td>
                                    <td>{{ $entry->narration ?: ($entry->voucher->narration ?? '-') }}</td>
                                    <td class="text-right">{{ number_format($entry->debit, 2) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted py-2">No receipts</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot class="font-weight-bold bg-light">
                                <tr><td colspan="2" class="text-right">Total Receipts</td><td class="text-right">₹ {{ number_format($totalReceipts, 2) }}</td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- RIGHT: Payments (Credit to Cash = money going out) --}}
            <div class="col-md-6">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white py-2"><strong>Payments (Cash Out)</strong></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light"><tr><th>Voucher</th><th>Particulars</th><th class="text-right">Amount (₹)</th></tr></thead>
                            <tbody>
                                @forelse($payments as $entry)
                                <tr>
                                    <td><a href="{{ route('accounting.vouchers.show', $entry->voucher_id) }}">{{ $entry->voucher->voucher_no ?? '-' }}</a></td>
                                    <td>{{ $entry->narration ?: ($entry->voucher->narration ?? '-') }}</td>
                                    <td class="text-right">{{ number_format($entry->credit, 2) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted py-2">No payments</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot class="font-weight-bold bg-light">
                                <tr><td colspan="2" class="text-right">Total Payments</td><td class="text-right">₹ {{ number_format($totalPayments, 2) }}</td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Closing Balance --}}
        <div class="alert {{ $closingBalance >= 0 ? 'alert-success' : 'alert-danger' }} mt-3">
            <div class="row">
                <div class="col-md-4"><strong>Opening:</strong> ₹ {{ number_format($openingBalance, 2) }}</div>
                <div class="col-md-4 text-center"><strong>+ Receipts:</strong> ₹ {{ number_format($totalReceipts, 2) }} &nbsp; <strong>− Payments:</strong> ₹ {{ number_format($totalPayments, 2) }}</div>
                <div class="col-md-4 text-right"><strong>Closing Balance: ₹ {{ number_format($closingBalance, 2) }}</strong></div>
            </div>
        </div>
    </div>
</div></div></div></div></div>
@endsection
