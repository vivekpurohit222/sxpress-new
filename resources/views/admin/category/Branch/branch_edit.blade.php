@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Edit Branch: {{ $branch->branch_name }}</h3>
            </div>

            @if($errors->any())
            <div class="alert alert-danger m-3">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('branch.update', $branch->id) }}">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="branch_name">Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="branch_name" name="branch_name" value="{{ old('branch_name', $branch->branch_name) }}" required>
                                @if($hasGrs)
                                    <small class="text-warning"><i class="fa fa-info-circle"></i> Renaming will cascade to all users and GRs.</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="branch_code">Branch Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="branch_code" name="branch_code" value="{{ old('branch_code', $branch->branch_code) }}" required style="text-transform:uppercase">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2">{{ old('address', $branch->address) }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="{{ old('city', $branch->city) }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="state">State</label>
                                <input type="text" class="form-control" id="state" name="state" value="{{ old('state', $branch->state) }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="pincode">Pincode</label>
                                <input type="text" class="form-control" id="pincode" name="pincode" value="{{ old('pincode', $branch->pincode) }}" maxlength="6">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $branch->phone) }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $branch->email) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-check mt-4 pt-2">
                                    <input type="hidden" name="status" value="0">
                                    <input type="checkbox" class="form-check-input" id="status" name="status" value="1" {{ old('status', $branch->status) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="status">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Branch Manager --}}
                <div class="card-body border-top">
                    <h5><strong>Branch Manager</strong></h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Manager Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="manager_name" value="{{ old('manager_name', $manager->name ?? '') }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Manager Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="manager_email" value="{{ old('manager_email', $manager->email ?? '') }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" class="form-control" name="manager_password" minlength="6" placeholder="Leave blank to keep current">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Module Permissions --}}
                <div class="card-body border-top">
                    <h5><strong>Module Permissions</strong></h5>
                    <div class="row">
                        @php $allModules = ['gr','challan','freight_memo','import_challan','gate_pass','dds']; @endphp
                        @foreach($allModules as $mod)
                        <div class="col-md-4 mb-2">
                            <div class="form-check">
                                <input type="checkbox" name="permissions[]" value="{{ $mod }}" class="form-check-input"
                                    {{ in_array($mod, old('permissions', $branchPerms ?? [])) ? 'checked' : '' }}>
                                <label class="form-check-label">{{ ucwords(str_replace('_', ' ', $mod)) }}</label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Serial Number Ranges --}}
                <div class="card-body border-top">
                    <h5><strong>Serial Number Ranges</strong></h5>
                    <p class="text-muted" style="font-size:12px">Serial ranges for financial year {{ \App\Services\SerialNumberService::currentFyPrefix() }}</p>

                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Module</th>
                                <th>Range Start</th>
                                <th>Range End</th>
                                <th>Current</th>
                                <th>Remaining</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>GR</td>
                                <td><input type="text" class="form-control form-control-sm" name="gr_range_start" pattern="[0-9]{1,6}" inputmode="numeric" value="{{ old('gr_range_start', isset($serials['gr']) ? $serials['gr']->range_start : 1) }}" min="1" max="999999" required></td>
                                <td><input type="text" class="form-control form-control-sm" name="gr_range_end" pattern="[0-9]{1,6}" inputmode="numeric" value="{{ old('gr_range_end', isset($serials['gr']) ? $serials['gr']->range_end : 999999) }}" min="1" max="999999" required></td>
                                <td>{{ isset($serials['gr']) ? $serials['gr']->current_value : 0 }}</td>
                                <td>{{ isset($serials['gr']) ? max(0, $serials['gr']->range_end - ($serials['gr']->current_value ?: $serials['gr']->range_start - 1)) : '-' }}</td>
                            </tr>
                            <tr>
                                <td>Challan</td>
                                <td><input type="text" class="form-control form-control-sm" name="challan_range_start" pattern="[0-9]{1,6}" inputmode="numeric" value="{{ old('challan_range_start', isset($serials['challan']) ? $serials['challan']->range_start : 1) }}" min="1" max="999999" required></td>
                                <td><input type="text" class="form-control form-control-sm" name="challan_range_end" pattern="[0-9]{1,6}" inputmode="numeric" value="{{ old('challan_range_end', isset($serials['challan']) ? $serials['challan']->range_end : 999999) }}" min="1" max="999999" required></td>
                                <td>{{ isset($serials['challan']) ? $serials['challan']->current_value : 0 }}</td>
                                <td>{{ isset($serials['challan']) ? max(0, $serials['challan']->range_end - ($serials['challan']->current_value ?: $serials['challan']->range_start - 1)) : '-' }}</td>
                            </tr>
                            <tr>
                                <td>Freight Memo</td>
                                <td><input type="text" class="form-control form-control-sm" name="freight_memo_range_start" pattern="[0-9]{1,6}" inputmode="numeric" value="{{ old('freight_memo_range_start', isset($serials['freight_memo']) ? $serials['freight_memo']->range_start : 1) }}" min="1" max="999999" required></td>
                                <td><input type="text" class="form-control form-control-sm" name="freight_memo_range_end" pattern="[0-9]{1,6}" inputmode="numeric" value="{{ old('freight_memo_range_end', isset($serials['freight_memo']) ? $serials['freight_memo']->range_end : 999999) }}" min="1" max="999999" required></td>
                                <td>{{ isset($serials['freight_memo']) ? $serials['freight_memo']->current_value : 0 }}</td>
                                <td>{{ isset($serials['freight_memo']) ? max(0, $serials['freight_memo']->range_end - ($serials['freight_memo']->current_value ?: $serials['freight_memo']->range_start - 1)) : '-' }}</td>
                            </tr>
                            <tr>
                                <td>Gate Pass</td>
                                <td><input type="text" class="form-control form-control-sm" name="gate_pass_range_start" pattern="[0-9]{1,6}" inputmode="numeric" value="{{ old('gate_pass_range_start', isset($serials['gate_pass']) ? $serials['gate_pass']->range_start : 1) }}" min="1" max="999999" required></td>
                                <td><input type="text" class="form-control form-control-sm" name="gate_pass_range_end" pattern="[0-9]{1,6}" inputmode="numeric" value="{{ old('gate_pass_range_end', isset($serials['gate_pass']) ? $serials['gate_pass']->range_end : 999999) }}" min="1" max="999999" required></td>
                                <td>{{ isset($serials['gate_pass']) ? $serials['gate_pass']->current_value : 0 }}</td>
                                <td>{{ isset($serials['gate_pass']) ? max(0, $serials['gate_pass']->range_end - ($serials['gate_pass']->current_value ?: $serials['gate_pass']->range_start - 1)) : '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Update Branch</button>
                    <a href="{{ route('branch.index') }}" class="btn btn-default">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
