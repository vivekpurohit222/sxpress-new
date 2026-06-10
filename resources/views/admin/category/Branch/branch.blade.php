@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Add Branch</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li><a href="{{ route('branch.index') }}">Branches</a></li>
            <li class="active">Add</li>
        </ol>
    </div></div></div>
</div>

<div class="content mt-3">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-lg-10 offset-lg-1">
                <div class="card">
                    <div class="card-header"><strong>Add New Branch</strong></div>
                    <div class="card-body">

                        @if($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                        @endif

                        <form method="POST" action="{{ route('branch.store') }}">
                            @csrf

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Branch Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="branch_name" value="{{ old('branch_name') }}" required placeholder="Enter branch name (e.g. Rajkot)">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Branch Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="branch_code" value="{{ old('branch_code') }}" required style="text-transform:uppercase" placeholder="Enter code (e.g. RJKT)">
                                        <small class="text-muted">Uppercase letters/numbers only</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>GR Prefix <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="gr_prefix" value="{{ old('gr_prefix') }}" maxlength="2" placeholder="e.g. AA" pattern="[A-Z]{2}" style="text-transform:uppercase" required>
                                        <small class="text-muted">2 uppercase letters for GR numbering</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Address</label>
                                <textarea class="form-control" name="address" rows="2" placeholder="Enter full branch address">{{ old('address') }}</textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>City</label>
                                        <input type="text" class="form-control" name="city" value="{{ old('city') }}" placeholder="Enter city name">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>State</label>
                                        <input type="text" class="form-control" name="state" value="{{ old('state') }}" placeholder="Enter state name">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Pincode</label>
                                        <input type="text" class="form-control" name="pincode" value="{{ old('pincode') }}" maxlength="6" placeholder="6-digit pincode">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="text" class="form-control" name="phone" value="{{ old('phone') }}" placeholder="Enter phone number">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="Enter branch email address">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group" style="padding-top:24px">
                                        <div class="form-check">
                                            <input type="hidden" name="status" value="0">
                                            <input type="checkbox" class="form-check-input" id="status" name="status" value="1" {{ old('status', '1') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="status" style="text-transform:none;font-size:13px">Branch is Active</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-success btn-block">Create Branch</button>
                                </div>
                                <div class="col-md-6">
                                    <a href="{{ route('branch.index') }}" class="btn btn-secondary btn-block">Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
