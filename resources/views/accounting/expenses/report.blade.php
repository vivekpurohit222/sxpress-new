@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Expense Report</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('accounting.expenses.index') }}">Expenses</a></li><li class="active">Report</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    <div class="card-header">
        <strong>Expense Summary by Category</strong>
        <div class="float-right no-print"><button onclick="window.print()" class="btn btn-secondary btn-sm"><i class="fa fa-print"></i></button></div>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-3 no-print">
            <div class="row">
                <div class="col-md-3"><label>From</label><input type="date" name="from_date" value="{{ $fromDate }}" class="form-control"></div>
                <div class="col-md-3"><label>To</label><input type="date" name="to_date" value="{{ $toDate }}" class="form-control"></div>
                @if($branches->count())
                <div class="col-md-3"><label>Branch</label>
                    <select name="branch" class="form-control"><option value="">All</option>@foreach($branches as $b)<option value="{{ $b }}" {{ $branch == $b ? 'selected' : '' }}>{{ $b }}</option>@endforeach</select>
                </div>
                @endif
                <div class="col-md-3" style="padding-top:24px"><button type="submit" class="btn btn-primary btn-sm">Generate</button></div>
            </div>
        </form>

        <div class="alert alert-danger py-2 mb-3 text-center">
            <strong>Total Expenses: ₹ {{ number_format($grandTotal, 2) }}</strong>
            <small class="ml-2">({{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} — {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }})</small>
        </div>

        <table class="table table-bordered table-sm">
            <thead class="thead-dark">
                <tr><th>Expense Type</th><th class="text-right">Count</th><th class="text-right">Total (₹)</th><th class="text-right">% of Total</th></tr>
            </thead>
            <tbody>
                @forelse($byType as $row)
                <tr>
                    <td><strong>{{ \App\Models\Accounting\Expense::getTypeLabel($row->expense_type) }}</strong></td>
                    <td class="text-right">{{ $row->count }}</td>
                    <td class="text-right">₹ {{ number_format($row->total, 0) }}</td>
                    <td class="text-right">{{ $grandTotal > 0 ? round(($row->total / $grandTotal) * 100, 1) : 0 }}%</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No expenses in this period.</td></tr>
                @endforelse
            </tbody>
            @if($byType->count())
            <tfoot class="font-weight-bold bg-light">
                <tr><td>TOTAL</td><td class="text-right">{{ $byType->sum('count') }}</td><td class="text-right">₹ {{ number_format($grandTotal, 0) }}</td><td class="text-right">100%</td></tr>
            </tfoot>
            @endif
        </table>
    </div>
</div></div></div></div></div>
@endsection
