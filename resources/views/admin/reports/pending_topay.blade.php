@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>{{ $reportTitle }}</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('reports.index') }}">Reports</a></li><li class="active">Pending TO-PAY</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    <div class="card-header">
        <strong>{{ $reportTitle }}</strong> <small class="text-muted ml-2">{{ $reportSubtitle }}</small>
        <div class="float-right no-print">
            <a href="{{ route('reports.pending_topay', array_merge(request()->all(), ['export' => 'csv'])) }}" class="btn btn-success btn-sm"><i class="fa fa-download"></i> CSV</a>
            <button onclick="window.print()" class="btn btn-secondary btn-sm"><i class="fa fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        @if($branches->count())
        <form method="get" class="mb-3 no-print">
            <div class="row">
                <div class="col-md-3">
                    <select name="branch" class="form-control"><option value="">All Branches</option>
                    @foreach($branches as $b)<option value="{{ $b }}" {{ request('branch') == $b ? 'selected' : '' }}>{{ $b }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ route('reports.pending_topay') }}" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </div>
        </form>
        @endif

        <!-- Totals -->
        <div class="row mb-3">
            <div class="col-md-4"><div class="card bg-danger text-white"><div class="card-body text-center py-2"><h4 class="mb-0">{{ $totals['count'] }}</h4><small>Pending GRs</small></div></div></div>
            <div class="col-md-8"><div class="card bg-warning text-dark"><div class="card-body text-center py-2"><h4 class="mb-0">₹{{ number_format($totals['total_amount'], 0) }}</h4><small>Total Pending Amount</small></div></div></div>
        </div>

        <!-- Aging Buckets -->
        <div class="row mb-3">
            <div class="col-md-3"><div class="card border-danger"><div class="card-body text-center py-2"><h5 class="text-danger mb-0">{{ $aging['overdue_30']->count() }}</h5><small>Over 30 Days</small><div class="text-danger">₹{{ number_format($aging['overdue_30']->sum('total_amount'), 0) }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-warning"><div class="card-body text-center py-2"><h5 class="text-warning mb-0">{{ $aging['overdue_15']->count() }}</h5><small>16–30 Days</small><div>₹{{ number_format($aging['overdue_15']->sum('total_amount'), 0) }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-info"><div class="card-body text-center py-2"><h5 class="text-info mb-0">{{ $aging['overdue_7']->count() }}</h5><small>8–15 Days</small><div>₹{{ number_format($aging['overdue_7']->sum('total_amount'), 0) }}</div></div></div></div>
            <div class="col-md-3"><div class="card border-secondary"><div class="card-body text-center py-2"><h5 class="mb-0">{{ $aging['recent']->count() }}</h5><small>Within 7 Days</small><div>₹{{ number_format($aging['recent']->sum('total_amount'), 0) }}</div></div></div></div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="thead-dark">
                    <tr><th>GR No</th><th>Date</th><th>From</th><th>To</th><th>Consignee</th><th class="text-right">Amount</th><th class="text-center">Age</th><th>Office</th></tr>
                </thead>
                <tbody>
                    @forelse($grs as $gr)
                    @php $age = Carbon\Carbon::parse($gr->copy_date)->diffInDays(now()); @endphp
                    <tr class="{{ $age > 30 ? 'table-danger' : ($age > 15 ? 'table-warning' : '') }}">
                        <td><strong>{{ $gr->gr_no }}</strong></td>
                        <td>{{ optional($gr->copy_date)->format('d-m-Y') }}</td>
                        <td>{{ $gr->from_dest }}</td>
                        <td>{{ $gr->to_dest }}</td>
                        <td>{{ Str::limit($gr->consignee, 20) }}</td>
                        <td class="text-right"><strong>₹{{ number_format($gr->total_amount, 0) }}</strong></td>
                        <td class="text-center"><span class="badge badge-{{ $age > 30 ? 'danger' : ($age > 15 ? 'warning' : 'secondary') }}">{{ $age }}d</span></td>
                        <td>{{ $gr->office }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-3">All TO-PAY amounts collected. Nothing pending.</td></tr>
                    @endforelse
                </tbody>
                @if($grs->count())
                <tfoot class="font-weight-bold bg-light">
                    <tr><td colspan="5" class="text-right">TOTAL</td><td class="text-right">₹{{ number_format($totals['total_amount'], 0) }}</td><td colspan="2"></td></tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div></div></div></div></div>
@endsection
