@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Ageing Report</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('accounting.outstanding.index') }}">Outstanding</a></li><li class="active">Ageing</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    <div class="card-header">
        <strong>Ageing Report — {{ ucfirst($type) }}</strong>
        <div class="float-right no-print">
            <a href="{{ route('accounting.outstanding.ageing', ['type' => 'receivable']) }}" class="btn btn-sm {{ $type == 'receivable' ? 'btn-success' : 'btn-outline-success' }}">Receivables</a>
            <a href="{{ route('accounting.outstanding.ageing', ['type' => 'payable']) }}" class="btn btn-sm {{ $type == 'payable' ? 'btn-danger' : 'btn-outline-danger' }}">Payables</a>
            <button onclick="window.print()" class="btn btn-secondary btn-sm ml-2"><i class="fa fa-print"></i></button>
        </div>
    </div>
    <div class="card-body">
        @foreach($buckets as $label => $items)
        @if($items->count())
        <h6 class="mt-3 mb-2"><span class="badge badge-{{ Str::contains($label, '90+') ? 'danger' : (Str::contains($label, '6') ? 'warning' : 'secondary') }}">{{ $label }}</span> — {{ $items->count() }} entries — ₹ {{ number_format($items->sum('pending_amount'), 0) }}</h6>
        <table class="table table-bordered table-sm">
            <thead class="thead-light"><tr><th>Ref</th><th>Party</th><th>Date</th><th class="text-right">Total</th><th class="text-right">Paid</th><th class="text-right">Pending</th><th>Branch</th></tr></thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td><strong>{{ $item->invoice_ref }}</strong></td>
                    <td>{{ Str::limit($item->party_name, 30) }}</td>
                    <td>{{ $item->invoice_date->format('d-m-Y') }}</td>
                    <td class="text-right">₹{{ number_format($item->total_amount, 0) }}</td>
                    <td class="text-right">₹{{ number_format($item->paid_amount, 0) }}</td>
                    <td class="text-right font-weight-bold">₹{{ number_format($item->pending_amount, 0) }}</td>
                    <td>{{ $item->branch }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        @endforeach

        @if(collect($buckets)->flatten()->isEmpty())
        <div class="text-center text-muted py-5">No pending {{ $type }} entries found.</div>
        @endif
    </div>
</div></div></div></div></div>
@endsection
