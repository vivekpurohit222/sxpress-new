@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Vouchers</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li class="active">Vouchers</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show m-3">{{ session('success') }}<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
    @endif
    @if($errors->any())<div class="alert alert-danger m-3"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="card-header">
        <strong>Vouchers</strong>
        <div class="float-right">
            <a href="{{ route('accounting.vouchers.create', ['type' => 'receipt']) }}" class="btn btn-success btn-sm">Receipt</a>
            <a href="{{ route('accounting.vouchers.create', ['type' => 'payment']) }}" class="btn btn-danger btn-sm">Payment</a>
            <a href="{{ route('accounting.vouchers.create', ['type' => 'contra']) }}" class="btn btn-info btn-sm">Contra</a>
            <a href="{{ route('accounting.vouchers.create', ['type' => 'journal']) }}" class="btn btn-warning btn-sm">Journal</a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-3">
            <div class="row">
                <div class="col-md-2">
                    <select name="type" class="form-control form-control-sm"><option value="">All Types</option>
                    @foreach($types as $t)<option value="{{ $t }}" {{ request('type') == $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2"><input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}" placeholder="From"></div>
                <div class="col-md-2"><input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}" placeholder="To"></div>
                <div class="col-md-2">
                    <select name="status" class="form-control form-control-sm"><option value="">All Status</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ route('accounting.vouchers.index') }}" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </div>
        </form>

        <table class="table table-bordered table-sm">
            <thead class="thead-dark">
                <tr><th>Voucher No</th><th>Date</th><th>Type</th><th>Narration</th><th class="text-right">Amount</th><th>Branch</th><th>Status</th><th style="width:120px">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($vouchers as $v)
                <tr class="{{ $v->status == 'cancelled' ? 'text-muted' : '' }}">
                    <td><strong>{{ $v->voucher_no }}</strong></td>
                    <td>{{ $v->voucher_date->format('d-m-Y') }}</td>
                    <td><span class="badge badge-{{ match($v->voucher_type) { 'receipt'=>'success','payment'=>'danger','contra'=>'info','journal'=>'warning',default=>'secondary' } }}">{{ ucfirst($v->voucher_type) }}</span></td>
                    <td>{{ Str::limit($v->narration, 40) }}</td>
                    <td class="text-right">₹{{ number_format($v->total_amount, 2) }}</td>
                    <td>{{ $v->branch }}</td>
                    <td><span class="badge badge-{{ $v->status == 'approved' ? 'success' : ($v->status == 'cancelled' ? 'danger' : 'secondary') }}">{{ ucfirst($v->status) }}</span></td>
                    <td>
                        <a href="{{ route('accounting.vouchers.show', $v->id) }}" class="btn btn-secondary btn-sm" title="View"><i class="fa fa-eye"></i></a>
                        <a href="{{ route('accounting.vouchers.print', $v->id) }}" class="btn btn-info btn-sm" title="Print"><i class="fa fa-print"></i></a>
                        @if($v->status !== 'cancelled')
                        @hasanyrole('SuperAdmin|BranchManager')
                        <form action="{{ route('accounting.vouchers.cancel', $v->id) }}" method="POST" style="display:inline">@csrf
                            <button type="submit" class="btn btn-danger btn-sm" title="Cancel" onclick="return confirm('Cancel this voucher?')"><i class="fa fa-ban"></i></button>
                        </form>
                        @endhasanyrole
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-3">No vouchers found. Create one using the buttons above.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-2">{{ $vouchers->withQueryString()->links() }}</div>
    </div>
</div></div></div></div></div>
@endsection
