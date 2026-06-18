@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Freight Memo</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li class="active">Freight Memo</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show m-3">{{ session('success') }}<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
    @endif

    <div class="card-header">
        <strong>Truck Owner Settlements</strong>
        @hasanyrole('SuperAdmin|BranchManager')
        <a href="{{ route('frieghtmemo.create') }}" class="btn btn-success btn-sm pull-right"><i class="fa fa-plus"></i> New Freight Memo</a>
        @endhasanyrole
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('frieghtmemo.index') }}" class="mb-3">
            <div class="row">
                <div class="col-md-3"><input type="text" name="search" class="form-control" placeholder="Search FM No, Truck No..." value="{{ request('search') }}"></div>
                <div class="col-md-2"><input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}" placeholder="From date"></div>
                <div class="col-md-2"><input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}" placeholder="To date"></div>
                @if(auth()->user()->hasRole('SuperAdmin') && isset($branches))
                <div class="col-md-2">
                    <select name="branch" class="form-control">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)<option value="{{ $b }}" {{ request('branch') == $b ? 'selected' : '' }}>{{ $b }}</option>@endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ route('frieghtmemo.index') }}" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </div>
        </form>

        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>FM No</th>
                    <th>Date</th>
                    <th>Truck</th>
                    <th>Route</th>
                    <th class="text-right">Truck Freight</th>
                    <th class="text-right">Commission</th>
                    <th class="text-right">Balance Due</th>
                    <th style="width:130px">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $row)
                <tr>
                    <td><strong>{{ $row->fm_no ?: $row->memo_no }}</strong></td>
                    <td>{{ $row->fm_date ? \Carbon\Carbon::parse($row->fm_date)->format('d-m-Y') : '-' }}</td>
                    <td>{{ $row->truck_no ?: '-' }}</td>
                    <td>{{ $row->from_dest }} → {{ $row->to_dest }}</td>
                    <td class="text-right">₹{{ number_format($row->truck_freight ?? 0, 0) }}</td>
                    <td class="text-right">₹{{ number_format($row->commission ?? 0, 0) }}</td>
                    <td class="text-right"><strong class="{{ ($row->balance_due ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">₹{{ number_format($row->balance_due ?? 0, 0) }}</strong></td>
                    <td>
                        <a href="{{ route('frieghtmemo.show', $row->id) }}" class="btn btn-secondary btn-sm" title="View"><i class="fa fa-eye"></i></a>
                        <a href="{{ route('frieghtmemo.print', $row->id) }}" class="btn btn-warning btn-sm" title="Print"><i class="fa fa-print"></i></a>
                        @hasanyrole('SuperAdmin|BranchManager')
                        <a href="{{ route('frieghtmemo.edit', $row->id) }}" class="btn btn-primary btn-sm" title="Edit"><i class="fa fa-edit"></i></a>
                        <form action="{{ route('frieghtmemo.destroy', $row->id) }}" method="POST" style="display:inline;">@method('DELETE') @csrf
                            <button type="submit" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Delete this freight memo?')"><i class="fa fa-trash"></i></button>
                        </form>
                        @endhasanyrole
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted">No freight memos found. Create one from a completed challan.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-2">{{ $items->withQueryString()->links() }}</div>
    </div>
</div></div></div></div></div>
@endsection
