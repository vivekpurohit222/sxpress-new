@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4">
        <div class="page-header float-left">
            <div class="page-title"><h1>Import Challan</h1></div>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="page-header float-right">
            <div class="page-title">
                <ol class="breadcrumb text-right">
                    <li><a href="{{ url('/dash') }}">Dashboard</a></li>
                    <li class="active">Import Challan</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content mt-3">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show m-3">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                    @endif

                    @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show m-3">
                        {{ $errors->first() }}
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                    @endif

                    <div class="card-header">
                        <strong class="card-title">Challans In Transit (Awaiting Import)</strong>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('import_challan.index') }}" class="mb-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="text" name="search" class="form-control" placeholder="Search challan no, origin..." value="{{ request('search') }}">
                                </div>
                                <div class="col-md-2">
                                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                                </div>
                                <div class="col-md-2">
                                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('import_challan.index') }}" class="btn btn-secondary">Reset</a>
                                </div>
                            </div>
                        </form>

                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Challan No.</th>
                                    <th>Date</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Vehicle</th>
                                    <th>Items</th>
                                    <th>Weight</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $row)
                                <tr>
                                    <td><strong>{{ $row->challan_no }}</strong></td>
                                    <td>{{ $row->challan_date ? $row->challan_date->format('d-m-Y') : '-' }}</td>
                                    <td>{{ $row->from_dest }}</td>
                                    <td>{{ $row->to_dest }}</td>
                                    <td>{{ $row->vehicle ? $row->vehicle->vehicle_number : ($row->truck_no ?? '-') }}</td>
                                    <td>{{ $row->items_count ?? '-' }}</td>
                                    <td>{{ number_format($row->total_weight ?? 0, 2) }} kg</td>
                                    <td><span class="badge badge-warning">In Transit</span></td>
                                    <td>
                                        <form action="{{ route('import_challan.import', $row->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Import this challan? GRs will be marked as In Transit at your office.')">
                                                <i class="fa fa-download"></i> Import
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="9" class="text-center">No challans awaiting import</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-3">{{ $items->withQueryString()->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
