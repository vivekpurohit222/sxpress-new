@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Add User</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li><a href="{{ route('users.index') }}">Users</a></li>
            <li class="active">Add User</li>
        </ol>
    </div></div></div>
</div>

<div class="content mt-3">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="card">
                    <div class="card-header"><strong>Add New User</strong></div>
                    <div class="card-body">

                        @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                        @endif

                        <form method="POST" action="{{ route('users.store') }}">
                            @csrf

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required maxlength="120" placeholder="Enter full name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email Address <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required placeholder="Enter email address">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Phone Number</label>
                                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" maxlength="15" placeholder="Enter phone number">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Branch / Office <span class="text-danger">*</span></label>
                                        <select name="office" class="form-control" required>
                                            <option value="">-- Select office --</option>
                                            @foreach($offices as $office)
                                                <option value="{{ $office }}" {{ old('office') == $office ? 'selected' : '' }}>{{ $office }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Assign Role <span class="text-danger">*</span></label>
                                <div class="mt-1">
                                    @foreach($roles as $role)
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="form-check-input"
                                                {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label">{{ $role->name }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Password <span class="text-danger">*</span></label>
                                        <input type="password" name="password" class="form-control" required minlength="8" placeholder="Min 8 chars, 1 uppercase, 1 number">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Confirm Password <span class="text-danger">*</span></label>
                                        <input type="password" name="password_confirmation" class="form-control" required placeholder="Re-enter password">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                                        {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active" style="text-transform:none;font-size:13px">Account Active (user can login immediately)</label>
                                </div>
                            </div>

                            <hr>
                            <button type="submit" class="btn btn-success btn-block">Create User</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
