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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="branch_name">Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="branch_name" name="branch_name" value="{{ old('branch_name', $branch->branch_name) }}" required>
                                @if($hasGrs)
                                    <small class="text-warning"><i class="fa fa-info-circle"></i> Renaming will cascade to all users and GRs.</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="branch_code">Branch Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="branch_code" name="branch_code" value="{{ old('branch_code', $branch->branch_code) }}" required style="text-transform:uppercase">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="gr_prefix">GR Prefix <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="gr_prefix" name="gr_prefix" value="{{ old('gr_prefix', $branch->gr_prefix) }}" maxlength="2" pattern="[A-Z]{2}" style="text-transform:uppercase" required {{ $hasGrs ? 'readonly' : '' }}>
                                @if($hasGrs)
                                    <small class="text-danger"><i class="fa fa-lock"></i> Locked — GRs exist with this prefix.</small>
                                @else
                                    <small class="text-muted">2 uppercase letters (e.g. AA, CG)</small>
                                @endif
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
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Update Branch</button>
                    <a href="{{ route('branch.index') }}" class="btn btn-default">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
