@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Edit Vehicle</h3>
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
            <form method="POST" action="{{ route('vehicle.update', $vehicle->id) }}">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="vehicle_number">Vehicle Number *</label>
                                <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" value="{{ old('vehicle_number', $vehicle->vehicle_number) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="vehicle_type">Vehicle Type</label>
                                <input type="text" class="form-control" id="vehicle_type" name="vehicle_type" value="{{ old('vehicle_type', $vehicle->vehicle_type) }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="chassis_no">Chassis No</label>
                                <input type="text" class="form-control" id="chassis_no" name="chassis_no" value="{{ old('chassis_no', $vehicle->chassis_no) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="engine_no">Engine No</label>
                                <input type="text" class="form-control" id="engine_no" name="engine_no" value="{{ old('engine_no', $vehicle->engine_no) }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="capacity">Capacity</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" value="{{ old('capacity', $vehicle->capacity) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="capacity_unit">Unit</label>
                                <input type="text" class="form-control" id="capacity_unit" name="capacity_unit" value="{{ old('capacity_unit', $vehicle->capacity_unit) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active" {{ $vehicle->status == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ $vehicle->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="under_maintenance" {{ $vehicle->status == 'under_maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="insurance_date">Insurance Date</label>
                                <input type="date" class="form-control" id="insurance_date" name="insurance_date" value="{{ old('insurance_date', $vehicle->insurance_date ? $vehicle->insurance_date->format('Y-m-d') : '') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tax_date">Tax Date</label>
                                <input type="date" class="form-control" id="tax_date" name="tax_date" value="{{ old('tax_date', $vehicle->tax_date ? $vehicle->tax_date->format('Y-m-d') : '') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="permit_date">Permit Date</label>
                                <input type="date" class="form-control" id="permit_date" name="permit_date" value="{{ old('permit_date', $vehicle->permit_date ? $vehicle->permit_date->format('Y-m-d') : '') }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="owner_name">Owner Name</label>
                                <input type="text" class="form-control" id="owner_name" name="owner_name" value="{{ old('owner_name', $vehicle->owner_name) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="owner_phone">Owner Phone</label>
                                <input type="text" class="form-control" id="owner_phone" name="owner_phone" value="{{ old('owner_phone', $vehicle->owner_phone) }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-check mt-4 pt-2">
                                    <input type="checkbox" class="form-check-input" id="is_own" name="is_own" {{ $vehicle->is_own ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_own">Own Vehicle</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ url('/vehicle') }}" class="btn btn-default">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
