@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Station List</h3>
                <div class="card-tools">
                    <a href="{{ url('/dash/station/create') }}" class="btn btn-success btn-sm">Add New</a>
                </div>
            </div>
            @if (session('success'))
            <div class="alert alert-success m-3">
                {{ session('success') }}
            </div>
            @endif
            <div class="card-body">
                <form method="GET" action="{{ url('/dash/station') }}" class="mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search station..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </div>
                </form>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Station Name</th>
                            <th>Code</th>
                            <th>City</th>
                            <th>Branch</th>
                            <th>Warehouse</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stations as $station)
                        <tr>
                            <td>{{ $station->id }}</td>
                            <td>{{ $station->station_name }}</td>
                            <td>{{ $station->station_code }}</td>
                            <td>{{ $station->city ?? '-' }}</td>
                            <td>{{ $station->branch ? $station->branch->branch_name : '-' }}</td>
                            <td>
                                @if($station->is_warehouse)
                                <span class="badge badge-info">Yes</span>
                                @else
                                <span class="badge badge-secondary">No</span>
                                @endif
                            </td>
                            <td>
                                @if($station->status)
                                <span class="badge badge-success">Active</span>
                                @else
                                <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ url('/dash/station/'.$station->id.'/edit') }}" class="btn btn-info btn-sm">Edit</a>
                                <a href="{{ url('/dash/station/'.$station->id) }}" class="btn btn-secondary btn-sm">View</a>
                                <form method="POST" action="{{ url('/dash/station/'.$station->id) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No stations found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3">
                    {{ $stations->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</section>
@endsection