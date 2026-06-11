@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Consignor Details</h3>
                <div class="card-tools">
                    <a href="{{ url('/consignor/'.$consignor->id.'/edit') }}" class="btn btn-primary btn-sm">Edit</a>
                    <a href="{{ url('/consignor') }}" class="btn btn-default btn-sm">Back</a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Consignor Name</th>
                        <td>{{ $consignor->consignor_name }}</td>
                    </tr>
                    <tr>
                        <th>Consignor Code</th>
                        <td>{{ $consignor->consignor_code }}</td>
                    </tr>
                    <tr>
                        <th>GST No</th>
                        <td>{{ $consignor->gst_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>PAN No</th>
                        <td>{{ $consignor->pan_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td>{{ $consignor->address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>City</th>
                        <td>{{ $consignor->city ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>State</th>
                        <td>{{ $consignor->state ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Pincode</th>
                        <td>{{ $consignor->pincode ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td>{{ $consignor->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Mobile</th>
                        <td>{{ $consignor->mobile ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $consignor->email ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Contact Person</th>
                        <td>{{ $consignor->contact_person ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($consignor->status)
                            <span class="badge badge-success">Active</span>
                            @else
                            <span class="badge badge-secondary">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Created At</th>
                        <td>{{ $consignor->created_at->format('d-m-Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
