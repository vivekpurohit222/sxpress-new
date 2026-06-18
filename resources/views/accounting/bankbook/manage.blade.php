@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Manage Bank Accounts</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('accounting.bankbook.index') }}">Bank Book</a></li><li class="active">Manage</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row">

    {{-- Existing Banks --}}
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><strong>Bank Accounts</strong></div>
            <div class="card-body">
                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                <table class="table table-bordered table-sm">
                    <thead class="thead-light"><tr><th>Bank</th><th>A/c Number</th><th>IFSC</th><th>Type</th><th>Linked Account</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($banks as $bank)
                        <tr>
                            <td><strong>{{ $bank->bank_name }}</strong><br><small>{{ $bank->branch_name ?? '' }}</small></td>
                            <td>{{ $bank->account_number }}</td>
                            <td>{{ $bank->ifsc_code ?? '-' }}</td>
                            <td>{{ ucfirst($bank->account_type) }}</td>
                            <td>{{ $bank->account->code ?? '-' }} — {{ $bank->account->name ?? '-' }}</td>
                            <td><span class="badge badge-{{ $bank->is_active ? 'success' : 'secondary' }}">{{ $bank->is_active ? 'Active' : 'Inactive' }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">No bank accounts. Add one using the form.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Add Bank Form --}}
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><strong>Add Bank Account</strong></div>
            <div class="card-body">
                @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
                <form method="POST" action="{{ route('accounting.bankbook.store') }}">
                    @csrf
                    <div class="form-group"><label>Bank Name <span class="text-danger">*</span></label>
                        <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}" required placeholder="e.g. State Bank of India"></div>
                    <div class="form-group"><label>Account Number <span class="text-danger">*</span></label>
                        <input type="text" name="account_number" class="form-control" value="{{ old('account_number') }}" required placeholder="e.g. 39876543210"></div>
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label>IFSC Code</label>
                            <input type="text" name="ifsc_code" class="form-control" value="{{ old('ifsc_code') }}" placeholder="e.g. SBIN0001234"></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Account Type</label>
                            <select name="account_type" class="form-control">
                                <option value="current">Current</option>
                                <option value="savings">Savings</option>
                            </select></div></div>
                    </div>
                    <div class="form-group"><label>Bank Branch</label>
                        <input type="text" name="branch_name" class="form-control" value="{{ old('branch_name') }}" placeholder="e.g. Rajkot Main Branch"></div>
                    <div class="form-group"><label>Linked Ledger Account <span class="text-danger">*</span></label>
                        <select name="account_id" class="form-control" required>
                            <option value="">Select Account</option>
                            @foreach($bankLedgerAccounts as $a)
                            <option value="{{ $a->id }}">{{ $a->code }} — {{ $a->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">This links the bank to a ledger account (e.g. 1110 Bank Account - Main)</small>
                    </div>
                    <div class="form-group"><label>Opening Balance (₹)</label>
                        <input type="number" name="opening_balance" class="form-control" value="{{ old('opening_balance', 0) }}" step="0.01" onfocus="this.select()"></div>
                    <button type="submit" class="btn btn-success btn-block">Add Bank Account</button>
                </form>
            </div>
        </div>
    </div>

</div></div></div>
@endsection
