@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>{{ $reportTitle }}</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('reports.index') }}">Reports</a></li><li class="active">Freight Memo</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    <div class="card-header">
        <strong>{{ $reportTitle }}</strong> <small class="text-muted ml-2">{{ $reportSubtitle }}</small>
        <div class="float-right no-print">
            <a href="{{ route('reports.freight', array_merge(request()->all(), ['export' => 'csv'])) }}" class="btn btn-success btn-sm"><i class="fa fa-download"></i> CSV</a>
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
                    <a href="{{ route('reports.freight') }}" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </div>
        </form>

        <!-- Summary -->
        <div class="row mb-3">
            <div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body text-center py-2"><h4 class="mb-0">{{ $totals['count'] }}</h4><small>Freight Memos</small></div></div></div>
            <div class="col-md-3"><div class="card bg-info text-white"><div class="card-body text-center py-2"><h4 class="mb-0">₹{{ number_format($totals['truck_freight'], 0) }}</h4><small>Total Truck Freight</small></div></div></div>
            <div class="col-md-3"><div class="card bg-warning text-dark"><div class="card-body text-center py-2"><h4 class="mb-0">₹{{ number_format($totals['commission'], 0) }}</h4><small>Total Commission</small></div></div></div>
            <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body text-center py-2"><h4 class="mb-0">₹{{ number_format($totals['balance_due'], 0) }}</h4><small>Total Balance Paid</small></div></div></div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="thead-dark">
                    <tr>
                        <th>FM No</th>
                        <th>Date</th>
                        <th>Truck</th>
                        <th>Route</th>
                        <th class="text-right">Truck Freight</th>
                        <th class="text-right">Commission</th>
                        <th class="text-right">Other</th>
                        <th class="text-right">Balance Due</th>
                        <th>Office</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($memos as $memo)
                    <tr>
                        <td><strong>{{ $memo->fm_no ?: $memo->memo_no }}</strong></td>
                        <td>{{ optional($memo->fm_date ?? $memo->memo_date)->format('d-m-Y') }}</td>
                        <td>{{ $memo->truck_no ?? '-' }}</td>
                        <td>{{ $memo->from_dest ?? '' }} → {{ $memo->to_dest ?? '' }}</td>
                        <td class="text-right">₹{{ number_format($memo->truck_freight ?? 0, 0) }}</td>
                        <td class="text-right">₹{{ number_format($memo->commission ?? 0, 0) }}</td>
                        <td class="text-right">₹{{ number_format($memo->other_charges ?? 0, 0) }}</td>
                        <td class="text-right"><strong>₹{{ number_format($memo->balance_due ?? 0, 0) }}</strong></td>
                        <td>{{ $memo->office }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-3">No freight memos for selected period.</td></tr>
                    @endforelse
                </tbody>
                @if($memos->count())
                <tfoot class="font-weight-bold bg-light">
                    <tr>
                        <td colspan="4" class="text-right">TOTAL</td>
                        <td class="text-right">₹{{ number_format($totals['truck_freight'], 0) }}</td>
                        <td class="text-right">₹{{ number_format($totals['commission'], 0) }}</td>
                        <td class="text-right">₹{{ number_format($totals['other_charges'], 0) }}</td>
                        <td class="text-right">₹{{ number_format($totals['balance_due'], 0) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div></div></div></div></div>
@endsection
