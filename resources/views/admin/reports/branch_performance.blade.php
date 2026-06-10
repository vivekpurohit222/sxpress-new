@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>{{ $reportTitle }}</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('reports.index') }}">Reports</a></li><li class="active">Branch Performance</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    <div class="card-header">
        <strong>{{ $reportTitle }}</strong> <small class="text-muted ml-2">{{ $reportSubtitle }}</small>
        <div class="float-right no-print">
            <a href="{{ route('reports.branch_performance', array_merge(request()->all(), ['export' => 'csv'])) }}" class="btn btn-success btn-sm"><i class="fa fa-download"></i> CSV</a>
            <button onclick="window.print()" class="btn btn-secondary btn-sm"><i class="fa fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <!-- Date Filter -->
        <form method="get" class="mb-3 no-print">
            <div class="row">
                <div class="col-md-3"><label>From</label><input type="date" name="from_date" value="{{ $fromDate }}" class="form-control"></div>
                <div class="col-md-3"><label>To</label><input type="date" name="to_date" value="{{ $toDate }}" class="form-control"></div>
                <div class="col-md-3" style="padding-top:24px">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ route('reports.branch_performance') }}" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="thead-dark">
                    <tr>
                        <th>Branch</th>
                        <th class="text-right">Total GRs</th>
                        <th class="text-right">Dispatched</th>
                        <th class="text-right">Delivered</th>
                        <th class="text-right">Freight (₹)</th>
                        <th class="text-right">Revenue (₹)</th>
                        <th class="text-center">Delivery %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branchStats as $branch)
                    <tr>
                        <td><strong>{{ $branch->branch_name }}</strong></td>
                        <td class="text-right">{{ $branch->total_grs ?? 0 }}</td>
                        <td class="text-right">{{ $branch->dispatched_grs ?? 0 }}</td>
                        <td class="text-right">{{ $branch->delivered_grs ?? 0 }}</td>
                        <td class="text-right">₹{{ number_format($branch->total_freight_sum ?? 0, 0) }}</td>
                        <td class="text-right">₹{{ number_format($branch->total_revenue_sum ?? 0, 0) }}</td>
                        <td class="text-center">
                            @if(($branch->total_grs ?? 0) > 0)
                                @php $pct = round(($branch->delivered_grs / $branch->total_grs) * 100, 1); @endphp
                                <span class="badge badge-{{ $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') }}">{{ $pct }}%</span>
                            @else
                                <span class="badge badge-secondary">N/A</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">No branch data available for selected period.</td></tr>
                    @endforelse
                </tbody>
                @if($branchStats->count())
                <tfoot class="font-weight-bold bg-light">
                    <tr>
                        <td>TOTAL</td>
                        <td class="text-right">{{ $branchStats->sum('total_grs') }}</td>
                        <td class="text-right">{{ $branchStats->sum('dispatched_grs') }}</td>
                        <td class="text-right">{{ $branchStats->sum('delivered_grs') }}</td>
                        <td class="text-right">₹{{ number_format($branchStats->sum('total_freight_sum'), 0) }}</td>
                        <td class="text-right">₹{{ number_format($branchStats->sum('total_revenue_sum'), 0) }}</td>
                        <td class="text-center">
                            @if($branchStats->sum('total_grs') > 0)
                                {{ round(($branchStats->sum('delivered_grs') / $branchStats->sum('total_grs')) * 100, 1) }}%
                            @endif
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div></div></div></div></div>
@endsection
