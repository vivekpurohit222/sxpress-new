@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Expenses</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li class="active">Expenses</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show m-3">{{ session('success') }}<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>@endif

    <div class="card-header">
        <strong>Expense Management</strong>
        <div class="float-right">
            <a href="{{ route('accounting.expenses.create') }}" class="btn btn-danger btn-sm"><i class="fa fa-plus"></i> New Expense</a>
            <a href="{{ route('accounting.expenses.report') }}" class="btn btn-info btn-sm"><i class="fa fa-bar-chart"></i> Report</a>
        </div>
    </div>
    <div class="card-body">
        {{-- Summary --}}
        <div class="row mb-3">
            <div class="col-md-4"><div class="card border-danger"><div class="card-body text-center py-2"><h5 class="mb-0 text-danger">₹ {{ number_format($summary['total_month'], 0) }}</h5><small>This Month</small></div></div></div>
            <div class="col-md-4"><div class="card border-warning"><div class="card-body text-center py-2"><h5 class="mb-0 text-warning">{{ $summary['pending_count'] }}</h5><small>Pending Approval</small></div></div></div>
            <div class="col-md-4"><div class="card border-warning"><div class="card-body text-center py-2"><h5 class="mb-0">₹ {{ number_format($summary['pending_amount'], 0) }}</h5><small>Pending Amount</small></div></div></div>
        </div>

        {{-- Filters --}}
        <form method="GET" class="mb-3 no-print">
            <div class="row">
                <div class="col-md-2"><select name="type" class="form-control form-control-sm"><option value="">All Types</option>@foreach($types as $t)<option value="{{ $t }}" {{ request('type') == $t ? 'selected' : '' }}>{{ \App\Models\Accounting\Expense::getTypeLabel($t) }}</option>@endforeach</select></div>
                <div class="col-md-2"><select name="status" class="form-control form-control-sm"><option value="">All Status</option><option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending</option><option value="approved" {{ request('status')=='approved'?'selected':'' }}>Approved</option></select></div>
                <div class="col-md-2"><input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}"></div>
                <div class="col-md-2"><input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm">Filter</button> <a href="{{ route('accounting.expenses.index') }}" class="btn btn-secondary btn-sm">Reset</a></div>
            </div>
        </form>

        <table class="table table-bordered table-sm">
            <thead class="thead-dark">
                <tr><th>Exp No</th><th>Date</th><th>Type</th><th>Description</th><th>Paid To</th><th class="text-right">Amount</th><th>Branch</th><th>Status</th></tr>
            </thead>
            <tbody>
                @forelse($expenses as $exp)
                <tr>
                    <td><strong>{{ $exp->expense_no }}</strong></td>
                    <td>{{ $exp->expense_date->format('d-m-Y') }}</td>
                    <td><span class="badge badge-danger">{{ \App\Models\Accounting\Expense::getTypeLabel($exp->expense_type) }}</span></td>
                    <td>{{ Str::limit($exp->description, 35) }}</td>
                    <td>{{ $exp->paid_to ?: '-' }}</td>
                    <td class="text-right font-weight-bold">₹{{ number_format($exp->amount, 0) }}</td>
                    <td>{{ $exp->branch }}</td>
                    <td><span class="badge badge-{{ $exp->status == 'approved' ? 'success' : ($exp->status == 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($exp->status) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-3">No expenses recorded yet. <a href="{{ route('accounting.expenses.create') }}">Add one</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-2">{{ $expenses->withQueryString()->links() }}</div>
    </div>
</div></div></div></div></div>
@endsection
