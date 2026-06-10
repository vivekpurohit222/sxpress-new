@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Edit User</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li><a href="{{ route('users.index') }}">Users</a></li>
            <li class="active">Edit</li>
        </ol>
    </div></div></div>
</div>

<div class="content mt-3">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="card">
                    <div class="card-header">
                        <strong>Edit {{ $user->name }}</strong>
                        @if(!$user->is_active)<span class="badge badge-danger ml-2">Inactive</span>@endif
                    </div>
                    <div class="card-body">

                        @if(session('flash_message'))
                        <div class="alert alert-success">{{ session('flash_message') }}</div>
                        @endif
                        @if($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                        @endif

                        <form method="POST" action="{{ route('users.update', $user->id) }}">
                            @csrf @method('PUT')

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required maxlength="120" placeholder="Enter full name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email Address <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required placeholder="Enter email address">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Phone Number</label>
                                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" maxlength="15" placeholder="Enter phone number">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Branch / Office <span class="text-danger">*</span></label>
                                        <select name="office" class="form-control" required>
                                            @foreach($offices as $office)
                                                <option value="{{ $office }}" {{ old('office', $user->office) == $office ? 'selected' : '' }}>{{ $office }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Assign Role <span class="text-danger">*</span></label>
                                <div class="mt-1">
                                    @php $userRoleIds = $user->roles->pluck('id')->toArray(); @endphp
                                    @foreach($roles as $role)
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="form-check-input"
                                                {{ in_array($role->id, old('roles', $userRoleIds)) ? 'checked' : '' }}>
                                            <label class="form-check-label">{{ $role->name }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>New Password</label>
                                        <input type="password" name="password" class="form-control" minlength="8" placeholder="Leave blank to keep current">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Confirm New Password</label>
                                        <input type="password" name="password_confirmation" class="form-control" placeholder="Re-enter new password">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                                        {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active" style="text-transform:none;font-size:13px">Account Active</label>
                                </div>
                            </div>

                            <hr>
                            <button type="submit" class="btn btn-primary btn-block">Update User</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
