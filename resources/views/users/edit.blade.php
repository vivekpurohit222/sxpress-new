@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Edit Agent</h1></div></div></div>
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
                    <div class="card-header"><strong>Edit {{ $user->name }}</strong></div>
                    <div class="card-body">

                        @if($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                        @endif

                        <form method="POST" action="{{ route('users.update', $user->id) }}">
                            @csrf @method('PUT')

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>New Password</label>
                                        <input type="password" name="password" class="form-control" minlength="6" placeholder="Leave blank to keep current">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Confirm Password</label>
                                        <input type="password" name="password_confirmation" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Branch <span class="text-danger">*</span></label>
                                <select name="branch_id" class="form-control" required>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id', $user->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->branch_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <hr>
                            <label><strong>Module Permissions</strong></label>
                            <div class="row mt-2">
                                @foreach($modules as $mod)
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input type="checkbox" name="permissions[]" value="{{ $mod }}" class="form-check-input"
                                            {{ in_array($mod, old('permissions', $userPerms)) ? 'checked' : '' }}>
                                        <label class="form-check-label">{{ ucwords(str_replace('_', ' ', $mod)) }}</label>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <hr>
                            <button type="submit" class="btn btn-primary btn-block">Update Agent</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
