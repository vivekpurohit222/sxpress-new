@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Vehicle List</h3>
                <div class="card-tools">
                    <a href="{{ url('/vehicle/create') }}" class="btn btn-success btn-sm">Add New</a>
                </div>
            </div>
            @if (session('success'))
            <div class="alert alert-success m-3">
                {{ session('success') }}
            </div>
            @endif
            <div class="card-body">
                <form method="GET" action="{{ url('/vehicle') }}" class="mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search vehicle..." value="{{ request('search') }}">
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
                            <th>Vehicle Number</th>
                            <th>Type</th>
                            <th>Capacity</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vehicles as $vehicle)
                        <tr>
                            <td>{{ $vehicle->id }}</td>
                            <td>{{ $vehicle->vehicle_number }}</td>
                            <td>{{ $vehicle->vehicle_type ?? '-' }}</td>
                            <td>{{ $vehicle->capacity }} {{ $vehicle->capacity_unit }}</td>
                            <td>{{ $vehicle->owner_name ?? '-' }}</td>
                            <td>
                                @if($vehicle->status == 'active')
                                <span class="badge badge-success">Active</span>
                                @elseif($vehicle->status == 'inactive')
                                <span class="badge badge-secondary">Inactive</span>
                                @else
                                <span class="badge badge-warning">Maintenance</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ url('/vehicle/'.$vehicle->id.'/edit') }}" class="btn btn-info btn-sm">Edit</a>
                                <a href="{{ url('/vehicle/'.$vehicle->id) }}" class="btn btn-secondary btn-sm">View</a>
                                <form method="POST" action="{{ url('/vehicle/'.$vehicle->id) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No vehicles found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3">
                    {{ $vehicles->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
