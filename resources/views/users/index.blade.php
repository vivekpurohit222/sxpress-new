@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Agents</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li class="active">Users (Agents)</li>
        </ol>
    </div></div></div>
</div>

<div class="content mt-3">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-md-12">
                <div class="card">

                    @if(session('flash_message'))
                    <div class="alert alert-success alert-dismissible fade show m-3">{{ session('flash_message') }}<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
                    @endif
                    @if($errors->any())
                    <div class="alert alert-danger m-3">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                    @endif

                    <div class="card-header">
                        <strong>Agent Management</strong>
                        <a href="{{ route('users.create') }}" class="btn btn-success btn-sm pull-right"><i class="fa fa-plus"></i> Add Agent</a>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="mb-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="text" name="search" class="form-control" placeholder="Search name or email..." value="{{ request('search') }}">
                                </div>
                                <div class="col-md-3">
                                    <select name="branch_id" class="form-control">
                                        <option value="">All Branches</option>
                                        @foreach($branches as $b)
                                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->branch_name }}</option>
                                        @endforeach
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
                                <tr><th>Name</th><th>Email</th><th>Branch</th><th>Status</th><th style="width:140px">Actions</th></tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                <tr class="{{ !$user->is_active ? 'text-muted' : '' }}">
                                    <td><strong>{{ $user->name }}</strong></td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->branch->branch_name ?? '-' }}</td>
                                    <td>
                                        @if($user->is_active)<span class="badge badge-success">Active</span>
                                        @else<span class="badge badge-danger">Inactive</span>@endif
                                    </td>
                                    <td>
                                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-info btn-sm"><i class="fa fa-edit"></i></a>
                                        <form method="POST" action="{{ route('users.toggle-active', $user->id) }}" style="display:inline;">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-{{ $user->is_active ? 'warning' : 'success' }} btn-sm" onclick="return confirm('Toggle status?')">
                                                <i class="fa fa-{{ $user->is_active ? 'ban' : 'check' }}"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('users.destroy', $user->id) }}" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete {{ $user->name }}?')"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted">No agents found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-2">{{ $users->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
