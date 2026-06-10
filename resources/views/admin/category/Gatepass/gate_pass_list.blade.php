@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4">
        <div class="page-header float-left">
            <div class="page-title">
                <h1>{{ $gatepass_list_page }}</h1>
            </div>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="page-header float-right">
            <div class="page-title">
                <ol class="breadcrumb text-right">
                    <li><a href="{{ url('/dash') }}">Dashboard</a></li>
                    <li class="active">Gate Pass</li>
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
                    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                        <span class="badge badge-pill badge-success">Success</span> {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                    @endif

                    @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                    @endif

                    <div class="card-header">
                        <strong class="card-title">Gate Pass List</strong>
                        <a href="{{ route('gatepass.create') }}" class="btn btn-primary pull-right">
                            <i class="fa fa-plus"></i> Create Gate Pass
                        </a>
                    </div>
                    <div class="card-body">
                        {{-- Filters --}}
                        <form method="GET" action="{{ route('gatepass.index') }}" class="mb-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="text" name="search" class="form-control" placeholder="Search GP No, destination..." value="{{ request('search') }}">
                                </div>
                                <div class="col-md-2">
                                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}" placeholder="From date">
                                </div>
                                <div class="col-md-2">
                                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}" placeholder="To date">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('gatepass.index') }}" class="btn btn-secondary">Reset</a>
                                </div>
                            </div>
                        </form>

                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>GP No.</th>
                                    <th>Date</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>GRs</th>
                                    <th>Vehicle</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $row)
                                <tr>
                                    <td><strong>{{ $row->gp_no }}</strong></td>
                                    <td>{{ $row->gp_date ? $row->gp_date->format('d-m-Y') : '-' }}</td>
                                    <td>{{ $row->from_dest }}</td>
                                    <td>{{ $row->to_dest }}</td>
                                    <td>
                                        @if($row->grs && $row->grs->count())
                                            <span class="badge badge-info">{{ $row->grs->count() }} GR(s)</span>
                                        @elseif($row->gr_no)
                                            {{ $row->gr_no }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $row->vehicle ? $row->vehicle->vehicle_number : ($row->vehicle_no ?? '-') }}</td>
                                    <td>
                                        <a href="{{ route('gatepass.show', $row->id) }}" class="btn btn-secondary btn-sm" title="View"><i class="fa fa-eye"></i></a>
                                        <a href="{{ route('gatepass.print', $row->id) }}" class="btn btn-warning btn-sm" title="Print"><i class="fa fa-print"></i></a>
                                        @hasanyrole('SuperAdmin|Admin|Manager')
                                        <a href="{{ route('gatepass.edit', $row->id) }}" class="btn btn-primary btn-sm" title="Edit"><i class="fa fa-edit"></i></a>
                                        @endhasanyrole
                                        @hasanyrole('SuperAdmin|Admin')
                                        <form action="{{ route('gatepass.destroy', $row->id) }}" method="POST" style="display:inline;">
                                            @method('DELETE')
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Delete this gatepass?')"><i class="fa fa-trash"></i></button>
                                        </form>
                                        @endhasanyrole
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">No gatepasses found</td>
                                </tr>
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
