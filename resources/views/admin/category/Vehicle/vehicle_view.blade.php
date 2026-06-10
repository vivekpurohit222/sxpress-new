@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Vehicle Details</h3>
                <div class="card-tools">
                    <a href="{{ url('/dash/vehicle/'.$vehicle->id.'/edit') }}" class="btn btn-primary btn-sm">Edit</a>
                    <a href="{{ url('/dash/vehicle') }}" class="btn btn-default btn-sm">Back</a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Vehicle Number</th>
                        <td>{{ $vehicle->vehicle_number }}</td>
                    </tr>
                    <tr>
                        <th>Vehicle Type</th>
                        <td>{{ $vehicle->vehicle_type ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Chassis No</th>
                        <td>{{ $vehicle->chassis_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Engine No</th>
                        <td>{{ $vehicle->engine_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Capacity</th>
                        <td>{{ $vehicle->capacity }} {{ $vehicle->capacity_unit }}</td>
                    </tr>
                    <tr>
                        <th>Owner Name</th>
                        <td>{{ $vehicle->owner_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Owner Phone</th>
                        <td>{{ $vehicle->owner_phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Insurance Date</th>
                        <td>{{ $vehicle->insurance_date ? $vehicle->insurance_date->format('d-m-Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Tax Date</th>
                        <td>{{ $vehicle->tax_date ? $vehicle->tax_date->format('d-m-Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Permit Date</th>
                        <td>{{ $vehicle->permit_date ? $vehicle->permit_date->format('d-m-Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Own Vehicle</th>
                        <td>{{ $vehicle->is_own ? 'Yes' : 'No' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($vehicle->status == 'active')
                            <span class="badge badge-success">Active</span>
                            @elseif($vehicle->status == 'inactive')
                            <span class="badge badge-secondary">Inactive</span>
                            @else
                            <span class="badge badge-warning">Under Maintenance</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Created At</th>
                        <td>{{ $vehicle->created_at->format('d-m-Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection