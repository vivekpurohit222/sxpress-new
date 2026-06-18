@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Outstanding</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li class="active">Outstanding</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show m-3">{{ session('success') }}<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>@endif

    <div class="card-header">
        <strong>Outstanding Management</strong>
        <div class="float-right">
            <a href="{{ route('accounting.outstanding.ageing') }}" class="btn btn-warning btn-sm">Ageing Report</a>
            <a href="{{ route('accounting.outstanding.recovery') }}" class="btn btn-info btn-sm">Recovery Report</a>
            @role('SuperAdmin')
            <form action="{{ route('accounting.outstanding.sync') }}" method="POST" style="display:inline">@csrf
                <button type="submit" class="btn btn-outline-success btn-sm" onclick="return confirm('Sync from GR & Freight Memo data?')"><i class="fa fa-refresh"></i> Sync from Operations</button>
            </form>
            @endrole
        </div>
    </div>
    <div class="card-body">
        {{-- Summary Cards --}}
        <div class="row mb-3">
            <div class="col-md-3"><div class="card border-success"><div class="card-body text-center py-2"><h5 class="mb-0 text-success">₹ {{ number_format($summary['total_receivable'], 0) }}</h5><small>Total Receivable</small></div></div></div>
            <div class="col-md-3"><div class="card border-danger"><div class="card-body text-center py-2"><h5 class="mb-0 text-danger">₹ {{ number_format($summary['total_payable'], 0) }}</h5><small>Total Payable</small></div></div></div>
            <div class="col-md-3"><div class="card border-warning"><div class="card-body text-center py-2"><h5 class="mb-0 text-warning">{{ $summary['overdue_count'] }}</h5><small>Overdue Items</small></div></div></div>
            <div class="col-md-3"><div class="card border-danger"><div class="card-body text-center py-2"><h5 class="mb-0 text-danger">₹ {{ number_format($summary['total_overdue'], 0) }}</h5><small>Overdue Amount</small></div></div></div>
        </div>

        {{-- Filters --}}
        <form method="GET" class="mb-3 no-print">
            <div class="row">
                <div class="col-md-2">
                    <select name="type" class="form-control form-control-sm"><option value="">All Types</option>
                    <option value="receivable" {{ $type == 'receivable' ? 'selected' : '' }}>Receivable</option>
                    <option value="payable" {{ $type == 'payable' ? 'selected' : '' }}>Payable</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="party_type" class="form-control form-control-sm"><option value="">All Parties</option>
                    <option value="customer" {{ $partyType == 'customer' ? 'selected' : '' }}>Customer</option>
                    <option value="consignor" {{ $partyType == 'consignor' ? 'selected' : '' }}>Consignor</option>
                    <option value="consignee" {{ $partyType == 'consignee' ? 'selected' : '' }}>Consignee</option>
                    <option value="truck_owner" {{ $partyType == 'truck_owner' ? 'selected' : '' }}>Truck Owner</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control form-control-sm"><option value="">All Status</option>
                    <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="partial" {{ $status == 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="overdue" {{ $status == 'overdue' ? 'selected' : '' }}>Overdue</option>
                    <option value="paid" {{ $status == 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>
                @if($branches->count())
                <div class="col-md-2">
                    <select name="branch" class="form-control form-control-sm"><option value="">All Branches</option>
                    @foreach($branches as $b)<option value="{{ $b }}" {{ $branch == $b ? 'selected' : '' }}>{{ $b }}</option>@endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ route('accounting.outstanding.index') }}" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </div>
        </form>

        {{-- Table --}}
        <table class="table table-bordered table-sm">
            <thead class="thead-dark">
                <tr><th>Ref</th><th>Date</th><th>Party</th><th>Type</th><th class="text-right">Total</th><th class="text-right">Paid</th><th class="text-right">Pending</th><th>Age</th><th>Status</th><th style="width:100px">Action</th></tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr class="{{ $item->status == 'overdue' ? 'table-danger' : ($item->status == 'paid' ? 'text-muted' : '') }}">
                    <td><strong>{{ $item->invoice_ref }}</strong></td>
                    <td>{{ $item->invoice_date->format('d-m-Y') }}</td>
                    <td>{{ Str::limit($item->party_name, 25) }}<br><small class="text-muted">{{ ucfirst(str_replace('_',' ',$item->party_type)) }}</small></td>
                    <td><span class="badge badge-{{ $item->type == 'receivable' ? 'success' : 'danger' }}">{{ ucfirst($item->type) }}</span></td>
                    <td class="text-right">₹{{ number_format($item->total_amount, 0) }}</td>
                    <td class="text-right">₹{{ number_format($item->paid_amount, 0) }}</td>
                    <td class="text-right font-weight-bold">₹{{ number_format($item->pending_amount, 0) }}</td>
                    <td><span class="badge badge-{{ $item->age_days > 30 ? 'danger' : ($item->age_days > 15 ? 'warning' : 'secondary') }}">{{ $item->age_days }}d</span></td>
                    <td><span class="badge badge-{{ match($item->status) { 'paid'=>'success','overdue'=>'danger','partial'=>'info',default=>'warning' } }}">{{ ucfirst($item->status) }}</span></td>
                    <td>
                        @if($item->status !== 'paid')
                        <form action="{{ route('accounting.outstanding.payment', $item->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Record payment?')">
                            @csrf
                            <div class="input-group input-group-sm" style="width:120px">
                                <input type="number" name="amount" class="form-control" placeholder="₹" min="0.01" max="{{ $item->pending_amount }}" step="0.01" required style="width:70px">
                                <div class="input-group-append"><button type="submit" class="btn btn-success btn-sm">Pay</button></div>
                            </div>
                        </form>
                        @else
                        <span class="text-success"><i class="fa fa-check"></i></span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center text-muted py-3">No outstanding entries. Click "Sync from Operations" to pull data from GRs and Freight Memos.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-2">{{ $items->withQueryString()->links() }}</div>
    </div>
</div></div></div></div></div>
@endsection
