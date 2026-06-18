@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Route Details</h3>
                <div class="card-tools">
                    <a href="{{ url('/route/'.$route->id.'/edit') }}" class="btn btn-primary btn-sm">Edit</a>
                    <a href="{{ url('/route') }}" class="btn btn-default btn-sm">Back</a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Route Name</th>
                        <td>{{ $route->route_name }}</td>
                    </tr>
                    <tr>
                        <th>Route Code</th>
                        <td>{{ $route->route_code }}</td>
                    </tr>
                    <tr>
                        <th>Origin Station</th>
                        <td>{{ $route->originStation ? $route->originStation->station_name : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Destination Station</th>
                        <td>{{ $route->destinationStation ? $route->destinationStation->station_name : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Distance</th>
                        <td>{{ $route->distance_km }} km</td>
                    </tr>
                    <tr>
                        <th>Duration</th>
                        <td>{{ $route->duration_hours }} hours</td>
                    </tr>
                    <tr>
                        <th>Base Freight</th>
                        <td>{{ $route->base_freight }}</td>
                    </tr>
                    <tr>
                        <th>Via Locations</th>
                        <td>{{ $route->via_locations ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($route->status)
                            <span class="badge badge-success">Active</span>
                            @else
                            <span class="badge badge-secondary">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Created At</th>
                        <td>{{ $route->created_at->format('d-m-Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
