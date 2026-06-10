@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Users</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li class="active">Users</li>
        </ol>
    </div></div></div>
</div>

<div class="content mt-3">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-md-12">
                <div class="card">

                    @if(session('flash_message'))
                    <div class="alert alert-success alert-dismissible fade show m-3">
                        {{ session('flash_message') }}
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                    @endif
                    @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show m-3">
                        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                    @endif

                    <div class="card-header">
                        <strong>User Management</strong>
                        <a href="{{ route('users.create') }}" class="btn btn-success btn-sm pull-right"><i class="fa fa-plus"></i> Add User</a>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('users.index') }}" class="mb-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="text" name="search" class="form-control" placeholder="Search name, email, phone..." value="{{ request('search') }}">
                                </div>
                                <div class="col-md-2">
                                    <select name="office" class="form-control">
                                        <option value="">All Offices</option>
                                        @foreach($offices as $office)
                                        <option value="{{ $office }}" {{ request('office') == $office ? 'selected' : '' }}>{{ $office }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="status" class="form-control">
                                        <option value="">All Status</option>
                                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                                    <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">Reset</a>
                                </div>
                            </div>
                        </form>

                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Office</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th style="width:140px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                <tr class="{{ !$user->is_active ? 'text-muted' : '' }}">
                                    <td><strong>{{ $user->name }}</strong></td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->office ?? '-' }}</td>
                                    <td>{{ $user->phone ?? '-' }}</td>
                                    <td><span class="badge badge-primary">{{ $user->roles->pluck('name')->implode(', ') }}</span></td>
                                    <td>
                                        @if($user->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-info btn-sm" title="Edit"><i class="fa fa-edit"></i></a>
                                        <form method="POST" action="{{ route('users.toggle-active', $user->id) }}" style="display:inline;">
                                            @csrf @method('PATCH')
                                            @if($user->is_active)
                                                <button type="submit" class="btn btn-warning btn-sm" title="Deactivate" onclick="return confirm('Deactivate {{ $user->name }}?')"><i class="fa fa-ban"></i></button>
                                            @else
                                                <button type="submit" class="btn btn-success btn-sm" title="Reactivate" onclick="return confirm('Reactivate {{ $user->name }}?')"><i class="fa fa-check"></i></button>
                                            @endif
                                        </form>
                                        {!! Form::open(['method' => 'DELETE', 'route' => ['users.destroy', $user->id], 'style' => 'display:inline;']) !!}
                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Permanently delete {{ $user->name }}?')"><i class="fa fa-trash"></i></button>
                                        {!! Form::close() !!}
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted">No users found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-2">{{ $users->withQueryString()->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
