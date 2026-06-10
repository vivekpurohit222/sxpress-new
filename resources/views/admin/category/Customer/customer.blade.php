@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Add New Customer</h3>
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
            <form method="POST" action="{{ route('customer.store') }}">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="customer_name">Customer Name *</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="customer_code">Customer Code *</label>
                                <input type="text" class="form-control" id="customer_code" name="customer_code" value="{{ old('customer_code') }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="customer_type">Customer Type</label>
                                <select class="form-control" id="customer_type" name="customer_type">
                                    <option value="party">Party</option>
                                    <option value="company">Company</option>
                                    <option value="individual">Individual</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="gst_no">GST No</label>
                                <input type="text" class="form-control" id="gst_no" name="gst_no" value="{{ old('gst_no') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="pan_no">PAN No</label>
                                <input type="text" class="form-control" id="pan_no" name="pan_no" value="{{ old('pan_no') }}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="billing_address">Billing Address</label>
                        <textarea class="form-control" id="billing_address" name="billing_address" rows="2">{{ old('billing_address') }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="billing_city">City</label>
                                <input type="text" class="form-control" id="billing_city" name="billing_city" value="{{ old('billing_city') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="billing_state">State</label>
                                <input type="text" class="form-control" id="billing_state" name="billing_state" value="{{ old('billing_state') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="billing_pincode">Pincode</label>
                                <input type="text" class="form-control" id="billing_pincode" name="billing_pincode" value="{{ old('billing_pincode') }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="contact_person">Contact Person</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person" value="{{ old('contact_person') }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="credit_limit">Credit Limit</label>
                                <input type="number" class="form-control" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', 0) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="payment_terms">Payment Terms</label>
                                <input type="text" class="form-control" id="payment_terms" name="payment_terms" value="{{ old('payment_terms') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="form-check mt-4 pt-2">
                                    <input type="checkbox" class="form-check-input" id="status" name="status" checked>
                                    <label class="form-check-label" for="status">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <a href="{{ url('/dash/customer') }}" class="btn btn-default">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection