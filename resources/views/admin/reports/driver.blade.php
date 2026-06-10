@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>{{ $reportTitle }}</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('reports.index') }}">Reports</a></li><li class="active">Driver</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    <div class="card-header">
        <strong>{{ $reportTitle }}</strong>
        <div class="float-right no-print">
            <a href="{{ route('reports.driver', array_merge(request()->all(), ['export' => 'csv'])) }}" class="btn btn-success btn-sm"><i class="fa fa-download"></i> CSV</a>
            <button onclick="window.print()" class="btn btn-secondary btn-sm"><i class="fa fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <form method="get" class="mb-3 no-print">
            <div class="row">
                <div class="col-md-3">
                    <select name="status" class="form-control"><option value="">All Status</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3"><button type="submit" class="btn btn-primary btn-sm">Filter</button></div>
            </div>
        </form>

        <div class="row mb-3">
            <div class="col-md-4"><div class="card bg-primary text-white"><div class="card-body text-center py-2"><h4 class="mb-0">{{ $totals['total'] }}</h4><small>Total</small></div></div></div>
            <div class="col-md-4"><div class="card bg-success text-white"><div class="card-body text-center py-2"><h4 class="mb-0">{{ $totals['active'] }}</h4><small>Active</small></div></div></div>
            <div class="col-md-4"><div class="card bg-secondary text-white"><div class="card-body text-center py-2"><h4 class="mb-0">{{ $totals['inactive'] }}</h4><small>Inactive</small></div></div></div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="thead-dark">
                    <tr><th>#</th><th>Driver Name</th><th>Truck No</th><th>License</th><th>Mobile</th><th>Address</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse($drivers as $d)
                    <tr class="{{ !$d->status ? 'text-muted' : '' }}">
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $d->driver_name }}</strong></td>
                        <td>{{ $d->truck_no ?? '-' }}</td>
                        <td>{{ $d->license ?? '-' }}</td>
                        <td>{{ $d->mobile_no1 ?? '-' }}</td>
                        <td>{{ Str::limit($d->driver_address ?? '', 25) }}</td>
                        <td><span class="badge badge-{{ $d->status ? 'success' : 'secondary' }}">{{ $d->status ? 'Active' : 'Inactive' }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">No drivers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div></div></div></div></div>
@endsection
