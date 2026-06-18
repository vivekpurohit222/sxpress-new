@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Add Account</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('accounting.accounts.index') }}">Accounts</a></li><li class="active">Add</li></ol>
    </div></div></div>
</div>

<div class="content mt-3"><div class="animated fadeIn"><div class="row"><div class="col-lg-8 offset-lg-2"><div class="card">
    <div class="card-header"><strong>Create New Account</strong></div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

        <form method="POST" action="{{ route('accounting.accounts.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Account Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" value="{{ old('code', $nextCode) }}" required placeholder="e.g. 1101">
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Account Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="e.g. Cash In Hand">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-control" required>
                            @foreach($types as $t)<option value="{{ $t }}" {{ old('type') == $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Parent Account</label>
                        <select name="parent_id" class="form-control">
                            <option value="">-- Root Level --</option>
                            @foreach($parentAccounts as $p)
                            <option value="{{ $p->id }}" {{ old('parent_id') == $p->id ? 'selected' : '' }}>{{ $p->code }} - {{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Opening Balance (₹)</label>
                        <input type="number" name="opening_balance" class="form-control" value="{{ old('opening_balance') }}" step="0.01" placeholder="0.00" onfocus="this.select()">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="form-check">
                    <input type="hidden" name="is_group" value="0">
                    <input type="checkbox" name="is_group" value="1" class="form-check-input" id="is_group" {{ old('is_group') ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_group" style="text-transform:none;font-size:13px">This is a Group Account (parent only, no direct transactions)</label>
                </div>
            </div>
            <hr>
            <button type="submit" class="btn btn-success btn-block">Create Account</button>
        </form>
    </div>
</div></div></div></div></div>
@endsection
