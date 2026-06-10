@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Customer Details</h3>
                <div class="card-tools">
                    <a href="{{ url('/dash/customer/'.$customer->id.'/edit') }}" class="btn btn-primary btn-sm">Edit</a>
                    <a href="{{ url('/dash/customer') }}" class="btn btn-default btn-sm">Back</a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Customer Name</th>
                        <td>{{ $customer->customer_name }}</td>
                    </tr>
                    <tr>
                        <th>Customer Code</th>
                        <td>{{ $customer->customer_code }}</td>
                    </tr>
                    <tr>
                        <th>Customer Type</th>
                        <td>{{ ucfirst($customer->customer_type) }}</td>
                    </tr>
                    <tr>
                        <th>GST No</th>
                        <td>{{ $customer->gst_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>PAN No</th>
                        <td>{{ $customer->pan_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Billing Address</th>
                        <td>{{ $customer->billing_address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Billing City</th>
                        <td>{{ $customer->billing_city ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Billing State</th>
                        <td>{{ $customer->billing_state ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td>{{ $customer->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $customer->email ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Contact Person</th>
                        <td>{{ $customer->contact_person ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Credit Limit</th>
                        <td>{{ $customer->credit_limit ?? 0 }}</td>
                    </tr>
                    <tr>
                        <th>Payment Terms</th>
                        <td>{{ $customer->payment_terms ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($customer->status)
                            <span class="badge badge-success">Active</span>
                            @else
                            <span class="badge badge-secondary">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Created At</th>
                        <td>{{ $customer->created_at->format('d-m-Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection