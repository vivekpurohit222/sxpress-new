@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Recovery Report</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('accounting.outstanding.index') }}">Outstanding</a></li><li class="active">Recovery</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    <div class="card-header">
        <strong>Recovery Report</strong>
        <div class="float-right no-print"><button onclick="window.print()" class="btn btn-secondary btn-sm"><i class="fa fa-print"></i></button></div>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-3 no-print">
            <div class="row">
                <div class="col-md-3"><label>From</label><input type="date" name="from_date" value="{{ $fromDate }}" class="form-control"></div>
                <div class="col-md-3"><label>To</label><input type="date" name="to_date" value="{{ $toDate }}" class="form-control"></div>
                @if($branches->count())
                <div class="col-md-3"><label>Branch</label>
                    <select name="branch" class="form-control"><option value="">All</option>
                    @foreach($branches as $b)<option value="{{ $b }}" {{ $branch == $b ? 'selected' : '' }}>{{ $b }}</option>@endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3" style="padding-top:24px"><button type="submit" class="btn btn-primary btn-sm">Filter</button></div>
            </div>
        </form>

        <div class="alert alert-success py-2 mb-3">
            <strong>Total Recovered:</strong> ₹ {{ number_format($totalRecovered, 2) }} in this period
        </div>

        <table class="table table-bordered table-sm">
            <thead class="thead-dark"><tr><th>Ref</th><th>Party</th><th>Type</th><th class="text-right">Invoice</th><th class="text-right">Recovered</th><th class="text-right">Still Pending</th><th>Status</th><th>Branch</th></tr></thead>
            <tbody>
                @forelse($recoveries as $item)
                <tr>
                    <td><strong>{{ $item->invoice_ref }}</strong></td>
                    <td>{{ Str::limit($item->party_name, 25) }}</td>
                    <td><span class="badge badge-{{ $item->type == 'receivable' ? 'success' : 'danger' }}">{{ ucfirst($item->type) }}</span></td>
                    <td class="text-right">₹{{ number_format($item->total_amount, 0) }}</td>
                    <td class="text-right text-success font-weight-bold">₹{{ number_format($item->paid_amount, 0) }}</td>
                    <td class="text-right">₹{{ number_format($item->pending_amount, 0) }}</td>
                    <td><span class="badge badge-{{ $item->status == 'paid' ? 'success' : 'info' }}">{{ ucfirst($item->status) }}</span></td>
                    <td>{{ $item->branch }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-3">No recoveries in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div></div></div></div></div>
@endsection
