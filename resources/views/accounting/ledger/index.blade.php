@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Ledger</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li class="active">Ledger</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    <div class="card-header">
        <strong>General Ledger — Account Balances</strong>
    </div>
    <div class="card-body">
        {{-- Filters --}}
        <form method="GET" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <select name="type" class="form-control">
                        <option value="">All Types</option>
                        @foreach($types as $t)
                        <option value="{{ $t }}" {{ $type == $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>
                @if($branches->count())
                <div class="col-md-3">
                    <select name="branch" class="form-control">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                        <option value="{{ $b }}" {{ $branch == $b ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ route('accounting.ledger.index') }}" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </div>
        </form>

        {{-- Quick Ledger Filters (per document: Customer, Vehicle, Driver, Branch ledgers) --}}
        <div class="mb-3">
            <small class="text-muted mr-2">Quick:</small>
            <a href="{{ route('accounting.ledger.index') }}" class="btn btn-sm {{ !$filter ? 'btn-dark' : 'btn-outline-dark' }}">All</a>
            <a href="{{ route('accounting.ledger.index', ['filter' => 'receivable']) }}" class="btn btn-sm {{ ($filter ?? '') == 'receivable' ? 'btn-success' : 'btn-outline-success' }}">Receivables</a>
            <a href="{{ route('accounting.ledger.index', ['filter' => 'payable']) }}" class="btn btn-sm {{ ($filter ?? '') == 'payable' ? 'btn-danger' : 'btn-outline-danger' }}">Payables</a>
            <a href="{{ route('accounting.ledger.index', ['filter' => 'vehicle']) }}" class="btn btn-sm {{ ($filter ?? '') == 'vehicle' ? 'btn-info' : 'btn-outline-info' }}">Vehicle</a>
            <a href="{{ route('accounting.ledger.index', ['filter' => 'staff']) }}" class="btn btn-sm {{ ($filter ?? '') == 'staff' ? 'btn-warning' : 'btn-outline-warning' }}">Staff/Driver</a>
            <a href="{{ route('accounting.ledger.index', ['filter' => 'office']) }}" class="btn btn-sm {{ ($filter ?? '') == 'office' ? 'btn-secondary' : 'btn-outline-secondary' }}">Office</a>
        </div>

        <table class="table table-bordered table-sm">
            <thead class="thead-dark">
                <tr>
                    <th>Code</th>
                    <th>Account Name</th>
                    <th>Type</th>
                    <th class="text-right">Balance (₹)</th>
                    <th style="width:100px">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                <tr>
                    <td>{{ $account->code }}</td>
                    <td><strong>{{ $account->name }}</strong></td>
                    <td><span class="badge badge-{{ match($account->type) { 'asset'=>'primary','liability'=>'warning','income'=>'success','expense'=>'danger','equity'=>'info',default=>'secondary' } }}">{{ ucfirst($account->type) }}</span></td>
                    <td class="text-right {{ $account->current_balance < 0 ? 'text-danger' : '' }}">
                        ₹ {{ number_format(abs($account->current_balance), 2) }}
                        {{ $account->current_balance < 0 ? ' Cr' : ' Dr' }}
                    </td>
                    <td>
                        <a href="{{ route('accounting.ledger.show', $account->id) }}" class="btn btn-info btn-sm"><i class="fa fa-book"></i> View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-3">No accounts found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div></div></div></div></div>
@endsection
