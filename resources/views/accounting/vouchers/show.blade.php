@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>{{ $voucher->voucher_no }}</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('accounting.vouchers.index') }}">Vouchers</a></li><li class="active">{{ $voucher->voucher_no }}</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-lg-10 offset-lg-1"><div class="card">
    <div class="card-header">
        <strong>{{ \App\Models\Accounting\Voucher::getTypeLabel($voucher->voucher_type) }}</strong>
        <span class="badge badge-{{ match($voucher->voucher_type) { 'receipt'=>'success','payment'=>'danger','contra'=>'info','journal'=>'warning',default=>'secondary' } }} ml-1">{{ $voucher->voucher_no }}</span>
        <span class="badge badge-{{ $voucher->status == 'approved' ? 'success' : 'danger' }} ml-1">{{ ucfirst($voucher->status) }}</span>
        <div class="float-right">
            <a href="{{ route('accounting.vouchers.print', $voucher->id) }}" class="btn btn-info btn-sm"><i class="fa fa-print"></i> Print</a>
            <a href="{{ route('accounting.vouchers.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3"><strong>Date:</strong><br>{{ $voucher->voucher_date->format('d M Y') }}</div>
            <div class="col-md-3"><strong>Branch:</strong><br>{{ $voucher->branch }}</div>
            <div class="col-md-3"><strong>Created By:</strong><br>{{ $voucher->creator->name ?? '-' }}</div>
            <div class="col-md-3"><strong>Amount:</strong><br><strong style="font-size:16px">₹ {{ number_format($voucher->total_amount, 2) }}</strong></div>
        </div>

        <div class="alert alert-light"><strong>Narration:</strong> {{ $voucher->narration }}</div>

        <table class="table table-bordered table-sm">
            <thead class="thead-dark">
                <tr><th>Account Code</th><th>Account Name</th><th class="text-right">Debit (₹)</th><th class="text-right">Credit (₹)</th></tr>
            </thead>
            <tbody>
                @foreach($voucher->entries as $entry)
                <tr>
                    <td>{{ $entry->account->code }}</td>
                    <td>{{ $entry->account->name }}</td>
                    <td class="text-right">{{ $entry->debit > 0 ? number_format($entry->debit, 2) : '' }}</td>
                    <td class="text-right">{{ $entry->credit > 0 ? number_format($entry->credit, 2) : '' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="font-weight-bold bg-light">
                <tr>
                    <td colspan="2" class="text-right">TOTAL</td>
                    <td class="text-right">₹ {{ number_format($voucher->entries->sum('debit'), 2) }}</td>
                    <td class="text-right">₹ {{ number_format($voucher->entries->sum('credit'), 2) }}</td>
                </tr>
            </tfoot>
        </table>

        @if($voucher->approved_by)
        <small class="text-muted">Approved by: {{ $voucher->approver->name ?? '-' }} at {{ $voucher->approved_at?->format('d M Y H:i') }}</small>
        @endif
    </div>
</div></div></div></div></div>
@endsection
