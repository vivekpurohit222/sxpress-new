@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Chart of Accounts</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li class="active">Accounts</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show m-3">{{ session('success') }}<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger m-3"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="card-header">
        <strong>Chart of Accounts</strong>
        <a href="{{ route('accounting.accounts.create') }}" class="btn btn-success btn-sm pull-right"><i class="fa fa-plus"></i> Add Account</a>
    </div>
    <div class="card-body">
        {{-- Type Filter --}}
        <div class="mb-3">
            <a href="{{ route('accounting.accounts.index') }}" class="btn btn-sm {{ !$type ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
            @foreach($types as $t)
            <a href="{{ route('accounting.accounts.index', ['type' => $t]) }}" class="btn btn-sm {{ $type == $t ? 'btn-primary' : 'btn-outline-secondary' }}">{{ ucfirst($t) }}</a>
            @endforeach
        </div>

        {{-- Account Tree --}}
        <table class="table table-bordered table-sm">
            <thead class="thead-dark">
                <tr><th style="width:10%">Code</th><th>Account Name</th><th style="width:12%">Type</th><th style="width:10%">Group</th><th class="text-right" style="width:12%">Opening Bal.</th><th style="width:8%">Status</th><th style="width:12%">Actions</th></tr>
            </thead>
            <tbody>
                @foreach($accounts as $account)
                    @include('accounting.accounts.partials.account_row', ['account' => $account, 'level' => 0])
                @endforeach
                @if($accounts->isEmpty())
                <tr><td colspan="7" class="text-center text-muted py-3">No accounts found. <a href="{{ route('accounting.accounts.create') }}">Create one</a>.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div></div></div></div></div>
@endsection
