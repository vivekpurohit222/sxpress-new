@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Consignee Details</h3>
                <div class="card-tools">
                    <a href="{{ url('/consignee/'.$consignee->id.'/edit') }}" class="btn btn-primary btn-sm">Edit</a>
                    <a href="{{ url('/consignee') }}" class="btn btn-default btn-sm">Back</a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Consignee Name</th>
                        <td>{{ $consignee->consignee_name }}</td>
                    </tr>
                    <tr>
                        <th>Consignee Code</th>
                        <td>{{ $consignee->consignee_code }}</td>
                    </tr>
                    <tr>
                        <th>GST No</th>
                        <td>{{ $consignee->gst_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>PAN No</th>
                        <td>{{ $consignee->pan_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td>{{ $consignee->address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>City</th>
                        <td>{{ $consignee->city ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>State</th>
                        <td>{{ $consignee->state ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Pincode</th>
                        <td>{{ $consignee->pincode ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td>{{ $consignee->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Mobile</th>
                        <td>{{ $consignee->mobile ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $consignee->email ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Contact Person</th>
                        <td>{{ $consignee->contact_person ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($consignee->status)
                            <span class="badge badge-success">Active</span>
                            @else
                            <span class="badge badge-secondary">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Created At</th>
                        <td>{{ $consignee->created_at->format('d-m-Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
