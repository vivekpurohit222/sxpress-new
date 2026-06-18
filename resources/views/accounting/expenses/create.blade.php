@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Record Expense</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('accounting.expenses.index') }}">Expenses</a></li><li class="active">New</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-lg-9 offset-lg-1"><div class="card">
    <div class="card-header"><strong>New Expense</strong> <span class="float-right">Office: {{ $office }}</span></div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

        <form method="POST" action="{{ route('accounting.expenses.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date <span class="text-danger">*</span></label>
                        <input type="date" name="expense_date" class="form-control" value="{{ old('expense_date', now()->format('Y-m-d')) }}" required max="{{ now()->format('Y-m-d') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Expense Type <span class="text-danger">*</span></label>
                        <select name="expense_type" id="expense_type" class="form-control" required>
                            @foreach($types as $t)<option value="{{ $t }}" {{ old('expense_type') == $t ? 'selected' : '' }}>{{ \App\Models\Accounting\Expense::getTypeLabel($t) }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" value="{{ old('amount') }}" step="0.01" min="0.01" required placeholder="0.00" onfocus="this.select()">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Paid To</label>
                        <input type="text" name="paid_to" class="form-control" value="{{ old('paid_to') }}" placeholder="e.g. Petrol Pump / Driver name">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Description <span class="text-danger">*</span></label>
                <input type="text" name="description" class="form-control" value="{{ old('description') }}" required placeholder="e.g. Diesel for truck GJ-03-AB-1234 — Rajkot to Navagam trip">
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Expense Account (Debit) <span class="text-danger">*</span></label>
                        <select name="account_id" class="form-control" required>
                            @foreach($expenseAccounts as $a)
                            <option value="{{ $a->id }}" {{ old('account_id') == $a->id ? 'selected' : '' }}>{{ $a->code }} — {{ $a->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Which expense category does this fall under?</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Paid From (Credit) <span class="text-danger">*</span></label>
                        <select name="paid_from_account_id" class="form-control" required>
                            @foreach($cashAccounts as $a)
                            <option value="{{ $a->id }}" {{ old('paid_from_account_id') == $a->id ? 'selected' : '' }}>{{ $a->code }} — {{ $a->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Cash in hand or bank account used for payment</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Vehicle (if applicable)</label>
                        <select name="vehicle_id" class="form-control">
                            <option value="">-- None --</option>
                            @foreach($vehicles as $v)<option value="{{ $v->id }}" {{ old('vehicle_id') == $v->id ? 'selected' : '' }}>{{ $v->vehicle_number }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Driver (if applicable)</label>
                        <select name="driver_id" class="form-control">
                            <option value="">-- None --</option>
                            @foreach($drivers as $d)<option value="{{ $d->id }}" {{ old('driver_id') == $d->id ? 'selected' : '' }}>{{ $d->driver_name }}</option>@endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Optional additional details...">{{ old('notes') }}</textarea>
            </div>

            <hr>
            <button type="submit" class="btn btn-danger btn-lg btn-block">Record Expense</button>
        </form>
    </div>
</div></div></div></div></div>
@endsection
