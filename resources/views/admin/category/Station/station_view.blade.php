@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Station Details</h3>
                <div class="card-tools">
                    <a href="{{ url('/dash/station/'.$station->id.'/edit') }}" class="btn btn-primary btn-sm">Edit</a>
                    <a href="{{ url('/dash/station') }}" class="btn btn-default btn-sm">Back</a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Station Name</th>
                        <td>{{ $station->station_name }}</td>
                    </tr>
                    <tr>
                        <th>Station Code</th>
                        <td>{{ $station->station_code }}</td>
                    </tr>
                    <tr>
                        <th>Branch</th>
                        <td>{{ $station->branch ? $station->branch->branch_name : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td>{{ $station->address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>City</th>
                        <td>{{ $station->city ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>State</th>
                        <td>{{ $station->state ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Pincode</th>
                        <td>{{ $station->pincode ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td>{{ $station->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Mobile</th>
                        <td>{{ $station->mobile ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $station->email ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Contact Person</th>
                        <td>{{ $station->contact_person ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Is Warehouse</th>
                        <td>{{ $station->is_warehouse ? 'Yes' : 'No' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($station->status)
                            <span class="badge badge-success">Active</span>
                            @else
                            <span class="badge badge-secondary">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Created At</th>
                        <td>{{ $station->created_at->format('d-m-Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection