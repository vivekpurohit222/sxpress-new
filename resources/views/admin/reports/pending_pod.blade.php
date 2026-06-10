@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>{{ $reportTitle }}</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('reports.index') }}">Reports</a></li><li class="active">Pending POD</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    <div class="card-header">
        <strong>{{ $reportTitle }}</strong> <small class="text-muted ml-2">{{ $reportSubtitle }}</small>
        <div class="float-right no-print">
            <a href="{{ route('reports.pending_pod', array_merge(request()->all(), ['export' => 'csv'])) }}" class="btn btn-success btn-sm"><i class="fa fa-download"></i> CSV</a>
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
                    <a href="{{ route('reports.pending_pod') }}" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </div>
        </form>
        @endif

        <!-- Summary -->
        <div class="row mb-3">
            <div class="col-md-4"><div class="card bg-warning text-dark"><div class="card-body text-center py-2"><h4 class="mb-0">{{ $totals['count'] }}</h4><small>GRs Awaiting POD</small></div></div></div>
            <div class="col-md-4"><div class="card bg-info text-white"><div class="card-body text-center py-2"><h4 class="mb-0">₹{{ number_format($totals['total_amount'], 0) }}</h4><small>Total Amount</small></div></div></div>
            <div class="col-md-4"><div class="card bg-danger text-white"><div class="card-body text-center py-2"><h4 class="mb-0">{{ $grs->where('days_pending', '>', 7)->count() }}</h4><small>Overdue (&gt;7 days)</small></div></div></div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="thead-dark">
                    <tr><th>GR No</th><th>Date</th><th>From</th><th>To</th><th>Consignee</th><th class="text-right">Weight</th><th class="text-right">Amount</th><th class="text-center">Days Pending</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse($grs as $gr)
                    <tr class="{{ $gr->days_pending > 7 ? 'table-warning' : '' }}">
                        <td><strong>{{ $gr->gr_no }}</strong></td>
                        <td>{{ optional($gr->copy_date)->format('d-m-Y') }}</td>
                        <td>{{ $gr->from_dest }}</td>
                        <td>{{ $gr->to_dest }}</td>
                        <td>{{ Str::limit($gr->consignee, 20) }}</td>
                        <td class="text-right">{{ number_format($gr->weight, 2) }} kg</td>
                        <td class="text-right">₹{{ number_format($gr->total_amount, 0) }}</td>
                        <td class="text-center"><span class="badge badge-{{ $gr->days_pending > 7 ? 'danger' : ($gr->days_pending > 3 ? 'warning' : 'secondary') }}">{{ $gr->days_pending }}d</span></td>
                        <td><span class="badge badge-primary">{{ ucfirst($gr->status) }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-3">All dispatched GRs have POD uploaded. No pending items.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div></div></div></div></div>
@endsection
