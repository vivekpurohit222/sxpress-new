@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Edit Station</h3>
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
            <form method="POST" action="{{ route('station.update', $station->id) }}">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="station_name">Station Name *</label>
                                <input type="text" class="form-control" id="station_name" name="station_name" value="{{ old('station_name', $station->station_name) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="station_code">Station Code *</label>
                                <input type="text" class="form-control" id="station_code" name="station_code" value="{{ old('station_code', $station->station_code) }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="branch_id">Branch</label>
                                <select class="form-control" id="branch_id" name="branch_id">
                                    <option value="">-- Select Branch --</option>
                                    @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $station->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->branch_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contact_person">Contact Person</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person" value="{{ old('contact_person', $station->contact_person) }}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2">{{ old('address', $station->address) }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="{{ old('city', $station->city) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="state">State</label>
                                <input type="text" class="form-control" id="state" name="state" value="{{ old('state', $station->state) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="pincode">Pincode</label>
                                <input type="text" class="form-control" id="pincode" name="pincode" value="{{ old('pincode', $station->pincode) }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $station->phone) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="mobile">Mobile</label>
                                <input type="text" class="form-control" id="mobile" name="mobile" value="{{ old('mobile', $station->mobile) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $station->email) }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-check mt-4 pt-2">
                                    <input type="checkbox" class="form-check-input" id="is_warehouse" name="is_warehouse" {{ $station->is_warehouse ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_warehouse">Is Warehouse</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-check mt-4 pt-2">
                                    <input type="checkbox" class="form-check-input" id="status" name="status" {{ $station->status ? 'checked' : '' }}>
                                    <label class="form-check-label" for="status">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ url('/dash/station') }}" class="btn btn-default">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection