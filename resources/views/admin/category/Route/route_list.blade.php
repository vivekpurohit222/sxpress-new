@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Route List</h3>
                <div class="card-tools">
                    <a href="{{ url('/dash/route/create') }}" class="btn btn-success btn-sm">Add New</a>
                </div>
            </div>
            @if (session('success'))
            <div class="alert alert-success m-3">
                {{ session('success') }}
            </div>
            @endif
            <div class="card-body">
                <form method="GET" action="{{ url('/dash/route') }}" class="mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search route..." value="{{ request('search') }}">
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
                            <th>Route Name</th>
                            <th>Code</th>
                            <th>Origin</th>
                            <th>Destination</th>
                            <th>Distance</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($routes as $route)
                        <tr>
                            <td>{{ $route->id }}</td>
                            <td>{{ $route->route_name }}</td>
                            <td>{{ $route->route_code }}</td>
                            <td>{{ $route->originStation ? $route->originStation->station_name : '-' }}</td>
                            <td>{{ $route->destinationStation ? $route->destinationStation->station_name : '-' }}</td>
                            <td>{{ $route->distance_km }} km</td>
                            <td>
                                @if($route->status)
                                <span class="badge badge-success">Active</span>
                                @else
                                <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ url('/dash/route/'.$route->id.'/edit') }}" class="btn btn-info btn-sm">Edit</a>
                                <a href="{{ url('/dash/route/'.$route->id) }}" class="btn btn-secondary btn-sm">View</a>
                                <form method="POST" action="{{ url('/dash/route/'.$route->id) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No routes found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3">
                    {{ $routes->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</section>
@endsection