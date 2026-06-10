@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>{{ $reportTitle }}</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('reports.index') }}">Reports</a></li><li class="active">Revenue</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    <div class="card-header">
        <strong>{{ $reportTitle }}</strong> <small class="text-muted ml-2">{{ $reportSubtitle }}</small>
        <div class="float-right no-print">
            <a href="{{ route('reports.revenue', array_merge(request()->all(), ['export' => 'csv'])) }}" class="btn btn-success btn-sm"><i class="fa fa-download"></i> CSV</a>
            <button onclick="window.print()" class="btn btn-secondary btn-sm"><i class="fa fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="get" class="mb-3 no-print">
            <div class="row">
                <div class="col-md-3"><label>From</label><input type="date" name="from_date" value="{{ $fromDate }}" class="form-control"></div>
                <div class="col-md-3"><label>To</label><input type="date" name="to_date" value="{{ $toDate }}" class="form-control"></div>
                @if($branches->count())
                <div class="col-md-3"><label>Branch</label>
                    <select name="branch" class="form-control"><option value="">All</option>
                    @foreach($branches as $b)<option value="{{ $b }}" {{ request('branch') == $b ? 'selected' : '' }}>{{ $b }}</option>@endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3" style="padding-top:24px">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ route('reports.revenue') }}" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </div>
        </form>

        <!-- Summary -->
        <div class="row mb-3">
            <div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body text-center py-2"><h4 class="mb-0">{{ $totals['gr_count'] }}</h4><small>Total GRs</small></div></div></div>
            <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body text-center py-2"><h4 class="mb-0">₹{{ number_format($totals['total'], 0) }}</h4><small>Total Revenue</small></div></div></div>
            <div class="col-md-3"><div class="card bg-info text-white"><div class="card-body text-center py-2"><h4 class="mb-0">₹{{ number_format($totals['collected'], 0) }}</h4><small>Collected</small></div></div></div>
            <div class="col-md-3"><div class="card bg-warning text-dark"><div class="card-body text-center py-2"><h4 class="mb-0">₹{{ number_format($totals['pending'], 0) }}</h4><small>Pending</small></div></div></div>
        </div>

        <!-- Monthly Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="thead-dark">
                    <tr><th>Month</th><th class="text-right">GR Count</th><th class="text-right">Freight</th><th class="text-right">Total Revenue</th><th class="text-right">Paid</th><th class="text-right">Pending</th></tr>
                </thead>
                <tbody>
                    @forelse($monthly as $m)
                    <tr>
                        <td><strong>{{ Carbon\Carbon::create($m->year, $m->month)->format('F Y') }}</strong></td>
                        <td class="text-right">{{ $m->gr_count }}</td>
                        <td class="text-right">₹{{ number_format($m->freight_total ?? 0, 0) }}</td>
                        <td class="text-right">₹{{ number_format($m->total_revenue ?? 0, 0) }}</td>
                        <td class="text-right">₹{{ number_format($m->paid_total ?? 0, 0) }}</td>
                        <td class="text-right text-danger">₹{{ number_format($m->pending_total ?? 0, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">No data for selected period. Try a wider date range.</td></tr>
                    @endforelse
                </tbody>
                @if($monthly->count())
                <tfoot class="font-weight-bold bg-light">
                    <tr>
                        <td>TOTAL</td>
                        <td class="text-right">{{ $totals['gr_count'] }}</td>
                        <td class="text-right">₹{{ number_format($totals['freight'], 0) }}</td>
                        <td class="text-right">₹{{ number_format($totals['total'], 0) }}</td>
                        <td class="text-right">₹{{ number_format($totals['collected'], 0) }}</td>
                        <td class="text-right text-danger">₹{{ number_format($totals['pending'], 0) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div></div></div></div></div>
@endsection
