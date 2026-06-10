@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>{{ $reportTitle }}</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('reports.index') }}">Reports</a></li><li class="active">GR Register</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    <div class="card-header">
        <strong>{{ $reportTitle }}</strong> <small class="text-muted ml-2">{{ $reportSubtitle }}</small>
        <div class="float-right no-print">
            <a href="{{ route('reports.gr_register', array_merge(request()->all(), ['export' => 'csv'])) }}" class="btn btn-success btn-sm"><i class="fa fa-download"></i> CSV</a>
            <button onclick="window.print()" class="btn btn-secondary btn-sm"><i class="fa fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="get" class="mb-3 no-print">
            <div class="row">
                <div class="col-md-2"><label>From</label><input type="date" name="from_date" value="{{ $fromDate }}" class="form-control"></div>
                <div class="col-md-2"><label>To</label><input type="date" name="to_date" value="{{ $toDate }}" class="form-control"></div>
                @if($branches->count())
                <div class="col-md-2"><label>Branch</label>
                    <select name="branch" class="form-control"><option value="">All</option>
                    @foreach($branches as $b)<option value="{{ $b }}" {{ request('branch') == $b ? 'selected' : '' }}>{{ $b }}</option>@endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2"><label>Status</label>
                    <select name="status" class="form-control"><option value="">All</option>
                    @foreach(['created','dispatched','in_transit','delivered','closed','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach</select>
                </div>
                <div class="col-md-2"><label>Payment</label>
                    <select name="payment" class="form-control"><option value="">All</option>
                    <option value="paid" {{ request('payment') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="to_pay" {{ request('payment') == 'to_pay' ? 'selected' : '' }}>To-Pay</option>
                    </select>
                </div>
                <div class="col-md-2" style="padding-top:24px">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ route('reports.gr_register') }}" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </div>
        </form>

        <!-- Summary -->
        <div class="row mb-3">
            <div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body text-center py-2"><h4 class="mb-0">{{ $totals['count'] }}</h4><small>Total GRs</small></div></div></div>
            <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body text-center py-2"><h4 class="mb-0">₹{{ number_format($totals['freight'], 0) }}</h4><small>Freight</small></div></div></div>
            <div class="col-md-3"><div class="card bg-info text-white"><div class="card-body text-center py-2"><h4 class="mb-0">₹{{ number_format($totals['total'], 0) }}</h4><small>Total Amount</small></div></div></div>
            <div class="col-md-3"><div class="card bg-warning text-dark"><div class="card-body text-center py-2"><h4 class="mb-0">{{ number_format($totals['weight'], 0) }}kg</h4><small>Weight</small></div></div></div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="thead-dark">
                    <tr><th>GR No</th><th>Date</th><th>From</th><th>To</th><th>Consignor</th><th>Consignee</th><th class="text-right">Wt</th><th class="text-right">Freight</th><th class="text-right">Total</th><th>Type</th><th>Status</th><th>Office</th></tr>
                </thead>
                <tbody>
                    @forelse($grs as $gr)
                    <tr>
                        <td><strong>{{ $gr->gr_no }}</strong></td>
                        <td>{{ optional($gr->copy_date)->format('d-m-Y') }}</td>
                        <td>{{ $gr->from_dest }}</td>
                        <td>{{ $gr->to_dest }}</td>
                        <td>{{ Str::limit($gr->consignor, 15) }}</td>
                        <td>{{ Str::limit($gr->consignee, 15) }}</td>
                        <td class="text-right">{{ $gr->weight }}</td>
                        <td class="text-right">₹{{ number_format($gr->frieght_amount, 0) }}</td>
                        <td class="text-right">₹{{ number_format($gr->total_amount, 0) }}</td>
                        <td><span class="badge badge-{{ $gr->paid ? 'success' : 'warning' }}">{{ $gr->paid ? 'PAID' : 'TP' }}</span></td>
                        <td><span class="badge badge-{{ match($gr->status) { 'created' => 'secondary', 'dispatched' => 'primary', 'in_transit' => 'warning', 'delivered' => 'info', 'closed' => 'success', 'cancelled' => 'danger', default => 'light' } }}">{{ ucfirst($gr->status ?? 'created') }}</span></td>
                        <td>{{ $gr->office }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="12" class="text-center text-muted py-3">No GRs found for selected criteria.</td></tr>
                    @endforelse
                </tbody>
                @if($grs->count())
                <tfoot class="font-weight-bold bg-light">
                    <tr><td colspan="6" class="text-right">TOTAL</td><td class="text-right">{{ number_format($totals['weight'], 0) }}</td><td class="text-right">₹{{ number_format($totals['freight'], 0) }}</td><td class="text-right">₹{{ number_format($totals['total'], 0) }}</td><td colspan="3"></td></tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div></div></div></div></div>
@endsection
