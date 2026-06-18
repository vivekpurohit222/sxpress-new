@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Monthly Cash Book</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('accounting.cashbook.daily') }}">Cash Book</a></li><li class="active">Monthly</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    <div class="card-header">
        <strong>Monthly Cash Summary — {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</strong>
        <div class="float-right no-print">
            <a href="{{ route('accounting.cashbook.daily') }}" class="btn btn-outline-primary btn-sm">Daily View</a>
            <button onclick="window.print()" class="btn btn-secondary btn-sm"><i class="fa fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        {{-- Filter --}}
        <form method="GET" class="mb-3 no-print">
            <div class="row">
                <div class="col-md-3"><input type="month" name="month" value="{{ $month }}" class="form-control" onchange="this.form.submit()"></div>
                @if($branches->count())
                <div class="col-md-3">
                    <select name="branch" class="form-control" onchange="this.form.submit()"><option value="">All Branches</option>
                    @foreach($branches as $b)<option value="{{ $b }}" {{ $branch == $b ? 'selected' : '' }}>{{ $b }}</option>@endforeach
                    </select>
                </div>
                @endif
            </div>
        </form>

        {{-- Summary Cards --}}
        <div class="row mb-3">
            <div class="col-md-3"><div class="card border-secondary"><div class="card-body text-center py-2"><h5 class="mb-0">₹ {{ number_format($openingBalance, 2) }}</h5><small>Opening Balance</small></div></div></div>
            <div class="col-md-3"><div class="card border-success"><div class="card-body text-center py-2"><h5 class="mb-0 text-success">₹ {{ number_format($totalReceipts, 2) }}</h5><small>Total Receipts</small></div></div></div>
            <div class="col-md-3"><div class="card border-danger"><div class="card-body text-center py-2"><h5 class="mb-0 text-danger">₹ {{ number_format($totalPayments, 2) }}</h5><small>Total Payments</small></div></div></div>
            <div class="col-md-3"><div class="card border-primary"><div class="card-body text-center py-2"><h5 class="mb-0">₹ {{ number_format($closingBalance, 2) }}</h5><small>Closing Balance</small></div></div></div>
        </div>

        {{-- Daily Breakdown Table --}}
        <table class="table table-bordered table-sm">
            <thead class="thead-dark">
                <tr><th>Date</th><th class="text-right">Receipts (₹)</th><th class="text-right">Payments (₹)</th><th class="text-right">Running Balance (₹)</th><th>View</th></tr>
            </thead>
            <tbody>
                @forelse($days as $day)
                <tr>
                    <td><strong>{{ \Carbon\Carbon::parse($day['date'])->format('d M (D)') }}</strong></td>
                    <td class="text-right text-success">{{ $day['receipts'] > 0 ? number_format($day['receipts'], 2) : '-' }}</td>
                    <td class="text-right text-danger">{{ $day['payments'] > 0 ? number_format($day['payments'], 2) : '-' }}</td>
                    <td class="text-right font-weight-bold">{{ number_format($day['balance'], 2) }}</td>
                    <td><a href="{{ route('accounting.cashbook.daily', ['date' => $day['date'], 'branch' => $branch]) }}" class="btn btn-sm btn-outline-info">Detail</a></td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-3">No cash transactions this month. Create vouchers from the <a href="{{ route('accounting.vouchers.create', ['type' => 'receipt']) }}">Vouchers</a> page.</td></tr>
                @endforelse
            </tbody>
            @if(count($days))
            <tfoot class="font-weight-bold bg-light">
                <tr>
                    <td class="text-right">TOTAL</td>
                    <td class="text-right text-success">₹ {{ number_format($totalReceipts, 2) }}</td>
                    <td class="text-right text-danger">₹ {{ number_format($totalPayments, 2) }}</td>
                    <td class="text-right">₹ {{ number_format($closingBalance, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div></div></div></div></div>
@endsection
