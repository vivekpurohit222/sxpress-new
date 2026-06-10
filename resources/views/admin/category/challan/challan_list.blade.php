@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4">
        <div class="page-header float-left">
            <div class="page-title"><h1>{{ $challan_list_page }}</h1></div>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="page-header float-right">
            <div class="page-title">
                <ol class="breadcrumb text-right">
                    <li><a href="{{ url('/dash') }}">Dashboard</a></li>
                    <li class="active">Challan</li>
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

                    <div class="card-header">
                        <strong class="card-title">Challan List</strong>
                        <a href="{{ route('challan.create') }}" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> Create Challan</a>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('challan.index') }}" class="mb-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="text" name="search" class="form-control" placeholder="Search challan no, destination..." value="{{ request('search') }}">
                                </div>
                                <div class="col-md-2">
                                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                                </div>
                                <div class="col-md-2">
                                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('challan.index') }}" class="btn btn-secondary">Reset</a>
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
                                    <th>Actions</th>
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
                                    <td>{{ $row->items_count ?? $row->items->count() ?? '-' }}</td>
                                    <td>{{ number_format($row->total_weight ?? 0, 2) }} kg</td>
                                    <td>
                                        <a href="{{ route('challan.show', $row->id) }}" class="btn btn-secondary btn-sm"><i class="fa fa-eye"></i></a>
                                        <a href="{{ route('challan.print', $row->id) }}" class="btn btn-warning btn-sm"><i class="fa fa-print"></i></a>
                                        @hasanyrole('SuperAdmin|Admin|Manager')
                                        <a href="{{ route('challan.edit', $row->id) }}" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i></a>
                                        @endhasanyrole
                                        @hasanyrole('SuperAdmin|Admin')
                                        <form action="{{ route('challan.destroy', $row->id) }}" method="POST" style="display:inline;">
                                            @method('DELETE') @csrf
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this challan and all items?')"><i class="fa fa-trash"></i></button>
                                        </form>
                                        @endhasanyrole
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="8" class="text-center">No challans found</td></tr>
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
