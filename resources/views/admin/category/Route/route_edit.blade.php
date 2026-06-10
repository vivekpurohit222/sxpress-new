@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Edit Route</h3>
            </div>
            @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            <form method="POST" action="{{ route('route.update', $route->id) }}">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="route_name">Route Name *</label>
                                <input type="text" class="form-control" id="route_name" name="route_name" value="{{ old('route_name', $route->route_name) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="route_code">Route Code *</label>
                                <input type="text" class="form-control" id="route_code" name="route_code" value="{{ old('route_code', $route->route_code) }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="origin_station_id">Origin Station</label>
                                <select class="form-control" id="origin_station_id" name="origin_station_id">
                                    <option value="">-- Select Origin --</option>
                                    @foreach($stations as $station)
                                    <option value="{{ $station->id }}" {{ old('origin_station_id', $route->origin_station_id) == $station->id ? 'selected' : '' }}>{{ $station->station_name }} ({{ $station->station_code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="destination_station_id">Destination Station</label>
                                <select class="form-control" id="destination_station_id" name="destination_station_id">
                                    <option value="">-- Select Destination --</option>
                                    @foreach($stations as $station)
                                    <option value="{{ $station->id }}" {{ old('destination_station_id', $route->destination_station_id) == $station->id ? 'selected' : '' }}>{{ $station->station_name }} ({{ $station->station_code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="distance_km">Distance (KM)</label>
                                <input type="number" step="0.01" class="form-control" id="distance_km" name="distance_km" value="{{ old('distance_km', $route->distance_km) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="duration_hours">Duration (Hours)</label>
                                <input type="number" step="0.01" class="form-control" id="duration_hours" name="duration_hours" value="{{ old('duration_hours', $route->duration_hours) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="base_freight">Base Freight</label>
                                <input type="number" step="0.01" class="form-control" id="base_freight" name="base_freight" value="{{ old('base_freight', $route->base_freight) }}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="via_locations">Via Locations</label>
                        <textarea class="form-control" id="via_locations" name="via_locations" rows="2">{{ old('via_locations', $route->via_locations) }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-check mt-4 pt-2">
                                    <input type="checkbox" class="form-check-input" id="status" name="status" {{ $route->status ? 'checked' : '' }}>
                                    <label class="form-check-label" for="status">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ url('/dash/route') }}" class="btn btn-default">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection