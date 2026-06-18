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
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Branch Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="branch_name" value="{{ old('branch_name') }}" required placeholder="Enter branch name (e.g. Rajkot)">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Branch Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="branch_code" value="{{ old('branch_code') }}" required style="text-transform:uppercase" placeholder="Enter code (e.g. RJKT)">
                                        <small class="text-muted">Uppercase letters/numbers only</small>
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
                            <h5><strong>Branch Manager</strong></h5>
                            <p class="text-muted" style="font-size:12px">A branch manager user will be created automatically with this branch</p>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Manager Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="manager_name" value="{{ old('manager_name') }}" required placeholder="Full name">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Manager Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" name="manager_email" value="{{ old('manager_email') }}" required placeholder="Login email">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Manager Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" name="manager_password" required minlength="6" placeholder="Min 6 characters">
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5><strong>Module Permissions</strong></h5>
                            <p class="text-muted" style="font-size:12px">Select which modules this branch can access</p>
                            <div class="row">
                                @foreach($modules as $mod)
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input type="checkbox" name="permissions[]" value="{{ $mod }}" class="form-check-input"
                                            {{ in_array($mod, old('permissions', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label">{{ ucwords(str_replace('_', ' ', $mod)) }}</label>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <hr>
                            <h5><strong>Serial Number Ranges</strong></h5>
                            <p class="text-muted" style="font-size:12px">Assign serial number ranges for each module in this financial year ({{ \App\Services\SerialNumberService::currentFyPrefix() }})</p>

                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Module</th>
                                        <th>Range Start</th>
                                        <th>Range End</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>GR</td>
                                        <td><input type="text" class="form-control form-control-sm" name="gr_range_start" pattern="[0-9]{1,6}" inputmode="numeric" value="{{ old('gr_range_start', 1) }}" min="1" max="999999" required></td>
                                        <td><input type="text" class="form-control form-control-sm" name="gr_range_end" pattern="[0-9]{1,6}" inputmode="numeric" value="{{ old('gr_range_end', 999999) }}" min="1" max="999999" required></td>
                                    </tr>
                                    <tr>
                                        <td>Challan</td>
                                        <td><input type="text" class="form-control form-control-sm" name="challan_range_start" pattern="[0-9]{1,6}" inputmode="numeric" value="{{ old('challan_range_start', 1) }}" min="1" max="999999" required></td>
                                        <td><input type="text" class="form-control form-control-sm" name="challan_range_end" pattern="[0-9]{1,6}" inputmode="numeric" value="{{ old('challan_range_end', 999999) }}" min="1" max="999999" required></td>
                                    </tr>
                                    <tr>
                                        <td>Freight Memo</td>
                                        <td><input type="text" class="form-control form-control-sm" name="freight_memo_range_start" pattern="[0-9]{1,6}" inputmode="numeric" value="{{ old('freight_memo_range_start', 1) }}" min="1" max="999999" required></td>
                                        <td><input type="text" class="form-control form-control-sm" name="freight_memo_range_end" pattern="[0-9]{1,6}" inputmode="numeric" value="{{ old('freight_memo_range_end', 999999) }}" min="1" max="999999" required></td>
                                    </tr>
                                    <tr>
                                        <td>Gate Pass</td>
                                        <td><input type="text" class="form-control form-control-sm" name="gate_pass_range_start" pattern="[0-9]{1,6}" inputmode="numeric" value="{{ old('gate_pass_range_start', 1) }}" min="1" max="999999" required></td>
                                        <td><input type="text" class="form-control form-control-sm" name="gate_pass_range_end" pattern="[0-9]{1,6}" inputmode="numeric" value="{{ old('gate_pass_range_end', 999999) }}" min="1" max="999999" required></td>
                                    </tr>
                                </tbody>
                            </table>

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
